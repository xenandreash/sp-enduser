<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayStats()) die("The setting display-stats isn't enabled");

$dbh = $settings->getDatabase();
$access = Session::Get()->getAccess();

function check_access($domain) {
	global $dbh, $access;
	$access = Session::Get()->getAccess();
	$q = $dbh->prepare('SELECT domain FROM stat WHERE domain = :domain AND user_id = :userid LIMIT 1;');
	$q->execute([':userid' => $access['userid'], ':domain' => $domain]);
	$rows = $q->fetch(PDO::FETCH_ASSOC);
	if (is_array($rows) && count($rows) == 1)
		return true;
	if (Session::Get()->checkAccessDomain($domain))
		return true;
	return false;
}

if ($_GET['ajax-rrd']) {
	if (!check_access($_GET['ajax-rrd']))
		die('access denied');
	$path = '../rrd/';
	echo json_encode(base64_encode(file_get_contents($path.$_GET['ajax-rrd'].'.rrd')));
	die();
}
if (isset($_GET['ajax-pie'])) {
	global $dbh, $access;
	if (!check_access($_GET['ajax-pie']))
		die('access denied');
	$stats = array();
	$since = null;
	// total pie
	if (!$_GET['time']) {
		$q = $dbh->prepare('SELECT SUM(reject) AS reject, SUM(deliver) AS deliver FROM stat WHERE user_id = :userid AND domain = :domain GROUP BY user_id,domain;');
		$q->execute([':userid' => $access['userid'], ':domain' => $_GET['ajax-pie']]);
	} else {
		$date = explode('-', $_GET['time']);
		$q = $dbh->prepare('SELECT reject,deliver FROM stat WHERE user_id = :userid AND domain = :domain AND year = :year AND month = :month;');
		$q->execute([':userid' => $access['userid'], ':domain' => $_GET['ajax-pie'], ':year' => $date[0], ':month' => $date[1]]);
	}
	$row = $q->fetch(PDO::FETCH_ASSOC);
	$flot = [];
	$flot[] = ['label' => 'deliver', 'data' => $row['deliver'], 'color' => '#7d6'];
	$flot[] = ['label' => 'reject', 'data' => $row['reject'], 'color' => '#d44'];
	header('Content-type: application/json');
	die(json_encode($flot));
}
if (isset($_GET['ajax-since'])) {
	global $dbh, $access;
	if (!check_access($_GET['ajax-since']))
		die('access denied');
	$q = $dbh->prepare('SELECT year,month FROM stat WHERE user_id = :userid AND domain = :domain;');
	$q->execute([':userid' => $access['userid'], ':domain' => $_GET['ajax-since']]);
	die(json_encode($q->fetchAll(PDO::FETCH_ASSOC)));
}
$title = 'Statistics';
$javascript[] = 'static/js/javascriptrrd.js';
$javascript[] = 'static/js/jquery.flot.min.js';
$javascript[] = 'static/js/jquery.flot.pie.min.js';
$javascript[] = 'static/js/jquery.flot.resize.min.js';
$javascript[] = 'static/js/jquery.flot.time.min.js';
$javascript[] = 'static/js/jquery.flot.selection.min.js';
$javascript[] = 'static/js/jquery.flot.stack.min.js';
$javascript[] = 'static/js/stats2.js';

require_once BASE.'/inc/smarty.php';

$domains = Session::Get()->getAccess('domain');

if (isset($access['userid'])) {
	$q = $dbh->prepare('SELECT domain FROM stat WHERE user_id = :userid GROUP BY domain;');
	$q->execute([':userid' => $access['userid']]);
	while ($d = $q->fetch(PDO::FETCH_ASSOC))
		$domains[] = $d['domain'];
}

$smarty->assign('domains', $domains);

$smarty->display('stats2.tpl');
