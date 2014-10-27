#!/bin/bash

# 
# Most comments are in cpanel-install.sh
# 

set -e

if [ $EUID -ne 0 ]; then
	echo "This script requires root privileges, please run it as root, or with:"
	echo "    sudo ./cpanel-uninstall.sh"
	exit 1
fi

if [ ! -x '/usr/local/cpanel/bin/unregister_cpanelplugin' ];
then
	echo "Can't seem to find unregister_cpanelplugin, is cPanel installed properly?"
	exit 1
fi



DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
DEST="/usr/local/cpanel/base/3rdparty/sp-enduser"

if [[ ! -d $DEST ]]; then
	echo "The SP-Enduser cPanel plugin is not installed!"
	exit 1
fi



echo "Unregistering the plugin with cPanel..."
if /usr/local/cpanel/bin/unregister_cpanelplugin $DIR/cpanel-sp-enduser.cpanelplugin > $DIR/unregister_cpanelplugin.log; then
	rm $DIR/unregister_cpanelplugin.log
else
	echo " -> Couldn't unregister the plugin!"
	echo "    Output has been written to unregister_cpanelplugin.log"
	exit 1
fi
echo ""

echo "Plugin unregistered, what would you like to do with your SP-Enduser install?"
if [[ -L $DEST ]]; then
	echo ""
	echo "Your installation is linked, so choosing 'Delete it' will only delete the link."
	echo "To permanently get rid of it, manually delete the source folder:"
	echo "    `readlink -f $DEST`"
else
	echo "Warning: Deletion is final, and can't be undone!"
fi

echo ""
select action in "Leave it" "Delete it"; do
	case $action in
		"Leave it")
			echo "Your installation has been left intact."
			echo "You can find it at:"
			echo "    $DEST"
			break;;
		"Delete it")
			rm -rf $DEST
			echo "SP-Enduser has been deleted."
			break;;
	esac
done
