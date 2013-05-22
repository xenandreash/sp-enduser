<?php

require_once('inc/core.php');
require_once('inc/utils.php');

// verify API key
if (!isset($settings['api-key']) || !isset($_GET['api-key']) || $settings['api-key'] !== $_GET['api-key'])
	die('Invalid API-key');

// add recipient (user) to local database, send password by mail
if ($_GET['type'] == 'trigger' && isset($_GET['recipient']) && $_GET['recipient'] !== '') {

	if (!isset($settings['database']['dsn']))
		die('No database configured');

	$recipient = $_GET['recipient'];
	$dbh = new PDO($settings['database']['dsn'], $settings['database']['user'], $settings['database']['password']);
	$statement = $dbh->prepare("SELECT 1 FROM users WHERE username = :username;");
	$statement->execute(array(':username' => $recipient));
	if (!$statement->fetch()) {

		$password = generate_random_password();
		$url = $settings['public-url'];

		$dbh->beginTransaction();
		$statement = $dbh->prepare("INSERT INTO users (username, password) VALUES (:username, :password);");
		$statement->execute(array(':username' => $recipient, 'password' => crypt($password)));
		$statement = $dbh->prepare("INSERT INTO users_relations (username, type, access) VALUES (:username, 'mail', :username);");
		$statement->execute(array(':username' => $recipient));

		if (!$dbh->commit())
			die('Database INSERT failed');

		mail2($recipient, "New account information", "An accounts has been created for you in the end-user interface at $url \r\n\r\nUsername: $recipient \r\nPassword: $password");
	}
	die('ok');
}

// check bwlist
if ($_GET['type'] == 'bwcheck' && isset($_GET['senderip']) || isset($_GET['sender']) || isset($_GET['recipient'])) {

	if (!isset($settings['database']['dsn']))
		die('No database configured');

	$dbh = new PDO($settings['database']['dsn'], $settings['database']['user'], $settings['database']['password']);

	$senderip = $_GET['senderip'];
	$sender = $_GET['sender'];
	@list($tmp, $senderdomain) = explode('@', $_GET['sender']);
	$recipient = $_GET['recipient'];
	@list($tmp, $recipientdomain) = explode('@', $_GET['recipient']);

	$statement = $dbh->prepare("SELECT * FROM bwlist WHERE (access = :recipient OR access = :recipientdomain) AND (value = :senderip OR value = :senderdomain OR value = :sender);");
	$statement->execute(array(':recipient' => $recipient, ':recipientdomain' => $recipientdomain, ':senderip' => $senderip, ':senderdomain' => $senderdomain, ':sender' => $sender));
	$blacklist = array();
	$whitelist = array();
	while ($row = $statement->fetch()) {
		if ($row['type'] == 'blacklist')
			$blacklist[] = $row['value'];
		if ($row['type'] == 'whitelist')
			$whitelist[] = $row['value'];
	}
	if (count($whitelist))
		die('whitelist');
	if (count($blacklist))
		die('blacklist');
	die('unknown');
}

?>
