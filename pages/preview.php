<?php
if (!defined('SP_ENDUSER')) die('File not included');

$id = preg_replace('/[^0-9]/', '', $_GET['id']);
$type = $_GET['type'];
$node = null;

if ($type == 'log') {
	// Log = database backend
	$dbBackend = new DatabaseBackend($settings->getDatabase());
	$mail = $dbBackend->getMail($id);
	if (!$mail) die('Invalid mail');

	// get mail flow from db (not available over soap)
	$msgactionlog = isset($mail->msgaction_log) ? json_decode($mail->msgaction_log) : [];
	foreach($msgactionlog as $i) {
		if (isset($i->ts0))
			$i->ts0 -= $_SESSION['timezone'] * 60;
	}

	// If in queue, quarantine or if text-log is enabled, get it from SOAP if possible (so that we get more info)
	if ($mail->msgaction == 'QUEUE' || $mail->msgaction == 'QUARANTINE' || $settings->getDisplayTextlog() == true)
		$node = $settings->getNodeBySerial($mail->serialno);
	if ($node !== null) {
		$client = $node->soap();
		$nodeBackend = new NodeBackend($node);
		$node_mail = $nodeBackend->getMailInQueueOrHistory('messageid='.$mail->msgid.' actionid='.$mail->msgactionid, $errors, $node_type);
		if ($node_mail && !$errors) {
			$mail = $node_mail;
			$type = $node_type;
		}
	}
} else {
	// Fetch data from SOAP
	$node = $settings->getNode($_GET['node']);
	if (!$node) die('Invalid mail');

	$client = $node->soap();
	$nodeBackend = new NodeBackend($node);
	if ($_GET['type'] == 'history')
		$mail = $nodeBackend->getMailInHistory('historyid='.$id, $errors);
	else if ($_GET['type'] == 'archive')
		$mail = $nodeBackend->getMailInArchive('queueid='.$id, $errors);
	else
		$mail = $nodeBackend->getMailInQueueOrHistory('queueid='.$id, $errors, $type);
	if (!$mail || $errors)
		die('Invalid mail');

	// Action are only available for mail in queue and archive
	if (isset($_POST['action']) && ($type == 'queue' || $type == 'archive')) {
		try {
			if ($_POST['action'] == 'bounce')
				$client->mailQueueBounce(array('id' => $mail->id));
			else if ($_POST['action'] == 'delete')
				$client->mailQueueDelete(array('id' => $mail->id));
			else if ($_POST['action'] == 'retry')
				$client->mailQueueRetry(array('id' => $mail->id));
			else if ($_POST['action'] == 'duplicate')
				$client->mailQueueRetry(array('id' => $mail->id, 'duplicate' => true));
		} catch (SoapFault $f) {
			die($f->getMessage());
		}
		if ($_POST['action'] == 'duplicate')
			header('Location: ?page=index');
		else
			header('Location: ?page=preview&type=queue&id='.$mail->id.'&node='.$node->getId());
		die();
	}
}

$action_colors = array(
	'DELIVER' => '#8c1',
	'QUEUE' => '#1ad',
	'QUARANTINE' => '#f70',
	'ARCHIVE' => '#b8b8b8',
	'REJECT' => '#ba0f4b',
	'DELETE' => '#333',
	'BOUNCE' => '#333',
	'ERROR' => '#333',
	'DEFER' => '#b5b',
);

$action_icons = array(
	'DELIVER' => 'check',
	'QUEUE' => 'exchange',
	'QUARANTINE' => 'inbox',
	'ARCHIVE' => 'inbox',
	'REJECT' => 'ban',
	'DELETE' => 'trash-o',
	'BOUNCE' => 'mail-reply',
	'ERROR' => 'exclamation',
	'DEFER' => 'clock-o',
);

if ($type == 'queue' && $mail->msgaction == 'DELIVER') $mail->msgaction = 'QUEUE';
if ($type == 'archive') $mail->msgaction = 'ARCHIVE';
// Prepare data
if ($type == 'queue' || $type == 'archive') {
	$uniq = uniqid();
	$command = array('previewmessage');
	if ($_GET['preview'] == 'text')
		$command[] = '-t';
	$command[] = $mail->msgpath;
	$command[] = $uniq;
	if ($mail->msgdeltapath)
		$command[] = $mail->msgdeltapath;
	$data = soap_exec($command, $client);
	$data = str_replace("\r\n", "\n", $data);
	$data = explode("$uniq|", $data);
	$result = array();
	$result['HEADERS'] = trim($data[0]);
	for ($i = 1; $i < count($data); ++$i) {
		list($format, $content) = explode("\n", $data[$i], 2);
		$result[$format] = trim($content);
	}
	if (isset($result['TEXT']) || isset($result['HTML'])) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$config->set('URI.Disable', true);
		$purifier = new HTMLPurifier($config);
		$header = $result['HEADERS'];
		$headerdelta = $result['HEADERS-DELTA'];
		require_once 'inc/utils/extension.inc.php';
		$attachments = $result['ATTACHMENTS'] != "" ? array_map(function ($k) {
				$l = explode('|', $k);
				return array('type' => $l[0], 'size' => $l[1], 'name' => $l[2], 'icon' => extension_icon($l[2]));
				}, explode("\n", $result['ATTACHMENTS'])) : array();

		$body = isset($result['TEXT']) ? htmlspecialchars($result['TEXT']) : $result['HTML'];
		$body = trim($purifier->purify($body));
		$encode = isset($result['TEXT']) ? 'TEXT' : 'HTML';
	} else {
		$encode = 'ERROR';
		$body = '<p class="text-center text-muted">Preview unavailable</p>';
	}
}

