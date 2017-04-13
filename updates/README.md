Adding a new update
---------------------
Create a new version file (eg. 2.php), updates are processed sequentially so it's important not to skip a number.
Each update script should use return codes for update.php to function properly.
To enable the update, set the number in version.txt to match the new version. Site will go in maintenance mode when database version and version.txt doesn't match.

Installation script
---------------------
All database updates needs to be added to install.php script. It's also important to set the latest database version after each database change.