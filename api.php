<?php

define('BASE', dirname(__FILE__));

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

if ($version['update_required'])
	panic('Site in maintenance mode.');

// verify API key
if (!isset($_GET['api-key']) || $settings->getAPIKey() !== $_GET['api-key'])
	panic('Invalid API-key');

// add recipient (user) to local database, send password by mail
if ($_GET['type'] == 'trigger' && isset($_GET['recipient']) && $_GET['recipient'] !== '') {
	if (!has_auth_database())
		panic('No database authentication source');

	$recipient = $_GET['recipient'];
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT 1 FROM users WHERE username = :username;");
	$statement->execute(array(':username' => $recipient));
	if (!$statement->fetch(PDO::FETCH_ASSOC)) {

		$password = password_hash(generate_random_password(), PASSWORD_DEFAULT);

		$token = uniqid();
		$publictoken = hash_hmac('sha256', $password, $token);

		$dbh->beginTransaction();
		$statement = $dbh->prepare("INSERT INTO users (username, password, reset_password_token) VALUES (:username, :password, :token);");
		$statement->execute(array(':username' => $recipient, 'password' => $password, 'token' => $token));
		$statement = $dbh->prepare("INSERT INTO users_relations (username, type, access) VALUES (:username, 'mail', :username);");
		$statement->execute(array(':username' => $recipient));

		if (!$dbh->commit())
			panic('Database INSERT failed');

		$smarty_no_assign = true;
		require BASE.'/inc/smarty.php';
		$smarty->assign('email', $recipient);
		$smarty->assign('register_url', $settings->getPublicURL()."/?page=forgot&reset=$recipient&type=create&token=$publictoken");

		$headers = array();
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = 'Content-Transfer-Encoding: base64';

		$body = $smarty->fetch('newuser.mail.tpl');
		$subject = $smarty->getTemplateVars('subject');

		mail2($recipient, $subject, chunk_split(base64_encode($body)), $headers);
	}
	// 'ok' response is checked by deprecated Quarantine implementaton
	success_text('ok');
}

