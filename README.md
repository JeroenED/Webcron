# Webcron Management
(c) 2017 Jeroen De Meerleer <me@jeroened.be>

Webcron management is an easy-to-use interface to manage cronjob running on a publicly available http-location.

## Requirements
* Webserver able to run PHP
* PHP 7.0 or greater
* MySQL/MariaDB
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

## Licence

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.
