<?php

define('SP_ENDUSER', true);
define('BASE', dirname(__FILE__));

if (file_exists(BASE.'/install.php') and !file_exists(BASE.'/installed.txt')) {
	require_once BASE.'/install.php';
	die();
}

require_once BASE.'/inc/core.php';

if (Session::Get()->isAuthenticated() && $_SERVER['QUERY_STRING'] == 'xhr') {
	require_once BASE.'/xhr.php';
	die();
}

if (!Session::Get()->isAuthenticated() && (!isset($_GET['page']) || ($_GET['page'] != 'login' && $_GET['page'] != 'forgot' && $_GET['page'] != 'digest'))) {
	session_destroy();
	header("Location: ?page=login&query=".urlencode($_SERVER['QUERY_STRING']));
	die();
}

if ($version['update_required']) {
	if (Session::Get()->isAuthenticated()) {
		session_destroy();
		header('Location: ?page=login');
		die();
	}

	require_once BASE.'/inc/smarty.php';
	$smarty->display('maintenance.tpl');
	die();
}

switch (@$_GET['page'])
{
	case 'forgot':
		require_once BASE.'/pages/forgot.php';
	break;
	case 'login':
		require_once BASE.'/pages/login.php';
	break;
	case 'logout':
		require_once BASE.'/pages/logout.php';
	break;
	case 'bwlist':
		require_once BASE.'/pages/bwlist.php';
	break;
	case 'spam':
		require_once BASE.'/pages/spam.php';
	break;
	case 'datastore':
		require_once BASE.'/pages/datastore.php';
	break;
	case 'download':
		require_once BASE.'/pages/download.php';
	break;
	case 'user':
		require_once BASE.'/pages/user.php';
	break;
	case 'stats':
		require_once BASE.'/pages/stats.php';
	break;
	case 'log':
		require_once BASE.'/pages/log.php';
	break;
	case 'preview':
		require_once BASE.'/pages/preview.php';
	break;
	case 'digest':
		require_once BASE.'/pages/digest.php';
	break;
	case 'rates':
		require_once BASE.'/pages/rates.php';
	break;
	case 'users':
		require_once BASE.'/pages/users.php';
	break;
	case 'totp':
		require_once BASE.'/pages/totp.php';
	break;
	case 'apiscript':
		require_once BASE.'/pages/apiscript.php';
	break;
	default:
	case 'index':
		require_once BASE.'/pages/index.php';
	break;
}
