#!/bin/bash

# Exit if anything errors; better safe than sorry
set -e

if [ $EUID -ne 0 ]; then
	echo "This script requires root privileges, please run it as root, or with:"
	echo "    sudo ./cpanel-install.sh"
	exit 1
fi

if [ ! -x '/usr/local/cpanel/bin/register_cpanelplugin' ];
then
	echo "Can't seem to find register_cpanelplugin, is cPanel installed properly?"
	exit 1
fi



# Figure out where we are; relative paths are evil
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Destination paths, because nobody likes repeating themselves
DEST="/usr/local/cpanel/base/3rdparty/sp-enduser"



echo "Installing SP-Enduser's cPanel plugin to:"
echo "$DEST"
echo ""

# Only move things if they're not already in the right place
if [[ "$DIR" != "$DEST" ]]; then
	
	# If there's an old (copied) installation, abort and tell the user
	if [[ -d $DEST ]]; then
		echo "An existing installation has been found."
		echo ""
		echo "Please either run 'git pull' and './cpanel-install.sh' in the existing"
		echo "installation to update it, or move it aside to set up a new installation."
		exit 1
	else
		cp -Rf "$DIR" "$DEST"
		
		# Register the plugin with cPanel; output is written 
		echo "Registering the plugin with cPanel... (this may take a while)"
		if /usr/local/cpanel/bin/register_cpanelplugin $DIR/cpanel-sp-enduser.cpanelplugin > $DIR/register_cpanelplugin.log; then
			# If the installation succeeds, just quietly delete the log file
			rm $DIR/register_cpanelplugin.log
		else
			echo " -> Couldn't register the plugin!"
			echo "    Output has been written to:"
			echo "    $DIR/register_cpanelplugin.log"
			exit 1
		fi
		
		echo ""
		echo "Halon SP-Enduser's cPanel plugin has been successfully installed!"
		echo ""
		echo "You can safely delete this directory, it has been copied to:"
		echo "$DEST"
		echo ""
		echo "To configure SP-Enduser, copy settings-default.php to settings.php and edit it."
		echo "Check the Wiki for installation instructions: http://wiki.halon.se/End-user"
	fi
fi
