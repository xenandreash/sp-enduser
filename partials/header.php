<?php

require_once BASE.'/inc/core.php';

require_once BASE.'/inc/smarty.php';

$smarty->assign('page_active', $_GET['page'] ? $_GET['page'] : 'index');
$smarty->assign('title', $title ?: 'Untitled');

$smarty->display('header.tpl');
