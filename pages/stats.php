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

if ($settings->getUseDatabaseStats() && isset($access['userid']))
{
	$dbh = $settings->getDatabase();
	$q = $dbh->prepare('SELECT domain FROM stat WHERE userid = :userid GROUP BY domain;');
	$q->execute(array(':userid' => $access['userid']));
	while ($d = $q->fetch(PDO::FETCH_ASSOC))
		$domains[] = $d['domain'];
}

$smarty->assign('domains', $domains);
$smarty->display('stats.tpl');
