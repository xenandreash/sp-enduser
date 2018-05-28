<?php

if (!defined('UPDATE_IGUARD')) die('File not included');

ini_set('max_execution_time', 0);

$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
$statement = $dbh->prepare('SELECT comment FROM bwlist LIMIT 1;');
if (!$statement || $statement->execute() === false) {
	echo "Adding column comment to bwlist... ";
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbh->exec('ALTER TABLE bwlist ADD COLUMN comment VARCHAR(255);');
	echo "Done\n";

	return 0;
} else {
	return 1;
}
