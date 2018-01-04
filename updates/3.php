<?php

if (!defined('UPDATE_IGUARD')) die('File not included');

ini_set('max_execution_time', 0);

foreach ($settings->getMessagelogTables() as $table)
{
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
	$statement = $dbh->prepare('SELECT msgaction_log FROM '.$table.' LIMIT 1;');
	if (!$statement || $statement->execute() === false) {
		echo "Adding column msgaction_log to $table. This can take several minutes depending on the size of the table...";
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$dbh->exec('ALTER TABLE '.$table.' ADD COLUMN msgaction_log TEXT;');
		echo "Done\n";

		$status = 0;
	} else {
		echo "Failed to update $table...\n";
		$status = 1;
	}
}

return $status;
