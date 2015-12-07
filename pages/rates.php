<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayRateLimits()) die("The setting display-ratelimits isn't enabled");
if (count(Session::Get()->getAccess()) != 0) die('Insufficient permissions');

$ratelimits = $settings->getRateLimits();
$source = ($settings->getUseDatabaseLog() ? 'log' : 'all');
$search = $_GET['search'];

$actions = array(
	'QUARANTINE' => array('color' => '#f70', 'icon' => 'inbox'),
	'REJECT' => array('color' => '#ba0f4b', 'icon' => 'ban'),
	'DELETE' => array('color' => '#333', 'icon' => 'trash-o'),
	'DEFER' => array('color' => '#b5b', 'icon' => 'clock-o'),
);

// Comparison function for usort
function cmp($a, $b) {
    return $b['count'] - $a['count'];
}

$nodeBackend = new NodeBackend($settings->getNode(0));

$errors = array();
$result2 = array();

foreach ($ratelimits as $ns => $param) {
	if ($search) $result = $nodeBackend->getRate(['ns' => $ns, 'entry' => $search], $errors)[0];
	else $result = $nodeBackend->getRate(['ns' => $ns, 'count' => $param['count_min']], $errors)[0];

	$items = array();

	if (count($result->result->item) > 0) {
		foreach ($result->result->item as $item) {
			$items[] = array(
				'entry' => $item->entry,
				'count' => $item->count,
				'search_filter' => urlencode(str_replace('$entry', $item->entry, $param['search_filter'])),
			);
		}
		usort($items, 'cmp');
	};

	// Use generic icon for action if the type is unknown
	$action = strtoupper($param['action']);
	if (!array_key_exists($action, $actions)) $action = '';

	$result2[] = array(
		'name' => $ns,
		'items' => array_slice($items, 0, 10),
		'count_limit' => $param['count_limit'],
		'action' => array(
			'type' => $action,
			'icon' => $actions[$action]['icon'],
			'color' => $actions[$action]['color'],
		),
	);
}

$javascript[] = 'static/js/rates.js';
require_once BASE.'/inc/smarty.php';

if ($errors) $smarty->assign('errors', $errors);
if ($search) $smarty->assign('search', $search);
$smarty->assign('namespaces', $result2);
$smarty->assign('source', $source);
$smarty->display('rates.tpl');
