<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$logs = $settings->getDisplayTextlog();
if (!$logs) die('logs disabled');

if (isset($_GET['ajax']))
{
	// poll, has it's own permission system
	if (!@in_array($_GET['cmd_id'], $_SESSION['logs_id'], true))
		die(json_encode(array("invalid session\n")));

	$client = $settings->getNode($_GET['cmd_node'])->soap();
	if ($_GET['action'] == 'poll')
	{
		$result = $client->commandPoll(array('commandid' => $_GET['cmd_id']));
		if ($result->result->item)
			die(json_encode(array_map(function ($str) {
					return str_replace("\r", "", $str);
				}, $result->result->item)));
		die(json_encode(array()));
	}
	if ($_GET['action'] == 'stop')
	{
		$result = $client->commandStop(array('commandid' => $_GET['cmd_id']));
		die(json_encode(true));
	}
}

$id = preg_replace('/[^0-9]/', '', $_GET['id']);

if ($_GET['type'] == 'log')
{
	// Fetch data from local SQL log
	$dbBackend = new DatabaseBackend($settings->getDatabase());
	$mail = $dbBackend->getMail($id);
	if (!$mail) die('Invalid mail');

	// Resolv SOAP node
	$node = $settings->getNodeBySerial($mail->serialno);
	if (!$node) die('Unable to find SOAP node');
	$args = array('searchlog', '-a', $mail->msgts, '--', $mail->msgid);
} else {
	$node = $settings->getNode($_GET['node']);
	if (!$node) die('Invalid mail');

	$nodeBackend = new NodeBackend($node);
	if ($_GET['type'] == 'history')
		$mail = $nodeBackend->getMailInHistory('historyid='.$id, $errors);
	else
		$mail = $nodeBackend->getMailInQueue('queueid='.$id, $errors);
	if (!$mail || $errors) die('Invalid mail');
	$args = array('searchlog', '-a', $mail->msgts, '--', $mail->msgid.':'.$id);
}

$client = $node->soap();
try {
	$cmd_id = $client->commandRun(array('argv' => $args));
} catch (Exception $e) {
	die('unable to start log');
}
$_SESSION['logs_id'][] = $cmd_id->result;

$title = 'Text log';
$show_back = true;
$javascript[] = 'static/js/log.js';
require_once BASE.'/partials/header.php';
?>
	<nav class="navbar navbar-toolbar navbar-static-top hidden-xs">
		<div class="container-fluid">
			<div class="navbar-header">
				<a id="history_back" class="navbar-brand" href="javascript:history.go(-1);">&larr;&nbsp;Back</a>
			</div>
		</div>
	</nav>
	<div class="container-fluid">
		<pre id="log"><span class="text-info" id="loading">Loading<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span></span></pre>
	</div>
	<script>
		cmd_id = <?php echo json_encode($cmd_id->result); ?>;
		cmd_node = <?php echo json_encode($node->getId()); ?>;
	</script>
<?php require_once BASE.'/partials/footer.php'; ?>
