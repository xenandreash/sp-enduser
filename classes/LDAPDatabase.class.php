<?php

class LDAPDatabase
{
	private $uri = '';
	private $basedn = '';
	private $schema = '';
	private $options = array();
	private $query = '';
	private $access_override = array();

	public function __construct($uri, $basedn, $schema, $options, $query, $access_override)
	{
		$this->uri = $uri;
		$this->basedn = $basedn;
		$this->schema = $schema;
		if (is_array($options)) $this->options = $options;
		$this->query = $query;
		if (is_array($access_override)) $this->access_override = $access_override;
	}
	public function check($username, $password)
	{
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
		$authed = false;

		$ldapuser = $this->_ldap_escape($username);
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
			case 'auth-only':
				$query = str_replace("\$ldapuser", $ldapuser, $this->query);
				$rs = ldap_search($ds, $this->basedn, $query);
				$entry = ldap_first_entry($ds, $rs);
				if ($entry) {
					$access = $this->access_override;
					$authed = true;
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

		if (empty($access['mail']) and !$authed)
			return false;
	
		$result = array();
		$result['username'] = $username;
		$result['source'] = 'ldap';
		$result['access'] = $access;
		return $result;
	}

	/*
	 * First appeared in PHP 5.6
	 */
	function _ldap_escape($data)
	{
		if (function_exists('ldap_escape'))
			return ldap_escape($data);
		return str_replace(array('\\', '*', '(', ')', '\0'), array('\\5c', '\\2a', '\\28', '\\29', '\\00'), $data);
	}
}
