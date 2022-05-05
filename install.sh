#!/bin/bash
#
#/
#/ Usage:
#/ install.sh <options>
#/ 
#/ Installs Webcron Management
#/
#/ Options:
#/  -e, --environment      The kind of environment you want to install (dev, main or release tag)
#/  -h, --help             Display this help text
#/ 
#/ Exit Codes:
#/  1 Dependencies not met
#/  2 Installation failed
#/

## Dependencies
php=8.0
npm=7.0

## Globals
script_name=$(basename "${0}")
script_dir=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
help=false
verbose=false
environment=main
root=/tmp/webcron

APP_ENV="prod"
DATABASE_URL="mysql://root:letmein@127.0.0.1:3306/webcron"
APP_SECRET=$(tr -dc A-Za-z0-9 </dev/urandom | head -c 20 ; echo '')
ENCRYPTION_METHOD="AES-256-CBC"
HASHING_METHOD="sha256"
DEBUG=false
COOKIE_LIFETIME=2592000
TZ=Europe/Brussels
TRUSTED_PROXIES=127.0.0.1
MAILER_DSN=native://default
MAILER_FROM=www-data@example.com

Usage() {
	grep '^#/' "${script_dir}/${script_name}" | sed 's/^#\/\w*//'
}

CheckDep() {
    app=$1
    cmd=$2
    cmderrormsg=$3
    cmderrrortype=$4
    version=$5
    versioncmd=$6
    versionerrormsg=$7
    versionerrortype=$8

    echo -n "Check ${app}..."
    bash -c "${cmd}" 2> /dev/null 1> /dev/null
    installed=$?
    if [[ $installed != 0 ]]; then
        if [[ ${cmderrrortype} == 'FAIL' ]]; then
            echo -e "\e[1;31mFAILED\e[0m"
            echo "${cmderrormsg}"
            exit
        else
            echo -e "\e[1;33mWARNING\e[0m"
            echo "${cmderrormsg}"
        fi
    else
        if [[ ${version} == '' ]]; then
            echo -e "\e[1;32mOK\e[0m"
        fi
    fi

    if [[ ${version} != '' ]]; then
        installed=$(bash -c "${versioncmd}" 2> /dev/null)
        echo -n "${installed}..."
        
        if [[ $(vercomp ${installed} ${version}) == 2 ]]; then
            if [[ ${versionerrortype} == 'FAIL' ]]; then
                echo -e "\e[1;31mFAILED\e[0m"
                echo "${versionerrormsg}"
                exit
            else
                echo -e "\e[1;33mWARNING\e[0m"
                echo "${versionerrormsg}"
            fi
        else
            echo -e "\e[1;32mOK\e[0m"
        fi
    fi
}

CheckDeps() {
    CheckDep "git" "git --version" "git is not available. Exiting" "FAIL"
    CheckDep "PHP" "php --version" "PHP is not available. Exiting" "FAIL" ${php} "echo '<?php echo phpversion();' | php" "PHP version too low. Exiting" "FAIL"
    CheckDep "Composer" "composer --version" "Composer is not available. Exiting" "FAIL"
    CheckDep "MySQL" "/usr/sbin/mysqld --version" "MySQL is not available. SQLite can be used" "WARNING"
    CheckDep "NodeJS" "node --version" "NodeJS is not available. Exiting" "FAIL"
    CheckDep "NPM" "npm --version" "NPM is not available. Exiting" "FAIL" ${npm} "npm --version" "NPM version too low. Exiting" "FAIL"
    CheckDep "php-pcntl" "php -me | grep pcntl" "php-pcntl extension is not available. Cronjobs will not be running asyncronous" "WARNING"
    CheckDep "php-intl" "php -me | grep intl" "php-intl extension is not available. Exiting" "FAIL"
    CheckDep "php-xml" "php -me | grep xml" "php-xml extension is not available. Exiting" "FAIL"
    echo -e "\e[1;32mDependency test OK\e[0m"
}

Install() {
    echo -e "\e[1mInstalling to ${root}\e[0m"
    if [[ -d "$root/.git" ]]; then
        echo -n "Updating repository..."
        cd $root
        git pull 1> /dev/null 2>&1
        checkExit "$?" "0"
    else 
        echo -n "Cloning repository..."
        git clone "https://github.com/jeroened/webcron.git" $root 1> /dev/null 2>&1
        checkExit "$?" "0"
        cd $root
    fi

    CreateEnvFile

    echo -n "Checking out release..."
    git checkout $environment 1> /dev/null 2>&1
    checkExit "$?" "0"


    echo -n "Installing composer dependencies..."
    composer install --optimize-autoloader 1> /dev/null 2>&1
    checkExit "$?" "0"

    echo -n "Installing npm dependencies..."
    npm install 1> /dev/null 2>&1
    checkExit "$?" "0"

    echo -n "Compiling Javascript..."
    npx vite build 1> /dev/null 2>&1
    checkExit "$?" "0"
}

