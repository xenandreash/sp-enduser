<?php

error_reporting(E_ALL ^ E_NOTICE);
define('SP_ENDUSER', true);

if (file_exists('install.php')) {
	require_once 'install.php';
	die();
}

switch (@$_GET['page'])
{
	case 'forgot':
		require_once 'pages/forgot.php';
	break;
	case 'login':
		require_once 'pages/login.php';
	break;
	case 'logout':
		require_once 'pages/logout.php';
	break;
	case 'bwlist':
		require_once 'inc/session.php';
		require_once 'pages/bwlist.php';
	break;
	case 'download':
		require_once 'inc/session.php';
		require_once 'pages/download.php';
	break;
	case 'user':
		require_once 'inc/session.php';
		require_once 'pages/user.php';
	break;
	case 'log':
		require_once 'inc/session.php';
		require_once 'pages/log.php';
	break;
	case 'preview':
		require_once 'inc/session.php';
		require_once 'pages/preview.php';
	break;
	case 'digest':
		require_once 'pages/digest.php';
	break;
	default:
	case 'index':
		require_once 'inc/session.php';
		require_once 'pages/index.php';
	break;
}

?>
