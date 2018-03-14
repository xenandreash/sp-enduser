<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!Session::Get()->checkAccessAll())
	die('Insufficient permissions');

require_once BASE.'/inc/smarty.php';
require_once BASE.'/inc/utils/hsl.inc.php';

$smarty->assign('show_script', isset($_GET['script']) ? $_GET['script'] : "api");
$smarty->assign('hsl_script', hsl_script());
$smarty->assign('page_active', $_GET['page']);

$smarty->display('apiscript.tpl');
