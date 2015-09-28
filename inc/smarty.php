<?php

$smarty = new Smarty();
$smarty->compile_dir = '/tmp/';
$smarty->template_dir = './templates/';

$smarty->assign('pagename', $settings->getPageName());
if (Session::Get()->getUsername()) $smarty->assign('username', Session::Get()->getUsername());

$smarty->assign('logo', file_exists('template/logo.png') ? 'template/logo.png' : 'static/img/logo.png');
$smarty->assign('styles', file_exists('template/styles.css') ? 'template/styles.css' : 'static/css/styles.css');
if (isset($javascript)) $smarty->assign('javascript', $javascript);

$dbCredentials = $settings->getDBCredentials();
if ($dbCredentials['dsn'] && $settings->getDisplayBWList()) $smarty->assign('feature_bwlist', true);
$access = Session::Get()->getAccess();
if (count($access['domain']) > 0 && $settings->getDisplayStats()) $smarty->assign('feature_stats', true);

if (isset($body_class)) $smarty->assign('body_class', $body_class);
