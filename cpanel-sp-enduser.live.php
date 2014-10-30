<?php
if ($_SERVER['CPANEL'] != 'active')
	die();

error_reporting(E_ALL ^ E_NOTICE);
// header("Content-Type: text/plain");

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
		
		$this->username = $_SERVER['REMOTE_USER'];
		$this->source = 'cpanel';
		
		// For some reason, querying 'listpops' when signed in as a domain
		// owner returns only the username, while logging in as a specific
		// email address returns that address (and likely aliases as well)
		if(strpos($_SERVER['REMOTE_USER'], '@') === false)
		{
			// It's the domain owner, give them access to everything
			$domains_res = $cpanel->api2('Email', 'listmaildomains');
			$domains = array();
			foreach($domains_res['cpanelresult']['data'] as $data)
				$domains[] = $data['domain'];
			if(empty($domains))
				die("No Domains");
			$this->access = array('domain' => $domains);
		}
		else
		{
			// It's an email user, give them access to their own account
			$addresses_res = $cpanel->api2('Email', 'listpops');
			$addresses = array();
			foreach($addresses_res['cpanelresult']['data'] as $data)
				$addresses[] = $data['email'];
			if(empty($addresses))
				die("No Addresses");
			$this->access = array('mail' => $addresses);
		}
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
	
	// Not supported in cPanel mode; server auth is not possible
	public function getSOAPUsername()
	{
		return null;
	}
	public function getSOAPPassword()
	{
		return null;
	}
}

if (Session::Get()->getUsername() === null) {
	die();
}

if (isset($_GET['timezone'])) setcookie('timezone', intval($_GET['timezone']));
if (!isset($_COOKIE['timezone']))
	die('<script>window.location.href = "?timezone=" + new Date().getTimezoneOffset();</script>');
$_SESSION['timezone'] = $_COOKIE['timezone'];

define('SP_ENDUSER', true);
define('BASE', dirname(__FILE__));

switch (@$_GET['page'])
{
	case 'bwlist':
		require_once BASE.'/pages/bwlist.php';
	break;
	case 'download':
		require_once BASE.'/pages/download.php';
	break;
	case 'user':
		require_once BASE.'/pages/user.php';
	break;
	case 'preview':
		require_once BASE.'/pages/preview.php';
	break;
	case 'digest':
		require_once BASE.'/pages/digest.php';
	break;
	default:
	case 'index':
		require_once BASE.'/pages/index.php';
	break;
}
