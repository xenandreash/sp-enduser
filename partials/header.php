<?php

require_once BASE.'/inc/core.php';

$pagename = $settings->getPageName();
$title = $title ?: 'Untitled';
$logo = file_exists('template/logo.png') ? 'template/logo.png' : 'static/img/logo.png';
$styles = file_exists('template/styles.css') ? 'template/styles.css' : 'static/css/styles.css';

function header_active($page)
{
	if ($_GET['page'] == $page || ($_GET['page'] == '' && $page == 'index'))
		echo ' active';
}

require_once BASE.'/inc/smarty.php';

$smarty->assign('page_active', $_GET['page'] ? $_GET['page'] : 'index');
$smarty->assign('title', $title);

$smarty->display('header.tpl');
