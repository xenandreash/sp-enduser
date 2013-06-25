#!/bin/sh

if [ ! -x '/usr/local/cpanel/bin/unregister_cpanelplugin' ];
then
	echo 'No unregister_cpanelplugin'
	exit 1
fi

rm -rf /usr/local/cpanel/base/3rdparty/sp-enduser
/usr/local/cpanel/bin/unregister_cpanelplugin cpanel-sp-enduser.cpanelplugin
