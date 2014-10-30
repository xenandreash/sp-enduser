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
		$session_name = settings('session-name');
		if ($session_name)
			session_name($session_name);
		
		session_start();
		
		$this->username = $_SESSION['username'];
		$this->source = $_SESSION['source'];
		$this->access = $_SESSION['access'];
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
	 */
	public function getAccess()
	{
		return $this->access;
	}
}
