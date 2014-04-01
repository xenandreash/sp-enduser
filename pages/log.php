<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once('inc/core.php');
require_once('inc/utils.php');

$node = intval($_GET['node']);
$queueid = intval($_GET['queueid']);
$client = soap_client($node);

// Poll, has it's own permission system
if (isset($_GET['ajax'])) {
	if (!@in_array($_GET['cmd_id'], $_SESSION['logs_id']))
		die(json_encode(array("invalid session\n")));
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

// Access permission
$query['filter'] = build_query_restrict().' && messageid='.$_GET['id'].' queueid='.$queueid;
$query['offset'] = 0;
$query['limit'] = 1;
if ($_GET['type'] == 'history')
	$queue = $client->mailHistory($query);
else
	$queue = $client->mailQueue($query);
if (count($queue->result->item) != 1)
	die('Invalid queueid');

$logs = isset($settings['display-textlog']) ? $settings['display-textlog'] : false;
if (!$logs) die('logs disabled');

$mail = $queue->result->item[0];
$args = array('searchlog', $mail->msgid.':'.$queueid, '-'.$mail->msgts);
try {
	$cmd_id = $client->commandRun(array('argv' => $args));
} catch (Exception $e) {
	die('unable to start log');
}
$_SESSION['logs_id'][] = $cmd_id->result;
// Prepare data

$title = 'Text log';
$javascript[] = 'static/log.js';
require_once('inc/header.php');
?>
			<form>
				<div class="item">
					<div class="button back" onclick="history.back()">Back</div>
				</div>
			</form>
		</div>
		<div class="fullpage"><input id="cmd_id" value="<?php echo $cmd_id->result ?>" style="display:none">
<pre id="log"></pre>
	</div>
<?php require_once('inc/footer.php'); ?>
