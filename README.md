End-user web application for Halon's email gateway. Please read more on https://halon.io

Requirements
------------
* PHP compatible web server (Apache, NGINX, IIS)
* PHP (>=5.6) or HHVM
* Recommended PHP modules
    * curl
    * openssl
    * dom
    * gettext
    * hash
    * session
    * soap
    * ldap
    * pdo
    * pdo-(database; mysql, pgsql, sqlite...)
    * rrd
* [Composer](https://getcomposer.org)

Installation
------------
1. Navigate to the directory you uploaded the files to and run `composer install` to install any dependencies
2. Copy the `/settings-default.php` file to `/settings.php` and open it to configure the database settings

Plugins
-------
* cPanel https://github.com/halon/sp-enduser-cpanel
* Odin (APS2) https://github.com/halon/sp-enduser-aps2
* Plesk https://github.com/halon/sp-enduser-plesk
