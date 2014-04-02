<?php

if ($_SERVER['CPANEL'] != 'active')
	die();

error_reporting(E_ALL ^ E_NOTICE);

class Session
{
	private $username = null;
	private $source = null;
	private $access = null;
	public static function Get()
	{
		static $inst = null;
		if ($inst === null)
			$inst = new Session();
		return $inst;
	}
	private function __construct()
	{
		require_once '/usr/local/cpanel/php/cpanel.php';

		$cpanel = &new CPANEL();
		$result = $cpanel->api2('DomainLookup', 'getdocroots');

		$domains = array();
		foreach ($result['cpanelresult']['data'] as $domain)
			$domains[] = $domain['domain'];
		if (empty($domains))
			die('No domains');

		$this->username = $_SERVER['REMOTE_USER'];
		$this->source = 'cpanel';
		$this->access = array('domain' => $domains);
	}
	public function getUsername()
	{
		return $this->username;
	}
	public function getSource()
	{
		return $this->source;
	}
	public function getAccess()
	{
		return $this->access;
	}
}

if (Session::Get()->getUsername() === null) {
	die();
}

define('SP_ENDUSER', true);

switch (@$_GET['page'])
{
	case 'bwlist':
		require_once 'pages/bwlist.php';
	break;
	case 'download':
		require_once 'pages/download.php';
	break;
	case 'user':
		require_once 'pages/user.php';
	break;
	case 'preview':
		require_once 'pages/preview.php';
	break;
	case 'digest':
		require_once 'pages/digest.php';
	break;
	default:
	case 'index':
		require_once 'pages/index.php';
	break;
}

?>