CreateEnvFile() {
    echo -n "Creating .env file..."
    cd $root
    if [[ -f ".env" ]]; then
        source .env
        rm .env 1> /dev/null 2>&1
        touch .env 1> /dev/null 2>&1
    fi
    echo "APP_ENV=\"$APP_ENV\"" >> .env
    echo "DATABASE_URL=\"$DATABASE_URL\"" >> .env
    echo "APP_SECRET=\"$APP_SECRET\"" >> .env
    echo "ENCRYPTION_METHOD=\"$ENCRYPTION_METHOD\"" >> .env
    echo "HASHING_METHOD=\"$HASHING_METHOD\"" >> .env
    echo "DEBUG=\"$DEBUG\"" >> .env
    echo "COOKIE_LIFETIME=\"$COOKIE_LIFETIME\"" >> .env
    echo "TZ=\"$TZ\"" >> .env
    echo "TRUSTED_PROXIES=\"$TRUSTED_PROXIES\"" >> .env
    echo "MAILER_DSN=\"$MAILER_DSN\"" >> .env
    echo "MAILER_FROM=\"$MAILER_FROM\"" >> .env
    echo -e "\e[1;32mOK\e[0m"
}

Finalize() {
  # touch DB file
  cd $root
  echo -n "Importing database..."
  php bin/console doctrine:schema:update --force 1> /dev/null 2>&1
  checkExit "$?" "0"
}

checkExit() {
    if [[ $1 == $2 ]]; then
        echo -e "\e[1;32mOK\e[0m"
    else
        echo -e "\e[1;31mFAILED\e[0m"
        exit 1
    fi        
}

GetOptions() {
    # https://stackoverflow.com/a/29754866
	OPTIONS=hve:r:
	LONGOPTS=help,verbose,environment:,root:

	# -use ! and PIPESTATUS to get exit code with errexit set
	# -temporarily store output to be able to check for errors
	# -activate quoting/enhanced mode (e.g. by writing out “--options”)
	# -pass arguments only via   -- "$@"   to separate them correctly
	! PARSED=$(getopt --options=$OPTIONS --longoptions=$LONGOPTS --name "$0" -- "$@")
	if [[ ${PIPESTATUS[0]} -ne 0 ]]; then
		# e.g. return value is 1
		#  then getopt has complained about wrong arguments to stdout
		Usage
		exit 2
	fi

	# read getopt’s output this way to handle the quoting right:
	eval set -- "$PARSED"

	# now enjoy the options in order and nicely split until we see --
	while true; do
		case "$1" in
			-h|--help)
				help=true
				shift
				;;
			-v|--verbose)
				if [[ $2 != "" ]]; then
					verbose=true
				fi
				shift 2
				;;
			-e|--environment)
				environment="$2"
				shift 2
				;;
			-r|--root)
				root="$2"
				shift 2
				;;
			--)
				shift
				break
				;;
			*)
                echo -e "\e[1;31mFAILED\e[0m Programming error"
				return 3
				;;
		esac
	done
}

vercomp () {
    if [[ $1 == $2 ]]
    then
        echo 1;exit;
    fi
    local IFS=.
    local i ver1=($1) ver2=($2)
    # fill empty fields in ver1 with zeros
    for ((i=${#ver1[@]}; i<${#ver2[@]}; i++))
    do
        ver1[i]=0
    done
    for ((i=0; i<${#ver1[@]}; i++))
    do
        if [[ -z ${ver2[i]} ]]
        then
            # fill empty fields in ver2 with zeros
            ver2[i]=0
        fi
        if ((10#${ver1[i]} > 10#${ver2[i]}))
        then
            echo 0;exit;
        fi
        if ((10#${ver1[i]} < 10#${ver2[i]}))
        then
            echo 2;exit;
        fi
    done
    echo 1
}

Main() {
    GetOptions $@
    if [[ $help == true ]]; then
        Usage
        return 0
    fi
    CheckDeps
    Install
    Finalize
}

Main $@
