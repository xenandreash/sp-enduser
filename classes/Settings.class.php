<?php

/**
 * Singleton wrapper for SP Enduser's settings (settings.php).
 */
class Settings
{
	private $settings = array();
	
	private $nodes = array();
	private $apiKey = null;
	private $dbCredentials = array('dns' => null);
	private $authSources = array(array('type' => 'server'));
	
	private $mailSender = null;
	private $publicURL = null;
	
	private $defaultSource = 'history';
	private $displayScores = false;
	private $displayTextlog = false;
	private $displayListener = array('mailserver:1' => "Inbound");
	private $displayTransport = array('mailtransport:2' => "Internet");
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
		$this->publicURL = self_url();
		
		$settings = array();
		require BASE.'/settings.php';
		
		$this->settings = $settings;
		
		$this->extract($this->nodes, 'node');
		$this->extract($this->apiKey, 'api-key');
		$this->extract($this->mailSender, 'mail.from');
		$this->extract($this->publicURL, 'public-url');
		$this->extract($this->defaultSource, 'default-source');
		$this->extract($this->displayScores, 'display-scores');
		$this->extract($this->displayTextlog, 'display-textlog');
		$this->extract($this->displayListener, 'display-listener');
		$this->extract($this->displayTransport, 'display-transport');
		$this->extract($this->dbCredentials, 'database');
		$this->extract($this->authSources, 'authentication');
		$this->extract($this->quarantineFilter, 'quarantine-filter');
		$this->extract($this->filterPattern, 'filter-pattern');
		$this->extract($this->digestToAll, 'digest.to-all');
		$this->extract($this->digestSecret, 'digest.secret');
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
	 * Returns a list of all configured nodes.
	 */
	public function getNodes()
	{
		return $this->nodes;
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
