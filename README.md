# Webcron Management
(c) 2017-2018, 2021- Jeroen De Meerleer <me@jeroened.be>

Webcron management is an easy-to-use interface to manage cronjobs running on a publicly available http-location.

## Building
### Requirements for build-server
* php <= 8.1 (incl composer <= 2, ext-pcntl, ext-openssl, ext-intl)
* NodeJS <= 16.0 (incl. npm <= 8)

### Building
Please run following command on the build server
```shell
$ composer install --no-dev --optimize-autoloader
$ npm install
$ npm run build
$ rm -rf node_modules # Node modules are only required for building
```

## Installation
### Requirements
* php <= 8.1
  * ext-openssl
  * ext-intl
  * ext-pcntl (highly recommended)
* MariaDB
* SSH-access to the server
* Ability to change the webroot directory
* Ability to run a script as daemon (eg. supervisor or systemd units)

### Installation
1. Create a build yourself or download the build from the releases page
2. Upload the build to the webserver.
3. Set up your webhosting to use the `/public` directory as web root
4. Create the .env file by copying .env.sample to .env and change the values
5. Run `php bin/console doctrine:migrations:migrate` to create or migrate the database
6. Create a first user by running `php bin/console webcron:user add`
7. Set up the daemon script using systemd, supervisord or similar system
   
   If this is not possible running the daemon using a cronjob is still possible using below gist (Not recommended)

```shell
0 * * * * cd /path/to/webcron/ && php webcron daemon --time-limit=3600 > /dev/null 1&>2
```

## Upgrading
### Requirements
Same requirements and deploying

### Procedure
1. Remove all files except .env from the webserver
2. Upload the new build to the webserver
3. Run `php bin/console doctrine:migrations:migrate` to migrate the database

## Common pitfalls
### I can't do an automatic system upgrade!
Doing a system upgrade requires sudo which has a certain number security measurements. To enable running anything with sudo (eg. `sudo apt dist-upgrade -y`) the user needs to be able to run sudo without tty and password.

TL;DR
* [disable sudo passwords](http://jeromejaglale.com/doc/unix/ubuntu_sudo_without_password) 
* [disable tty requirement](https://serverfault.com/questions/111064/sudoers-how-to-disable-requiretty-per-user)
