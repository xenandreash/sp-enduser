<?php

/**
 * Singleton wrapper for SP Enduser's settings (settings.php).
 */
class Settings
{
	private $settings = array();
	private $database = null;
	
	private $nodeCredentials = array();
	private $nodes = array();
	private $apiKey = null;
	private $dbCredentials = array('dns' => null);
	private $authSources = array(array('type' => 'server'));
	private $ldapOptions = array();
	
	private $mailSender = null;
	private $publicURL = null;
	
	private $pageName = "Halon SP for end-users";
	private $loginText = null;
	private $forgotText = null;
	private $defaultSource = 'history';
	private $displayScores = false;
	private $displayTextlog = false;
	private $displayHistory = true;
	private $displayQueue = true;
	private $displayQuarantine = true;
	private $displayAll = true;
	private $displayBWList = true;
	private $displayListener = array('mailserver:1' => "Inbound");
	private $displayTransport = array('mailtransport:2' => "Internet");
	private $useDatabaseLog = false;
	private $quarantineFilter = array();
	private $filterPattern = "{from} or {to}";
	
	private $digestToAll = false;
	private $digestSecret = null;
	private $sessionName = null;
	
	/**
	 * Returns a shared Settings instance.
	 */
	public static function Get()
	{
		static $inst = null;
		if ($inst === null)
			$inst = new Settings();
		return $inst;
	}
	
	/**
	 * Private constructor; use Settings::Get().
	 */
	private function __construct()
	{
		$settings = array();
		require BASE.'/settings.php';
		
		$this->settings = $settings;
		
		$this->extract($this->nodeCredentials, 'node');
		$this->extract($this->apiKey, 'api-key');
		$this->extract($this->mailSender, 'mail.from');
		$this->extract($this->publicURL, 'public-url');
		$this->extract($this->pageName, 'pagename');
		$this->extract($this->loginText, 'logintext');
		$this->extract($this->forgotText, 'forgottext');
		$this->extract($this->defaultSource, 'default-source');
		$this->extract($this->displayScores, 'display-scores');
		$this->extract($this->displayTextlog, 'display-textlog');
		$this->extract($this->displayQueue, 'display-queue');
		$this->extract($this->displayQuarantine, 'display-quarantine');
		$this->extract($this->displayAll, 'display-all');
		$this->extract($this->displayBWList, 'display-bwlist');
		$this->extract($this->displayListener, 'display-listener');
		$this->extract($this->displayTransport, 'display-transport');
		$this->extract($this->useDatabaseLog, 'database-log');
		$this->extract($this->dbCredentials, 'database');
		$this->extract($this->authSources, 'authentication');
		$this->extract($this->ldapOptions, 'ldap-options');
		$this->extract($this->quarantineFilter, 'quarantine-filter');
		$this->extract($this->filterPattern, 'filter-pattern');
		$this->extract($this->digestToAll, 'digest.to-all');
		$this->extract($this->digestSecret, 'digest.secret');
		
		foreach ($this->nodeCredentials as $cred) {
			$username = isset($cred['username']) ? $cred['username'] : null;
			$password = isset($cred['password']) ? $cred['password'] : null;
			$serial = isset($cred['serialno']) ? $cred['serialno'] : null;
			$this->nodes[] = new Node($cred['address'], $username, $password, $serial);
		}
		
		if(!$this->publicURL)
		{
			$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https" : "http";
			$url = $protocol."://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$this->publicURL = preg_replace("#[^/]*$#", "", $url);
		}
	}
	
	/**
	 * Extracts a value from the $this->settings array.
	 */
	private function extract(&$out, $key)
	{
		$parts = explode('.', $key);
		$tmp = $this->settings;
		foreach ($parts as $part) {
			$tmp = isset($tmp[$part]) ? $tmp[$part] : null;
			if($tmp === null) return;
		}
		
		$out = $tmp;
	}
	
	/**
	 * Returns a database wrapper object.
	 */
	public function getDatabase()
	{
		if(!$this->database)
		{
			$credentials = $this->getDBCredentials();
			
			if(!$credentials['dsn'])
				return null;
			
			$dsn = $credentials['dsn'];
			$username = isset($credentials['user']) ? $credentials['user'] : null;
			$password = isset($credentials['password']) ? $credentials['password'] : null;
			$this->database = new Database($dsn, $username, $password);
		}
		
		return $this->database;
	}
	