// prepare black and whitelist
if ($settings->getDisplayBWlist() && !empty($mail->msgfrom) && $mail->msgto != $mail->msgfrom) {

	$bwlist_settings = Array('whitelist' => Array('show' => false, 'enabled' => true), 'blacklist' => Array('show' => false, 'enabled' => true));

	if ($settings->getDisplayListener()[$mail->msglistener] != 'Outbound' && $settings->getDisplayTransport()[$mail->msgtransport] != 'Internet') {
		if (in_array($mail->msgaction, Array('QUARANTINE', 'REJECT')))
			$bwlist_settings['whitelist']['show'] = true;
		if (in_array($mail->msgaction, Array('DELIVER')))
			$bwlist_settings['blacklist']['show'] = true;
	}

	if ($bwlist_settings['whitelist']['show'] == true || $bwlist_settings['blacklist']['show'] == true) {
		$dbh = $settings->getDatabase();
		$statement = $dbh->prepare('SELECT * FROM bwlist WHERE access = :recipient AND value = :sender;');
		$statement->execute(array(':recipient' => $mail->msgto, ':sender' => $mail->msgfrom));
		while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
			if ($row['type'] == 'whitelist')
				$bwlist_settings['whitelist']['enabled'] = false;
			if ($row['type'] == 'blacklist')
				$bwlist_settings['blacklist']['enabled'] = false;
		}
	}
}

// geoip
if ($settings->getGeoIP()) {
	try {
		$reader = new GeoIp2\Database\Reader($settings->getGeoIPDatabase());
		$ipinfo = $reader->country($mail->msgfromserver);
		$geoip['name'] = $ipinfo->country->name;
		$geoip['isocode'] = strtolower($ipinfo->country->isoCode);
	} catch(Exception $e) {}
}

$javascript[] = 'static/js/preview.js';
$javascript[] = 'static/js/diff_match_patch.js';
$javascript[] = 'static/js/diff.js';

require_once BASE.'/inc/smarty.php';

if ($settings->getDisplayBWlist()) $smarty->assign('bwlist_settings', $bwlist_settings);
if ($settings->getDisplayTextlog() && $node && $mail->msgid) {
	$smarty->assign('support_log', true);
	$smarty->assign('support_log_query', urlencode($_SERVER['QUERY_STRING']));
}
if ($settings->getDisplayScores()) $smarty->assign('scores', history_parse_scores($mail));
if ($node) $smarty->assign('node', $node->getId());
if ($attachments) $smarty->assign('attachments', $attachments);
if (isset($body)) $smarty->assign('body', $body);
if ($encode) $smarty->assign('encode', $encode);
if ($_GET['preview'] == 'text') $smarty->assign('show_text', true);

$smarty->assign('use_iframe', $_SESSION['useiframe']);

$f = $_GET;
$f['preview'] = 'text';
$smarty->assign('show_text_link', '?'.http_build_query($f));

$f = $_GET;
unset($f['preview']);
$smarty->assign('show_html_link', '?'.http_build_query($f));

$smarty->assign('mail', $mail);
$smarty->assign('type', $type);
$smarty->assign('msg_mailflow', isset($msgactionlog) ? $msgactionlog : []);
if (isset($geoip)) $smarty->assign('geoip', $geoip);
if ($header) {
	$smarty->assign('header', json_encode($header));
	$smarty->assign('headerdelta', json_encode($headerdelta));
}
$smarty->assign('referer', isset($_POST['referer']) ? $_POST['referer'] : $_SERVER['HTTP_REFERER']);
$smarty->assign('action_color', $action_colors[$mail->msgaction]);
$smarty->assign('action_icon', $action_icons[$mail->msgaction]);
$smarty->assign('action_colors', $action_colors);
$smarty->assign('action_icons', $action_icons);
$transports = $settings->getDisplayTransport();
if (isset($transports[$mail->msgtransport])) $smarty->assign('transport', $transports[$mail->msgtransport]);
$listeners = $settings->getDisplayListener();
if (isset($listeners[$mail->msglistener])) $smarty->assign('listener', $listeners[$mail->msglistener]);
$smarty->assign('time', $mail->msgts0 - $_SESSION['timezone'] * 60, '%Y-%m-%d %H:%M:%S');
if (count($settings->getNodes())) $smarty->assign('has_nodes', true);
$smarty->assign('disabled_features', Session::Get()->getDisabledFeatures());

$smarty->display('preview.tpl');
