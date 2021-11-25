# Webcron Management
(c) 2017-2018, 2021- Jeroen De Meerleer <me@jeroened.be>

Webcron management is an easy-to-use interface to manage cronjobs running on a publicly available http-location.

## Status update
Rewrite is currently beta-quality. Production-use is possible, but use with caution. Personally, I'm using it myself already in a production-like environment.

### Known bugs
* (__! Security vulnerability !__) Secret variables can become exposed in job output
* Datepicker ([Tempus dominus v6](https://getdatepicker.com/)) is currently alpha-quality software. Altough [the dev states it is usable](https://jonathanpeterson.com/posts/state-of-my-datetime-picker-part-2.html) 

## Deploying
### Requirements for web-server
* php <= 8.0
  * ext-openssl
  * ext-pcntl (highly recommended)
* MySQL/MariaDB or SQLite
* Ability to change the webroot directory
* Ability to run a script as daemon (eg. supervisor or systemd units)


### Requirements for build-server
* php <= 8.0 (incl composer <= 2)
* NodeJS <= 14.0 (incl. npm <= 7)

### Building
Please run following command on the build server
```shell
$ composer install --no-dev --optimize-autoloader
$ npm install
$ npx build prod
$ rm -rf node_modules # Node modules are only required for building
```

### Configuration
All configuration can be found in .env.sample. Please copy this to file to .env and change its values

### Installation
First follow the build and configuration instructions. If you don't follow them correctly Webcron Management won't work correctly
1. Create your database and import the storage/database.sql file into the database
2. Create a first user by inserting a first record to the users table (Password is hashed using the HASHING_METHOD in your .env)
3. Set up your webhosting to use the `/public` directory as web root
4. Upload the repository to the webserver
5. Set up the daemon script using systemd, supervisord or similar system
   * If this is not possible running the daemon using a cronjob is still possible using below gist (Not recommended)

```shell
0 * * * * cd /path/to/webcron/ && php webcron daemon --time-limit=3600 > /dev/null 1&>2
```

The webcron interface should now work as expected.

## Common pitfalls
### Cronjobs are not running
Did you edit the crontab?

### I can't do an automatic system upgrade!
Doing a system upgrade requires sudo which has a certain number security measurements. To enable running anything with sudo (eg. `sudo apt dist-upgrade -y`) the user needs to be able to run sudo without tty and password.

TL;DR
* [disable sudo passwords](http://jeromejaglale.com/doc/unix/ubuntu_sudo_without_password) 
* [disable tty requirement](https://serverfault.com/questions/111064/sudoers-how-to-disable-requiretty-per-user)