// add message to local (SQL) history log
if ($_GET['type'] == 'log') {
	$dbh = $settings->getDatabase();
	if (isset($_POST['userid'])) {
		$statement = $dbh->prepare('INSERT INTO '.$settings->getMessagelogTable($_POST['userid']).' (userid, owner, owner_domain, msgts, msgid, msgactionid, msgaction, msglistener, msgtransport, msgsasl, msgfromserver, msgfrom, msgfrom_domain, msgto, msgto_domain, msgsubject, msgsize, score_rpd, score_sa, scores, msgdescription, serialno, msgaction_log) VALUES (:userid, :owner, :ownerdomain, :msgts, :msgid, :msgactionid, :msgaction, :msglistener, :msgtransport, :msgsasl, :msgfromserver, :msgfrom, :msgfromdomain, :msgto, :msgtodomain, :msgsubject, :msgsize, :score_rpd, :score_sa, :scores, :msgdescription, :serialno, :msgactionlog);');
		$statement->bindValue(':userid', $_POST['userid']);
	} else {
		$statement = $dbh->prepare('INSERT INTO '.$settings->getMessagelogTable($_POST['userid']).' (owner, owner_domain, msgts, msgid, msgactionid, msgaction, msglistener, msgtransport, msgsasl, msgfromserver, msgfrom, msgfrom_domain, msgto, msgto_domain, msgsubject, msgsize, score_rpd, score_sa, scores, msgdescription, serialno, msgaction_log) VALUES (:owner, :ownerdomain, :msgts, :msgid, :msgactionid, :msgaction, :msglistener, :msgtransport, :msgsasl, :msgfromserver, :msgfrom, :msgfromdomain, :msgto, :msgtodomain, :msgsubject, :msgsize, :score_rpd, :score_sa, :scores, :msgdescription, :serialno, :msgactionlog);');
	}
	$statement->bindValue(':owner', $_POST['owner']);
	$statement->bindValue(':ownerdomain', extract_domain($_POST['owner']));
	$statement->bindValue(':msgts', round($_POST['msgts']));
	$statement->bindValue(':msgid', $_POST['msgid']);
	$statement->bindValue(':msgactionid', $_POST['msgactionid']);
	$statement->bindValue(':msgaction', $_POST['msgaction']);
	$statement->bindValue(':msglistener', $_POST['msglistener']);
	$statement->bindValue(':msgtransport', $_POST['msgtransport']);
	$statement->bindValue(':msgsasl', $_POST['msgsasl']);
	$statement->bindValue(':msgfromserver', $_POST['msgfromserver']);
	$statement->bindValue(':msgfrom', $_POST['msgfrom']);
	$statement->bindValue(':msgfromdomain', extract_domain($_POST['msgfrom']));
	$statement->bindValue(':msgto', $_POST['msgto']);
	$statement->bindValue(':msgtodomain', extract_domain($_POST['msgto']));
	$statement->bindValue(':msgsubject', $_POST['msgsubject']);
	$statement->bindValue(':msgsize', $_POST['msgsize']);
	$statement->bindValue(':msgdescription', is_array($_POST['msgdescription']) ? implode('|', $_POST['msgdescription']) : $_POST['msgdescription']);
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

	$ts0 = isset($_POST['msgts0']) ? $_POST['msgts0'] : time();
	$statement->bindValue(':msgactionlog', json_encode(Array(['action' => $_POST['msgaction'], 'details' => $_POST['msgdescription'], 'ts0' => round($ts0)])));
	$statement->execute();

	// Database graphs
	if ($settings->getUseDatabaseStats())
	{
		$transports = $settings->getDisplayTransport();
		$listeners = $settings->getDisplayListener();
		if (($_POST['msgaction'] == 'QUEUE' || $_POST['msgaction'] == 'REJECT') && (isset($listeners[$_POST['msglistener']]) || isset($transports[$_POST['msgtransport']]))) {
			$reject = $deliver = 0;
			if ($_POST['msgaction'] == 'REJECT') $reject = 1;
			if ($_POST['msgaction'] == 'QUEUE') $deliver = 1;
			if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
				$statement = $dbh->prepare("INSERT INTO stat (userid, direction, domain, year, month, reject, deliver) VALUES (:userid, :direction, :domain, date_part('year', CURRENT_DATE), date_part('month', CURRENT_DATE), :reject, :deliver) ON CONFLICT (direction, domain, year, month) DO UPDATE SET reject = stat.reject + EXCLUDED.reject, deliver = stat.deliver + EXCLUDED.deliver;");
			} else {
				$statement = $dbh->prepare('INSERT INTO stat (userid, direction, domain, year, month, reject, deliver) VALUES (:userid, :direction, :domain, YEAR(NOW()), MONTH(NOW()), :reject, :deliver) ON DUPLICATE KEY UPDATE reject = reject + VALUES(reject), deliver = deliver + VALUES(deliver);');
			}
			$statement->bindValue(':userid', $_POST['userid']);
			if ($listeners[$_POST['msglistener']] == 'Outbound' || $transports[$_POST['msgtransport']] == 'Internet') {
				$statement->bindValue(':direction', 'outbound');
				$statement->bindValue(':domain', extract_domain($_POST['msgfrom']));
			} else {
				$statement->bindValue(':direction', 'inbound');
				$statement->bindValue(':domain', extract_domain($_POST['msgto']));
			}
			$statement->bindValue(':reject', $reject);
			$statement->bindValue(':deliver', $deliver);
			$statement->execute();
		}
	}

	success_json(array('status'=>'success'));
}

