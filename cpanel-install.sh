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

# Destination path, because nobody likes repeating themselves
DEST="/usr/local/cpanel/base/3rdparty/sp-enduser"



echo "Installing SP-Enduser's cPanel plugin to:"
echo "$DEST"
echo ""

# Only move things if they're not already in the right place
if [[ "$DIR" != "$DEST" ]]; then
	
	# If there's an old (copied) installation, abort and tell the user
	if [[ -L $DEST ]]; then
		echo "An existing link has been found, updating the link..."
		rm $DEST
		ln -s $DIR $DEST
	elif [[ -d $DEST ]]; then
		echo "An existing installation has been found."
		echo ""
		echo "Please either run 'git pull' and './cpanel-install.sh' in the existing"
		echo "installation to update it, or move it aside to set up a new installation."
		exit 1
	else
		echo "There are two ways to install SP-Enduser, choose the one you would like:"
		echo ""
		echo "== Copy this directory to the destination =="
		echo ""
		echo "   This is the easiest way to get SP-Enduser installed as a cPanel plugin,"
		echo "   but you can't, for instance, share a single installation between cPanel"
		echo "   and Apache, if you're going for that kind of setup."
		echo ""
		echo "   With this kind of install, the source directory is no longer needed."
		echo ""
		echo "== Link the source to the destination =="
		echo ""
		echo "   This will allow you to keep your SP-Enduser installation somewhere other"
		echo "   than in cPanel's files, for instance if you want to share a single"
		echo "   installation between cPanel and Apache, or you just want easier access"
		echo "   for configuration and custom modifications."
		echo ""
		echo "   With this kind of install, this directory needs to remain where it is!"
		echo "   If you move it, you must update the link, or re-run this script and"
		echo "   it'll do it for you."
		echo ""
		
		keep_around=false
		select type in "Copy" "Link"; do
			case $type in
				"Copy")
					echo ""
					echo "Copy selected, installing..."
					cp -Rf "$DIR" "$DEST"
					break;;
				"Link")
					echo ""
					echo "Link selected, installing..."
					ln -s "$DIR" "$DEST"
					keep_around=true
					break;;
			esac
		done
		
		# Register the plugin with cPanel; output is written 
		echo "Registering the plugin with cPanel... (this may take a while)"
		if /usr/local/cpanel/bin/register_cpanelplugin $DIR/cpanel-sp-enduser.cpanelplugin > $DIR/register_cpanelplugin.log; then
			# If the installation succeeds, just quietly delete the log file
			rm $DIR/register_cpanelplugin.log
		else
			echo " -> Couldn't register the plugin!"
			echo "    Output has been written to register_cpanelplugin.log"
			exit 1
		fi
		
		echo ""
		echo "Halon SP-Enduser has been successfully installed!"
		echo ""
		
		if $keep_around; then
			echo "Remember to keep this directory around - the installation is a link, and will"
			echo "cease to function if this is moved or deleted."
			echo ""
			echo "If you want to move or rename it, remember to re-run this script to relink it."
		else
			echo "You can safely delete this directory, it has been copied to:"
			echo "$DEST"
		fi
		
		echo ""
		echo "To configure SP-Enduser, copy settings-default.php to settings.php and edit it."
		echo "Check the Wiki for installation instructions: http://wiki.halon.se/End-user"
	fi
fi
