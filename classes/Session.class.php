<?php

/**
 * Singleton wrapper for the user's session.
 */
class Session
{
	private $authenticated = null;
	private $username = null;
	private $source = null;
	private $access = null;
	private $soap_username = null;
	private $soap_password = null;
	private $disabled_features = null;

	// elasticsearch
	private $available_indices = [];

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

		$this->authenticated = $_SESSION['authenticated'];
		$this->username = $_SESSION['username'];
		$this->source = $_SESSION['source'];
		$this->access = $_SESSION['access'];

		if(isset($_SESSION['soap_username']))
			$this->soap_username = $_SESSION['soap_username'];
		if(isset($_SESSION['soap_password']))
			$this->soap_password = $_SESSION['soap_password'];
		if(isset($_SESSION['disabled_features']))
			$this->disabled_features = $_SESSION['disabled_features'];

		if (isset($_SESSION['available_indices']))
			$this->available_indices = $_SESSION['available_indices'];
	}

	/**
	 * Returns true if the user is authenticated.
	 */
	public function isAuthenticated()
	{
		return $this->authenticated === true;
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
	 * Returns the features that are disabled for the user.
	 */
	public function getDisabledFeatures()
	{
		return (is_array($this->disabled_features) ? $this->disabled_features : array());
	}

	/**
	 * Check if a specific feature is disabled for the user.
	 */
	public function checkDisabledFeature($feature)
	{
		if (is_array($this->disabled_features) and in_array($feature, $this->disabled_features))
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

	/**
	 * Returns the user's assigned messagelog table
	 */
	public function getMessagelogTable()
	{
		return Settings::Get()->getMessagelogTable($this->access['userid']);
	}

	/**
	 * Two-factor authentication - Returns a users secret key if there is one
	 */
	public function getSecretKey($username)
	{
		global $settings;

		if (!isset($username) || empty($username))
			return false;

		$dbh = $settings->getDatabase();
		$statement = $dbh->prepare("SELECT * FROM users_totp WHERE username = :username;");
		$statement->execute([':username' => $username]);
		$row = $statement->fetch(PDO::FETCH_ASSOC);

		if (!$row)
			return false;
		else
			return $row['secret'];
	}

	/**
	 * Cache available indices for Elasticsearch, only update if needed
	 */
	public function setElasticsearchIndices($indices)
	{
		$this->available_indices = $indices;
		$_SESSION['available_indices'] = $indices;
	}

	/**
	 * Returns current indices cache for Elasticsearch
	 */
	public function getElasticsearchIndices()
	{
		return $this->available_indices;
	}
}
