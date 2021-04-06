# Webcron Management
(c) 2017, 2021 Jeroen De Meerleer <me@jeroened.be>

Webcron management is an easy-to-use interface to manage cronjob running on a publicly available http-location.

## Status update
I'm currently in the process of rewriting the application to more modern standards. The current main branch is very unstable at the moment. Please don't use it.

I encourage everyone to wait for the new version as upgrading will probably be very difficult.

### What will change with the rewrite?
* All urls will change. eg. /login/ and /jobs/5/edit/ instead of /login.php and editjob.php?jobId=5
* Dropping support for directly calling webcron.php from url-bar
* Daemonized main-script which will enable running cronjobs by seconds

## Requirements
* Webserver able to run PHP
* PHP 8.0 or greater
* MySQL/MariaDB (Or sqLite)
* Ability to add a system cronjob for installation (You can maybe ask you webhost?)

## Instalation

Follow the instructions below to install the webcron interface
1. Copy this repository to a public directory on your server
2. Create a database using the database.sql provided in the repository
3. Create a first user by inserting a first record to the users table (Password is hashed with bcrypt)
4. Run `composer install` to install dependencies.
5. Open ssh and add following line to your crontab

```
* * * * cd /path/to/webcron/ && php webcron.php > /dev/null 1&>2
```

## Common pittfalls
### Cronjobs are not running
Did you edit the crontab?

### I can't do an automatic system upgrade!
Doing a system upgrade requires sudo which has a certain number security measurements. To enable running anything with sudo (eg. `sudo apt dist-upgrade -y`) the user needs to be able to run sudo without tty and password.

TL;DR
* [disable sudo passwords](http://jeromejaglale.com/doc/unix/ubuntu_sudo_without_password) 
* [disable tty requirement](https://serverfault.com/questions/111064/sudoers-how-to-disable-requiretty-per-user)

### Can I schedule a reboot every week?
Yes, you can do this by creating a job with `reboot` as "url". When this job needs to run, the reboot is triggered to run at the very end. At the first run of the master script a list of active and terribly failed services is pushed to the job so you can check this if something is wrong.
