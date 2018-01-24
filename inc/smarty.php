<?php

require_once 'gettext.php';

$smarty = new Smarty();
$smarty->compile_dir = '/tmp/';
$smarty->template_dir = './templates/';

if ($smarty_no_assign) {
	unset($smart_no_assign);
	return;
}

$smarty->assign('theme', $settings->getTheme());
$smarty->assign('brand_logo', $settings->getBrandLogo());
$smarty->assign('brand_logo_height', $settings->getBrandLogoHeight());
$smarty->assign('pagename', $settings->getPageName());
if (Session::Get()->isAuthenticated()) $smarty->assign('authenticated', true);
if (Session::Get()->getUsername()) $smarty->assign('username', Session::Get()->getUsername());

if (isset($javascript)) $smarty->assign('javascript', $javascript);

if ($settings->getDisplayBWList()) $smarty->assign('feature_bwlist', true);
if ($settings->getDisplaySpamSettings()) $smarty->assign('feature_spam', true);
$access = Session::Get()->getAccess();
if ((count($access['domain']) > 0 || isset($access['userid'])) && $settings->getDisplayStats()) $smarty->assign('feature_stats', true);
if (Session::Get()->checkAccessAll() && $settings->getDisplayRateLimits()) $smarty->assign('feature_rates', true);

if ((Session::Get()->checkAccessAll()
	|| count($access['domain']) > 0)
	&& $settings->getDisplayDataStore()
	&& !Session::Get()->checkDisabledFeature('display-datastore')
) {
	$smarty->assign('feature_datastore', true);
}

if (Session::Get()->checkAccessAll() && $settings->getDisplayUsers() && !Session::Get()->checkDisabledFeature('display-users'))
	$smarty->assign('feature_users', true);

if (Session::Get()->checkAccessAll() && $settings->getTwoFactorAuth()) $smarty->assign('feature_totp', true);

$smarty->assign('feature_dblog', $settings->getUseDatabaseLog());
$smarty->assign('is_superadmin', Session::Get()->checkAccessAll());

if (isset($body_class)) $smarty->assign('body_class', $body_class);
