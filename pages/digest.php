<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once('inc/core.php');
require_once('inc/utils.php');

$settings = settings();
if (!isset($settings['digest']['secret']))
	die('No digest secret');

$node = intval($_GET['node']);
$queueid = intval($_GET['queueid']);
$time = intval($_GET['time']);
$sign = $_GET['sign'];
$client = soap_client($node);

// Check time, allow 1 week of links
if ($time + (3600*24*7) < time()) die('Link has expired (valid 1 week)');

// Get message ID, part of signing hash
$query['filter'] = 'queueid='.$queueid;
$query['offset'] = 0;
$query['limit'] = 1;
$queue = $client->mailQueue($query);
if (count($queue->result->item) == 1)
	$msgid = $queue->result->item[0]->msgid;

// Validate signature
$message = $node.$queueid.$time.$msgid;
$hash = hash_hmac('sha256', $message, $settings['digest']['secret']);
if ($hash !== $sign) die('Failed to release message');

// Perform action and close window
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
test
