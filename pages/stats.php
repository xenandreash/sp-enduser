<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayStats()) die("The setting display-stats isn't enabled");

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

$domains = Session::Get()->getAccess('domain');
$sorted_domains = array('inbound' => array(), 'outbound' => array());

if ($settings->getUseDatabaseStats()) {
	$dbh = $settings->getDatabase();
	if (isset($access['userid'])) {
		$q = $dbh->prepare('SELECT DISTINCT direction, domain FROM stat WHERE userid = :userid GROUP BY direction, domain;');
		$q->execute(array(':userid' => $access['userid']));
		while ($d = $q->fetch(PDO::FETCH_ASSOC)) {
			$sorted_domains[($d['direction'] == 'outbound') ? 'outbound' : 'inbound'][] = $d['domain'];
		}
	} else {
		foreach ($domains as $domain) {
			$q = $dbh->prepare('SELECT DISTINCT direction, domain FROM stat WHERE domain = :domain;');
			$q->execute(array(':domain' => $domain));
			while ($d = $q->fetch(PDO::FETCH_ASSOC)) {
				$sorted_domains[($d['direction'] == 'outbound') ? 'outbound' : 'inbound'][] = $d['domain'];
			}
		}
	}
} else {
	foreach ($domains as $domain) {
		$sorted_domains['inbound'][] = $domain;
	}
}

$smarty->assign('domains', $sorted_domains);
$smarty->display('stats.tpl');
