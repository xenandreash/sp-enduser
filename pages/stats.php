<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayStats()) die("The setting display-stats isn't enabled");

/* TODO
 * Use SOAP "future" instead for parallell config call
 * Support "outbound" aka more listeners
 */

if (isset($_GET['ajax-rrd'])) {
	if (!Session::Get()->checkAccessDomain($_GET['ajax-rrd']))
		die('access denied');
	$listener = 'mailserver:1';
	$listener = str_replace(':', '-', $listener);
	$domain = $_GET['ajax-rrd'];
	$data = array();
	foreach ($settings->getNodes() as $node) {
		try {
			$data[] = base64_encode($node->soap()->graphFile(array('name' => 'mail-stat-'.$listener.'-'.$domain))->result);
		} catch (SoapFault $e) {
		}
	}
	header('Content-type: application/json');
	die(json_encode($data));
}

if (isset($_GET['ajax-pie'])) {
	if (!Session::Get()->checkAccessDomain($_GET['ajax-pie']))
		die('access denied');
	$listener = 'mailserver:1';
	$keyname = 'mail:action:';
	$stats = array();
	$since = null;
	foreach ($settings->getNodes() as $node) {
		try {
			$ss = $node->soap()->statList(array('key1' => $keyname.'%', 'key2' => $inbound, 'key3' => $_GET['ajax-pie'], 'offset' => 0, 'limit' => 10))->result->item;
			if (!is_array($ss))
				continue;
			foreach ($ss as $s) {
				$k = str_replace($keyname, '', $s->key1);
				if (!$stats[$k]) $stats[$k] = 0;
				$stats[$k] += $s->count;
				if ($since === null || $s->created < $since) $since = $s->created;
			}
		} catch (SoapFault $e) {
		}
	}
	$flot = array();
	foreach ($stats as $k => $v) {
		$p = array('label' => $k, 'data' => $v);
		$color = null;
		if ($k == 'delete') $color = '#666';
		if ($k == 'deliver') $color = '#7d6';
		if ($k == 'allow') $color = '#9cf';
		if ($k == 'reject') $color = '#d44';
		if ($k == 'block') $color = '#622';
		if ($k == 'defer') $color = '#ed4';
		if ($k == 'quarantine') $color = '#e96';
		if ($color) $p['color'] = $color;
		$flot[] = $p;
	}
	header('Content-type: application/json');
	die(json_encode(array('since' => $since, 'flot' => $flot)));
}

$title = 'Statistics';
$javascript[] = 'static/js/javascriptrrd.js';
$javascript[] = 'static/js/jquery.flot.min.js';
$javascript[] = 'static/js/jquery.flot.pie.min.js';
$javascript[] = 'static/js/jquery.flot.resize.min.js';
$javascript[] = 'static/js/jquery.flot.time.min.js';
$javascript[] = 'static/js/jquery.flot.selection.min.js';
$javascript[] = 'static/js/jquery.flot.stack.min.js';
$javascript[] = 'static/js/stats.js';

require_once BASE.'/inc/smarty.php';

$smarty->assign('domains', Session::Get()->getAccess('domain'));

$smarty->display('stats.tpl');
