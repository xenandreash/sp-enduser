<?php

/**
 * Singleton wrapper for the user's session.
 * 
 * Note that the cPanel LivePHP version uses a different implementation of
 * this class, defined in cpanel-sp-enduser.live.php, that uses the cPanel
 * user's session instead.
 */
class Session
{
	private $username = null;
	private $source = null;
	private $access = null;
	private $soap_username = null;
	private $soap_password = null;
	
	/**
	 * Returns a shared Session instance.
	 */
	public static function Get()
	{
		static $inst = null;
		if ($inst === null)
			$inst = new Session();
		return $inst;
	}
	
	/**
	 * Private constructor; use Session::Get().
	 */
	private function __construct()
	{
		$session_name = Settings::Get()->getSessionName();
		if ($session_name)
			session_name($session_name);
		
		session_start();
		
		$this->username = $_SESSION['username'];
		$this->source = $_SESSION['source'];
		$this->access = $_SESSION['access'];
		
		if(isset($_SESSION['soap_username']))
			$this->soap_username = $_SESSION['soap_username'];
		if(isset($_SESSION['soap_password']))
			$this->soap_password = $_SESSION['soap_password'];
	}
	
	/**
	 * Returns the user's username.
	 * 
	 * What exactly this means is a bit dependent on the configured
	 * authentication methods - it may be basically anything, but should be
	 * written out as-is.
	 */
	public function getUsername()
	{
		return $this->username;
	}
	
	/**
	 * Returns the user's authentication source.
	 * 
	 * This can be one of:
	 * 
	 *   - account:  A local account defined in settings.php
	 *   - smtp:     Login accepted by an SMTP server
	 *   - ldap:     Login accepted by an LDAP server
	 *   - database: An account defined in an SQL database
	 *   - server:   Authenticated directly against an SP node
	 */
	public function getSource()
	{
		return $this->source;
	}
	
	/**
	 * Returns the user's access parameters (permissions).
	 * 
	 * This is an array with several keys that, if given, restrict the user to
	 * only the given realms. An empty array means no access.
	 * 
	 * Possible keys:
	 * 
	 *   - mail: Can only see records involving the given email address(es)
	 *   - domain: Can only see records involving the given domain(s)
	 * 
	 * @param $key The key to retrieve, or NULL for the whole array
	 */
	public function getAccess($key=NULL)
	{
		return $key !== NULL ? $this->access[$key] : ($this->access ?: array());
	}

	public function checkAccessMail($mail)
	{
		// super admin
		if (count($this->access) == 0)
			return true;
		// mail access
		$access_mail = (is_array($this->access['mail']) ? $this->access['mail'] : array());
		if (in_array($mail, $access_mail, true))
			return true;
		// domain access
		$access_domain = (is_array($this->access['domain']) ? $this->access['domain'] : array());
		$mail = explode('@', $mail);
		if (count($mail) != 2)
			return false;
		if (in_array($mail[1], $access_domain, true))
			return true;
		return false;
	}

	public function checkAccessDomain($domain)
	{
		// super admin
		if (count($this->access) == 0)
			return true;
		// domain access
		$access_domain = (is_array($this->access['domain']) ? $this->access['domain'] : array());
		if (in_array($domain, $access_domain, true))
			return true;
		return false;
	}

	public function checkAccessAll()
	{
		// super admin
		if (count($this->access) == 0)
			return true;
		return false;
	}
	
	/**
	 * Returns the user's own SOAP username, if there is one.
	 * 
	 * This is currently only used with server authentication.
	 */
	public function getSOAPUsername()
	{
		return $this->soap_username;
	}
	
	/**
	 * Returns the user's own SOAP password, if there is one.
	 * 
	 * This is currently only used with server authentication.
	 */
	public function getSOAPPassword()
	{
		return $this->soap_password;
	}
}
