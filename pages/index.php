<?php
if (!defined('SP_ENDUSER')) die('File not included');

if (isset($_POST['delete']) || isset($_POST['bounce']) || isset($_POST['retry']) || isset($_POST['duplicate'])) {
	$actions = array();
	foreach ($_POST as $k => $v)
	{
		if (!preg_match('/^multiselect-(\d+)$/', $k, $m))
			continue;

		$node = $settings->getNode($v);
		if (!$node) die('Invalid mail');

		$nodeBackend = new NodeBackend($node);
		$errors = array();
		$mail = $nodeBackend->getMailInQueue('queueid='.$m[1], $errors);
		if ($errors)
			continue;
		if (!$mail)
			$mail = $nodeBackend->getMailInArchive('queueid='.$m[1], $errors);
		if (!$mail || $errors)
			continue;

		$actions[$node->getId()][] = $mail->id;
	}
	if (empty($actions))
		die('Invalid mail');
	foreach ($actions as $soapid => $list)
	{
		try {
			$id = implode(',', $list);
			if (isset($_POST['bounce']))
				$settings->getNode($soapid)->soap()->mailQueueBounce(array('id' => $id));
			if (isset($_POST['delete']))
				$settings->getNode($soapid)->soap()->mailQueueDelete(array('id' => $id));
			if (isset($_POST['retry']))
				$settings->getNode($soapid)->soap()->mailQueueRetry(array('id' => $id));
			if (isset($_POST['duplicate']))
				$settings->getNode($soapid)->soap()->mailQueueRetry(array('id' => $id, 'duplicate' => true));
		} catch (SoapFault $f) {
			die($f->getMessage());
		}
	}
	header('Location: '.$_SERVER['REQUEST_URI']);
	die();
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

function get_preview_link($m)
{
	return '?'.http_build_query(array(
		'page' => 'preview',
		'node' => $m['id'],
		'id' => $m['data']->id,
		'msgid' => $m['data']->msgid,
		'msgactionid' => $m['data']->msgactionid,
		'type' => $m['type']
	));
}

// Backends
$dbBackend = new DatabaseBackend($settings->getDatabase());
$nodeBackend = new NodeBackend($settings->getNodes());

// Default values
$search = isset($_GET['search']) ? hql_transform($_GET['search']) : '';
$size = isset($_GET['size']) ? intval($_GET['size']) : 50;
$size = $size > 5000 ? 5000 : $size;
$source = isset($_GET['source']) ? $_GET['source'] : $settings->getDefaultSource();

// Select box arrays
$pagesize = array(50, 100, 500, 1000, 5000);
$sources = array();
if ($settings->getUseDatabaseLog())
	$sources += array('log' => 'Log');
if ($settings->getDisplayHistory())
	$sources += array('history' => 'History');
if ($nodeBackend->isValid() && $settings->getDisplayQueue())
	$sources += array('queue' => 'Queue');
if ($nodeBackend->isValid() && $settings->getDisplayQuarantine())
	$sources += array('quarantine' => 'Quarantine');
if ($nodeBackend->isValid() && $settings->getDisplayArchive())
	$sources += array('archive' => 'Archive');
if ($nodeBackend->isValid() && $settings->getDisplayAll())
	$sources += array('all' => 'All');

// Make sure a real, not disabled source is selected
if (!array_key_exists($source, $sources))
	die("Invalid source");

$queries = array();
if ($source == 'queue')
	$queries[] = 'action=DELIVER';
if ($source == 'quarantine' || $source == 'archive')
	$queries[] = 'action=QUARANTINE';
if ($search != '')
	$queries[] = $search;
$real_search = implode(' && ', $queries);

// Initial settings
$timesort = array();
$prev_button = ' disabled';
$next_button = ' disabled';
$param = array();
$errors = array();

// Override offset with GET
$totaloffset = 0;
foreach ($_GET as $k => $v) {
	if (!preg_match('/^(history|queue|log)offset(\d+)$/', $k, $m))
		continue;
	if ($v < 1)
		continue;
	$param[$m[1]][$m[2]]['offset'] = $v;
	$totaloffset += $v;
	$prev_button = '';
}

$cols = 7;

if ($source == 'log') {
	$results = $dbBackend->loadMailHistory($real_search, $size, $param['log'], $errors);
	$timesort = merge_2d($timesort, $results);
}
if (($source == 'history' || $source == 'all') && $settings->getDisplayHistory()) {
	$results = $nodeBackend->loadMailHistory($real_search, $size, $param['history'], $errors);
	$timesort = merge_2d($timesort, $results);
}

$hasMailWithActions = false;
if ($source == 'all') {
	$results = $nodeBackend->loadMailQueue($real_search, $size, $param['queue'], $errors);
	$hasMailWithActions = !empty($results);
	$timesort = merge_2d($timesort, $results);
	$results = $nodeBackend->loadMailArchive($real_search, $size, $param['queue'], $errors);
	if (!$hasMailWithActions) $hasMailWithActions = !empty($results);
	$timesort = merge_2d($timesort, $results);
}

if ($source == 'queue' || $source == 'quarantine') {
	$results = $nodeBackend->loadMailQueue($real_search, $size, $param['queue'], $errors);
	$hasMailWithActions = !empty($results);
	$timesort = merge_2d($timesort, $results);
}

if ($source == 'archive') {
	$results = $nodeBackend->loadMailArchive($real_search, $size, $param['queue'], $errors);
	$hasMailWithActions = !empty($results);
	$timesort = merge_2d($timesort, $results);
}

krsort($timesort);
ksort($errors);

$c = 0;
foreach ($timesort as $t)
	$c += count($t);
if ($c > $size)
	$next_button = ''; // enable "next" page button

$javascript[] = 'static/js/index.js';
require_once BASE.'/inc/smarty.php';

$smarty->assign('source_name', $sources[$source]);
$smarty->assign('source', $source);
$smarty->assign('sources', array_keys($sources));
$smarty->assign('search', $search);
$smarty->assign('size', $size);
$smarty->assign('errors', $errors);
$smarty->assign('mailwithaction', $hasMailWithActions);
$smarty->assign('mailhasmultipleaddresses', count(Session::Get()->getAccess('mail')) != 1 or count(Session::Get()->getAccess('domain')) > 0);
if (count(Session::Get()->getAccess('domain')) > 0 && count(Session::Get()->getAccess('domain')) < 30) $smarty->assign('search_domains', Session::Get()->getAccess('domain'));
if ($settings->getDisplayScores()) $smarty->assign('feature_scores', true);

$mails = array();

$i = 1;
foreach ($timesort as $t) {
	if ($i > $size) { break; }
	foreach ($t as $m) {
		if ($i > $size) { break; }
		$i++;
		if ($m['type'] == 'archive') {
			$m['data']->msgaction = 'ARCHIVE';
		}
		$param[$m['type']][$m['id']]['offset']++;
		$preview = get_preview_link($m);
		$td = $tr = '';
		if ($m['type'] == 'queue' || $m['type'] == 'archive')
			$td = 'data-href="'.htmlspecialchars($preview).'"';
		else
			$tr = 'data-href="'.htmlspecialchars($preview).'"';
		if ($m['type'] == 'queue' && $m['data']->msgaction == 'DELIVER') $m['data']->msgaction = 'QUEUE';

		$mail = array();

		$mail['mail'] = $m['data'];
		$mail['type'] = $m['type'];
		$mail['node'] = $m['id'];

		if ($m['data']->msgts0 + (3600 * 24) > time())
			$mail['today'] = true;
		$mail['time'] = $m['data']->msgts0 - $_SESSION['timezone'] * 60;

		$mail['preview'] = $preview;
		$mail['tr'] = $tr;
		$mail['td'] = $td;
		$mail['action_icon'] = $action_icons[$m['data']->msgaction];
		$mail['action_color'] = $action_colors[$m['data']->msgaction];
		$mail['description'] = $m['data']->msgerror ?: $m['data']->msgdescription;
		if ($settings->getDisplayScores()) {
			$printscores = array();
			$scores = history_parse_scores($m['data']);
			foreach ($scores as $engine => $s) {
				if ($engine == 'rpd' && $s['score'] != 'Unknown')
					$printscores[] = strtolower($s['score']);
				if ($engine == 'kav' && $s['score'] != 'Ok')
					$printscores[] = 'virus';
				if ($engine == 'clam' && $s['score'] != 'Ok')
					$printscores[] = 'virus';
				if ($engine == 'rpdav' && $s['score'] != 'Ok')
					$printscores[] = 'virus';
				if ($engine == 'sa')
					$printscores[] = $s['score'];
			}
			$mail['scores'] = implode(', ', array_unique($printscores));
		}
		$mails[] = $mail;
	}
}

$paging = array(); 
foreach ($param as $type => $nodes) {
	foreach ($nodes as $node => $args) {
		if ($args['offset'] > 0) {
			$paging[$type.'offset'.$node] = $args['offset'];
		}
	}
}

$smarty->assign('mails', $mails);
$smarty->assign('prev_button', $prev_button);
$smarty->assign('next_button', $next_button);
$smarty->assign('pagesizes', $pagesize);
$smarty->assign('paging', $paging);
$smarty->display('index.tpl');