	/**
	 * Returns the credentials for all configured nodes.
	 */
	public function getNodeCredentials()
	{
		return $this->nodeCredentials;
	}
	
	/**
	 * Returns a list of all configured nodes.
	 */
	public function getNodes()
	{
		return $this->nodes;
	}
	
	/**
	 * Returns a specific node from the list, or null if there's no such node.
	 */
	public function getNode($i)
	{
		if($i < count($this->nodes))
			return $this->nodes[$i];
		return null;
	}
	
	/**
	 * Returns the nodes' API key.
	 */
	public function getAPIKey()
	{
		return $this->apiKey;
	}
	
	/**
	 * Returns the configured database credentials.
	 */
	public function getDBCredentials()
	{
		return $this->dbCredentials;
	}
	
	/**
	 * Returns all configured authentication sources.
	 */
	public function getAuthSources()
	{
		return $this->authSources;
	}
	
	/**
	 * Returns an array of raw LDAP options. Empty by default.
	 */
	public function getLDAPOptions()
	{
		return $this->ldapOptions;
	}
	
	/**
	 * Returns the value for the "From:" field in outgoing emails, if any.
	 */
	public function getMailSender()
	{
		return $this->mailSender;
	}
	
	/**
	 * Returns the site's public URL (autodetected by default).
	 */
	public function getPublicURL()
	{
		return $this->publicURL;
	}
	
	/**
	 * Returns the page name.
	 */
	public function getPageName()
	{
		return $this->pageName;
	}
	
	/**
	 * Returns some text to display at the top of the login form, or null.
	 */
	public function getLoginText()
	{
		return $this->loginText;
	}
	
	/**
	 * Returns some text to do display at the top of the forgot form, or null.
	 */
	public function getForgotText()
	{
		return $this->forgotText;
	}
	
	/**
	 * Returns the default-selected Source.
	 */
	public function getDefaultSource()
	{
		return $this->defaultSource;
	}
	
	/**
	 * Returns whether scores should be displayed.
	 */
	public function getDisplayScores()
	{
		return $this->displayScores;
	}
	
	/**
	 * Returns whether the text log should be displayed.
	 */
	public function getDisplayTextlog()
	{
		return $this->displayTextlog;
	}
	
	/**
	 * Returns whether the History source should be displayed.
	 */
	public function getDisplayHistory()
	{
		return $this->displayHistory;
	}
	
	/**
	 * Returns whether the Queue source should be displayed.
	 */
	public function getDisplayQueue()
	{
		if ($this->getUseDatabaseLog())
			return false;
		return $this->displayQueue;
	}
	
	/**
	 * Returns whether the Quarantine source should be displayed.
	 */
	public function getDisplayQuarantine()
	{
		if ($this->getUseDatabaseLog())
			return false;
		return $this->displayQuarantine;
	}
	
	/**
	 * Returns whether the "All" (SOAP) source should be displayed.
	 */
	public function getDisplayAll()
	{
		if ($this->getUseDatabaseLog())
			return false;
		return $this->displayAll;
	}

	/**
	 * Returns whether the black/whitelist tab should be displayed.
	 */
	public function getDisplayBWList()
	{
		return $this->displayBWList;
	}
	
	/**
	 * ???
	 */
	public function getDisplayListener()
	{
		return $this->displayListener;
	}
	
	/**
	 * ???
	 */
	public function getDisplayTransport()
	{
		return $this->displayTransport;
	}
	
	/**
	 * Returns whether or not database logging is enabled.
	 */
	public function getUseDatabaseLog()
	{
		return $this->useDatabaseLog;
	}
	
	/**
	 * Returns a list of which quarantines should be visible, or an empty array
	 * if they should all be visible.
	 */
	public function getQuarantineFilter()
	{
		return $this->quarantineFilter;
	}
	
	/**
	 * Returns the pattern for creating additional inbound/outbound
	 * restrictions.
	 */
	public function getFilterPattern()
	{
		return $this->filterPattern;
	}
	
	/**
	 * Returns whether digest emails should be sent to everyone.
	 */
	public function getDigestToAll()
	{
		return $this->digestToAll;
	}
	
	/**
	 * Returns the secret key used to generate a "direct release" link in
	 * digest emails.
	 */
	public function getDigestSecret()
	{
		return $this->digestSecret;
	}
	
	/**
	 * Returns the custom session name, if any.
	 */
	public function getSessionName()
	{
		return $this->sessionName;
	}
}
