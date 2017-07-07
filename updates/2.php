<?php

if (!defined('UPDATE_IGUARD')) die('File not included');

$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$statement = $dbh->prepare('SELECT * FROM users_totp LIMIT 1;');
if (!$statement || $statement->execute() === false) {
	echo "Adding table users_totp... ";
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbh->exec('CREATE TABLE users_totp (username VARCHAR(128), secret TEXT, PRIMARY KEY(username));');
	echo "Done\n";

	return 0;
} else {
	return 1;
}
