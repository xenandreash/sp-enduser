<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

// Poll, has it's own permission system
if (isset($_GET['ajax'])) {
	if (!@in_array($_GET['cmd_id'], $_SESSION['logs_id']))
		die(json_encode(array("invalid session\n")));
	$client = soap_client(intval($_GET['cmd_node']));
	if ($_GET['action'] == 'poll') {
		$result = $client->commandPoll(array('commandid' => $_GET['cmd_id']));
		if ($result->result->item)
			echo json_encode(array_map(function ($str) {
					return str_replace("\r", "", $str);
				}, $result->result->item));
		else
			echo json_encode(array());
		die();
	}
	if ($_GET['action'] == 'stop') {
		$result = $client->commandStop(array('commandid' => $_GET['cmd_id']));
		die(json_encode(true));
	}
}

if ($_GET['type'] == 'log') {
	// SQL access permission
	$mail = restrict_local_mail(intval($_GET['id']));
	// Resolv SOAP node
	$node = null;
	foreach ($settings->getNodes() as $n => $tmpnode)
	{
		try {
			if($tmpnode->getSerial(true) == $mail->serialno)
				$node = $n;
		} catch (SoapFault $e) {
			
		}
	}
	if ($node === null) die('Unable to find SOAP node');
	$args = array('searchlog', $mail->msgid, '-'.$mail->msgts);
} else {
	// SOAP access permission
	$node = intval($_GET['node']);
	$id = intval($_GET['id']);
	$mail = restrict_mail($_GET['type'], $node, $id);
	$args = array('searchlog', $mail->msgid.':'.$id, '-'.$mail->msgts);
}

$logs = $settings->getDisplayTextlog();
if (!$logs) die('logs disabled');

$client = soap_client($node);
try {
	$cmd_id = $client->commandRun(array('argv' => $args));
} catch (Exception $e) {
	die('unable to start log');
}
$_SESSION['logs_id'][] = $cmd_id->result;
// Prepare data

$title = 'Text log';
$show_back = true;
$javascript[] = 'static/js/log.js';
require_once BASE.'/partials/header.php';
?>
	<nav class="navbar navbar-toolbar navbar-static-top hidden-xs">
		<div class="container-fluid">
			<div class="navbar-header">
				<a class="navbar-brand" href="javascript:history.go(-1);">&larr;&nbsp;Back</a>
			</div>
		</div>
	</nav>
	<div class="container-fluid">
		<pre id="log"></pre>
	</div>
	<script>
		cmd_id = <?php echo json_encode($cmd_id->result); ?>;
		cmd_node = <?php echo json_encode($node); ?>;
	</script>
<?php require_once BASE.'/partials/footer.php'; ?>
