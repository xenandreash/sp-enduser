<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayRateLimits()) die("The setting display-ratelimits isn't enabled");
if (!Session::Get()->checkAccessAll()) die('Insufficient permissions');

$ratelimits = $settings->getRateLimits();
$source = ($settings->getUseDatabaseLog() ? 'log' : 'all');
$search = $_GET['search'];

$views = array();
foreach ($ratelimits as $i => $rate) {
	$views[] = array(
		'name' => $rate['name'] ? $rate['name'] : $rate['ns'],
		'ns' => $rate['ns'],
		'id' => $i,
		'paging' => 0,
	);
}

$javascript[] = 'static/js/rates.js';
require_once BASE.'/inc/smarty.php';

if ($search) $smarty->assign('search', $search);
$smarty->assign('views', $views);
$smarty->assign('source', $source);
$smarty->display('rates.tpl');
