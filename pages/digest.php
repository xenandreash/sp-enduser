<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDigestSecret()) die('No digest secret');

$node = intval($_GET['node']);
$queueid = preg_replace('/[^0-9]/', '', $_GET['queueid']);
$time = intval($_GET['time']);
$sign = $_GET['sign'];
$client = soap_client($node);

// Check time, allow 1 week of links
if ($time + (3600*24*7) < time())
	die('Link has expired (valid 1 week)');

// Get message ID, part of signing hash
$query['filter'] = 'queueid='.$queueid;
$query['offset'] = 0;
$query['limit'] = 1;
$queue = $client->mailQueue($query);
if (count($queue->result->item) == 1) {
	$msgid = $queue->result->item[0]->msgid;
	$msgfrom = $queue->result->item[0]->msgfrom;
	$msgto = $queue->result->item[0]->msgto;
}

// Validate signature
$message = $node.$queueid.$time.$msgid;
$hash = hash_hmac('sha256', $message, $settings->getDigestSecret());
if ($hash !== $sign) die('Failed to release message');

// preview email
if ($_GET['preview'] == 'true') {
	if (!Session::Get()->isAuthenticated()) {
		session_destroy();
		header('Location: ?page=login&query='.urlencode($_SERVER['QUERY_STRING']));
	} else {
		$url = '?'.http_build_query([
			'page' => 'preview',
			'node' => $node,
			'id' => $queue->result->item[0]->id,
			'msgid' => $queue->result->item[0]->msgid,
			'msgactionid' => $queue->result->item[0]->msgactionid,
			'type' => 'queue'
		]);

		header('Location: '.$url);
	}
	die();
}

// Perform action and close window
if ($_GET['whitelist'] == 'true') {
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("INSERT INTO bwlist (access, type, value) VALUES(:access, :type, :value);");
	$statement->execute(array(':access' => strtolower($msgto), ':type' => 'whitelist', ':value' => strtolower($msgfrom)));
}
$client->mailQueueRetry(array('id' => $queueid));
?>
<html>
<head>
	<title>Message successfully released</title>
	<script>
		window.close();
	</script>
</head>
<body>
	The message was successfully released.
</body>
</html>