// Update message in local (SQL) history log
if ($_GET['type'] == 'logupdate') {
	$dbh = $settings->getDatabase();

	if ($_POST['msgaction'] == '')
		$msgaction = 'DELIVER';
	else if ($_POST['msgaction'] == 'RETRY')
		$msgaction = 'QUEUE';
	else
		$msgaction = $_POST['msgaction'];

	$statement = $dbh->prepare('SELECT msgaction, msgdescription, msgaction_log FROM '.$settings->getMessagelogTable($_POST['userid']).' WHERE msgid = :msgid AND msgactionid = :msgactionid AND serialno = :serialno;');
	$statement->bindValue(':msgid', $_POST['msgid']);
	$statement->bindValue(':msgactionid', $_POST['msgactionid']);
	$statement->bindValue(':serialno', $_POST['serialno']);
	$statement->execute();
	if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		$msgactionlog = ($row['msgaction_log'] != null) ? json_decode($row['msgaction_log']) : [];

		if ($msgaction != $row['msgaction']) {
			if (isset($_POST['predelivery']) && $_POST['predelivery'] == true && $row['msgaction'] != 'QUEUE' && $msgaction != 'QUEUE') {
				if (end($msgactionlog) != 'QUEUE') {
					$logaction = [];
					$logaction['action'] = 'QUEUE';
					$logaction['details'] = '';
					$logaction['ts0'] = isset($_POST['msgts0']) ? round($_POST['msgts0']) : time();
					$msgactionlog[] = $logaction;
				}
			}

			$logaction = [];
			$logaction['action'] = $msgaction;
			$logaction['details'] = $_POST['msgdescription'];
			$logaction['ts0'] = isset($_POST['msgts0']) ? round($_POST['msgts0']) : time();
			$msgactionlog[] = $logaction;
		}
	} else {
		$msgactionlog = [];
	}

	$statement = $dbh->prepare('UPDATE '.$settings->getMessagelogTable($_POST['userid']).' SET msgaction = :msgaction, msgdescription = :msgdescription, msgaction_log = :msgaction_log WHERE msgid = :msgid AND msgactionid = :msgactionid AND serialno = :serialno;');
	$statement->bindValue(':msgid', $_POST['msgid']);
	$statement->bindValue(':msgaction', $msgaction);
	$statement->bindValue(':msgdescription', $_POST['msgdescription']);
	$statement->bindValue(':serialno', $_POST['serialno']);
	$statement->bindValue(':msgactionid', $_POST['msgactionid']);
	$statement->bindValue(':msgaction_log', json_encode($msgactionlog));
	$statement->execute();
	success_json(array('status'=>'success'));
}

// check bwlist
if ($_GET['type'] == 'bwcheck' && (isset($_GET['senderip']) || isset($_GET['sender']) || isset($_GET['recipient']))) {
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
	while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		if ($row['type'] == 'blacklist')
			$blacklist[] = $row['value'];
		if ($row['type'] == 'whitelist')
			$whitelist[] = $row['value'];
	}

	// 'text' response is checked by deprecated ScanBWList implementaton
	if (count($whitelist))
		success_text('whitelist');
	if (count($blacklist))
		success_text('blacklist');
	success_text('unknown');
}

if ($_GET['type'] == 'bwlist') {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT access, type, value FROM bwlist;");
	$statement->execute();
	success_json($statement->fetchAll(PDO::FETCH_OBJ));
}

if ($_GET['type'] == 'spamsettings') {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT * FROM spamsettings;");
	$statement->execute();
	$result = array_map(function ($r) { $r['settings'] = json_decode($r['settings']); return $r; }, $statement->fetchAll(PDO::FETCH_ASSOC));
	success_json($result);
}

if ($_GET['type'] == 'datastore') {
	$dbh = $settings->getDatabase();
	$where = '';
	$wheres = array();
	if (isset($_GET['ns']))
		$wheres[] = 'namespace = :namespace';
	if (isset($_GET['key']))
		$wheres[] = 'keyname = :key';
	if (isset($_GET['value']))
		$wheres[] = 'value = :value';
	if (count($wheres))
		$where = 'WHERE '.implode(' AND ', $wheres);
	$sql = 'SELECT * FROM datastore';
	if ($where) $sql .= " $where";
	$statement = $dbh->prepare($sql);
	if (isset($_GET['ns']))
		$statement->bindValue(':namespace', $_GET['ns']);
	if (isset($_GET['key']))
		$statement->bindValue(':key', $_GET['key']);
	if (isset($_GET['value']))
		$statement->bindValue(':value', $_GET['value']);
	$statement->execute();
	$result = array();
	$i = 0;
	while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		$result[$i]['namespace'] = is_numeric($row['namespace']) ? floatval($row['namespace']) : $row['namespace'];
		$result[$i]['key'] = is_numeric($row['keyname']) ? floatval($row['keyname']) : $row['keyname'];
		$result[$i]['value'] = is_numeric($row['value']) ? floatval($row['value']) : $row['value'];
		$i += 1;
	}
	success_json($result);
}

panic('Unsupported API call');

function panic($message)
{
	http_response_code(503);
	header('Content-Type: application/json; charset=UTF-8');
	die(json_encode(array('error' => $message)));
}

function success_json($data)
{
	header('Content-Type: application/json; charset=UTF-8');
	die(json_encode($data));
}

function success_text($data)
{
	header('Content-Type: text/plain; charset=UTF-8');
	die($data);
}
