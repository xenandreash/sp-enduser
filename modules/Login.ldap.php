<?php

/*
 * authenticate users against a LDAP database
 */

function halon_login_ldap($username, $password, $method, $settings)
{
	$ldap = new LDAPDatabase($method['uri'], $method['base_dn'], $method['schema'], $method['options'], $method['query'], $method['access']);
	$result = $ldap->check($username, $password);
	if ($result && is_array($result)) $result['disabled_features'] = $method['disabled_features'];
	return $result;
}
