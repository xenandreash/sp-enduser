#!/bin/bash
set -e

if [ $EUID -ne 0 ]; then
	echo "This script requires root privileges, please run it as root, or with:"
	echo "    sudo ./cpanel-install.sh"
	exit 1
fi

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
FDEST="/usr/local/cpanel/base/frontend/default"
FILE="$DIR/cpanel-sp-enduser.live.php"

OLD_MTIME=""
while true; do
	MTIME=$(stat -c %Z $FILE)
	[[ "$OLD_MTIME" != "$MTIME" ]] && echo "Change detected" && cp $FILE $FDEST
	OLD_MTIME=$MTIME
	sleep 1
done
