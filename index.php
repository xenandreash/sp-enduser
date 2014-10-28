<?php
// cPanel users should use the cPanel LivePHP version instead
if (isset($_SERVER['CPANEL']))
{
	header("Location: cpanel-sp-enduser.live.php");
	die();
}

error_reporting(E_ALL ^ E_NOTICE);

define('SP_ENDUSER', true);
define('BASE', dirname(__FILE__));

if (file_exists(BASE.'/install.php')) {
	require_once BASE.'/install.php';
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
		require_once BASE.'/inc/session.php';
		require_once BASE.'/pages/bwlist.php';
	break;
	case 'download':
		require_once BASE.'/inc/session.php';
		require_once BASE.'/pages/download.php';
	break;
	case 'user':
		require_once BASE.'/inc/session.php';
		require_once BASE.'/pages/user.php';
	break;
	case 'log':
		require_once BASE.'/inc/session.php';
		require_once BASE.'/pages/log.php';
	break;
	case 'preview':
		require_once BASE.'/inc/session.php';
		require_once BASE.'/pages/preview.php';
	break;
	case 'digest':
		require_once BASE.'/pages/digest.php';
	break;
	default:
	case 'index':
		require_once BASE.'/inc/session.php';
		require_once BASE.'/pages/index.php';
	break;
}
