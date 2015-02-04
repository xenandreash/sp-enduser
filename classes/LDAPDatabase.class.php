<?php

class LDAPDatabase {
	private $uri = '';
	private $basedn = '';
	private $schema = '';
	private $options = array();

	public function __construct($uri, $basedn, $schema, $options) {
		$this->uri = $uri;
		$this->basedn = $basedn;
		$this->schema = $schema;
		if (is_array($options)) $this->options = $options;
	}
	public function check($username, $password) {
		// If username and password are not specified,
		// an anonymous bind is attempted. 
		if ($username == "" || $password == "")
			return false;

		if (!function_exists('ldap_connect'))
			die('PHP module LDAP missing (install php5-ldap)');

		$ds = ldap_connect($this->uri);
		if (!$ds)
			return false;
		ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
		
		foreach ($this->options as $k => $v)
			ldap_set_option($ds, $k, $v);

		$bind = @ldap_bind($ds, $username, $password);
		if (!$bind)
			return false;

		$access = array('mail' => array());

		$ldapuser = ldap_escape($username);
		switch ($this->schema) {
			case 'msexchange':
				$rs = ldap_search($ds, $this->basedn, "(&(userPrincipalName=$ldapuser)(proxyAddresses=smtp:*))", array('proxyAddresses'));
				$entry = ldap_first_entry($ds, $rs);
				if ($entry) {
					foreach (ldap_get_values($ds, $entry, 'proxyAddresses') as $mail) {
						if (!is_string($mail) || strcasecmp(substr($mail, 0, 5), 'smtp:') !== 0)
							continue;
						if (substr($mail, 0, 5) == 'SMTP:')
							array_unshift($access['mail'], strtolower(substr($mail, 5)));
						else
							array_push($access['mail'], strtolower(substr($mail, 5)));
					}
				}
			break;
			default:
				$rs = ldap_search($ds, $this->basedn, "(&(userPrincipalName=$ldapuser)(mail=*))", array('mail'));
				$entry = ldap_first_entry($ds, $rs);
				if ($entry) {
					foreach (ldap_get_values($ds, $entry, 'mail') as $mail) {
						if (!is_string($mail))
							continue;
						$access['mail'][] = strtolower($mail);
					}
				}
			break;
		}

		if (empty($access['mail']))
			return false;
	
		$_SESSION['username'] = $username;
		$_SESSION['source'] = 'ldap';
		$_SESSION['access'] = $access;

		return true;
	}
}
