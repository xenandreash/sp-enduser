<?php

define('SP_ENDUSER', true);
define('BASE', dirname(__FILE__));

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

// verify API key
if (!isset($_GET['api-key']) || $settings->getAPIKey() !== $_GET['api-key'])
	die('Invalid API-key');

// add recipient (user) to local database, send password by mail
if ($_GET['type'] == 'trigger' && isset($_GET['recipient']) && $_GET['recipient'] !== '') {
	if (!has_auth_database())
		die('No database authentication source');

	$recipient = $_GET['recipient'];
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT 1 FROM users WHERE username = :username;");
	$statement->execute(array(':username' => $recipient));
	if (!$statement->fetch()) {

		$password = generate_random_password();
		$url = $settings->getPublicURL();

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

// add message to local (SQL) history log
if ($_GET['type'] == 'log') {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare('INSERT INTO messagelog (owner, owner_domain, msgts, msgid, msgactionid, msgaction, msglistener, msgtransport, msgsasl, msgfromserver, msgfrom, msgfrom_domain, msgto, msgto_domain, msgsubject, score_rpd, score_sa, scores, msgdescription, serialno) VALUES (:owner, :ownerdomain, :msgts, :msgid, :msgactionid, :msgaction, :msglistener, :msgtransport, :msgsasl, :msgfromserver, :msgfrom, :msgfromdomain, :msgto, :msgtodomain, :msgsubject, :score_rpd, :score_sa, :scores, :msgdescription, :serialno);');
	$statement->bindValue(':owner', $_POST['owner']);
	$statement->bindValue(':ownerdomain', array_pop(explode('@', $_POST['owner'])));
	$statement->bindValue(':msgts', $_POST['msgts']);
	$statement->bindValue(':msgid', $_POST['msgid']);
	$statement->bindValue(':msgactionid', $_POST['msgactionid']);
	$statement->bindValue(':msgaction', $_POST['msgaction']);
	$statement->bindValue(':msglistener', $_POST['msglistener']);
	$statement->bindValue(':msgtransport', $_POST['msgtransport']);
	$statement->bindValue(':msgsasl', $_POST['msgsasl']);
	$statement->bindValue(':msgfromserver', $_POST['msgfromserver']);
	$statement->bindValue(':msgfrom', $_POST['msgfrom']);
	$statement->bindValue(':msgfromdomain', array_pop(explode('@', $_POST['msgfrom'])));
	$statement->bindValue(':msgto', $_POST['msgto']);
	$statement->bindValue(':msgtodomain', array_pop(explode('@', $_POST['msgto'])));
	$statement->bindValue(':msgsubject', $_POST['msgsubject']);
	$statement->bindValue(':msgdescription', $_POST['msgdescription']);
	$statement->bindValue(':serialno', $_POST['serialno']);
	if (isset($_POST['score_rpd']))
		$statement->bindValue(':score_rpd', $_POST['score_rpd']);
	else
		$statement->bindValue(':score_rpd', null, PDO::PARAM_INT);
	if (isset($_POST['score_sa']))
		$statement->bindValue(':score_sa', $_POST['score_sa']);
	else
		$statement->bindValue(':score_sa', null, PDO::PARAM_INT);
	$scores = array();
	$scores['sa'] = $_POST['score_sa_rules'];
	$scores['rpd'] = $_POST['score_rpd_refid'];
	$scores['rpdav'] = $_POST['score_rpdav'];
	$scores['kav'] = $_POST['score_kav'];
	$scores['clam'] = $_POST['score_clam'];
	$statement->bindValue(':scores', json_encode($scores));
	$statement->execute();
	die('ok');
}

// Update message in local (SQL) history log
if ($_GET['type'] == 'logupdate') {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare('UPDATE messagelog SET msgaction = :msgaction, msgdescription = :msgdescription WHERE msgid = :msgid AND msgactionid = :msgactionid AND serialno = :serialno;');
	$statement->bindValue(':msgid', $_POST['msgid']);
	$statement->bindValue(':msgaction', $_POST['msgaction']);
	$statement->bindValue(':msgdescription', $_POST['msgdescription']);
	$statement->bindValue(':serialno', $_POST['serialno']);
	$statement->bindValue(':msgactionid', $_POST['msgactionid']);
	$statement->execute();
	die('ok');
}

// check bwlist
if ($_GET['type'] == 'bwcheck' && isset($_GET['senderip']) || isset($_GET['sender']) || isset($_GET['recipient'])) {
	$dbh = $settings->getDatabase();

	$senderip = $_GET['senderip'];
	$sender = $_GET['sender'];
	@list($tmp, $senderdomain) = explode('@', $_GET['sender']);
	$recipient = $_GET['recipient'];
	@list($tmp, $recipientdomain) = explode('@', $_GET['recipient']);

	$statement = $dbh->prepare("SELECT * FROM bwlist WHERE (".
			"access = :recipient OR ".
			"access = :recipientdomain OR ".
			"access = ''".
			") AND (".
			"value = :senderip OR ".
			"value = :senderdomain OR ".
			"value = :sender OR ".
			"(CASE WHEN SUBSTR(value, 1, 1) = '.' AND SUBSTR(:senderdomain, LENGTH(:senderdomain) - LENGTH(value) + 1) = value THEN 1 ELSE 0 END) = 1".
		");");
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

die('ok');
