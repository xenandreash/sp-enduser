#!/bin/sh

if [ ! -x '/usr/local/cpanel/bin/register_cpanelplugin' ];
then
	echo 'No register_cpanelplugin'
	exit 1
fi

rm -rf /usr/local/cpanel/base/3rdparty/sp-enduser
cp -R . /usr/local/cpanel/base/3rdparty/sp-enduser
rm -f /usr/local/cpanel/base/3rdparty/sp-enduser/install.php
mv /usr/local/cpanel/base/3rdparty/sp-enduser/settings.php.default \
	/usr/local/cpanel/base/3rdparty/sp-enduser/settings.php

/usr/local/cpanel/bin/register_cpanelplugin cpanel-sp-enduser.cpanelplugin
echo 'Halon SP-enduser interface installed into cPanel.'
echo 'Configure: /usr/local/cpanel/base/3rdparty/sp-enduser/settings.php'
exit
