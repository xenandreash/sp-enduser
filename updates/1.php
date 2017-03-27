<?php

if (!defined('UPDATE_IGUARD')) die('File not included');

echo "Running [1.php]\n";

$dbh = $settings->getDatabase();

$statement = $dbh->prepare('SELECT * FROM dbversion LIMIT 1;');
if (!$statement || $statement->execute() === false) {
    echo "Adding table dbversion... ";
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->exec('CREATE TABLE dbversion (current INTEGER);');
    echo "Done\n";

    echo "Inserting values to dbversion... ";
    $dbh->exec('INSERT INTO dbversion (current) VALUES (0);');
    echo "Done\n";
} else {
    die("Error - table 'dbversion' already exist. Unable to continue... \n");
}

// last action, update version
$dbh->exec('UPDATE dbversion SET current = 1');