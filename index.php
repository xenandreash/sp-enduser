<?php

define('SP_ENDUSER', TRUE);

switch (@$_GET['page'])
{
	case 'bwlist':
		require_once 'pages/bwlist.php';
	break;
	case 'download':
		require_once 'pages/download.php';
	break;
	case 'forget':
		require_once 'pages/forget.php';
	break;
	case 'user':
		require_once 'pages/user.php';
	break;
	case 'preview':
		require_once 'pages/preview.php';
	break;
	case 'login':
		require_once 'pages/login.php';
	break;
	case 'logout':
		require_once 'pages/logout.php';
	break;
	default:
	case 'index':
		require_once 'pages/index.php';
	break;
}

?>
