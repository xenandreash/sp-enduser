<?php

$version['update_required'] = false;

function getCurrentVersion() {
	global $settings;
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare('SELECT current FROM dbversion');
	if ($statement && $statement->execute()) {
		$result = $statement->fetch(PDO::FETCH_ASSOC);
		return $result['current'];
	} else {
		return 0;
	}
}

function getAvailableVersion() {
	return (int) file_get_contents(BASE.'/updates/version.txt');
}

function isUpdateRequired() {
	return getAvailableVersion() > getCurrentVersion();
}

if ($settings->getDatabase()) {
	$version['update_required'] = isUpdateRequired();
}