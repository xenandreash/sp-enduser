<?php

/*
 * authenticate users against a LDAP database
 */

function halon_login_ldap($username, $password, $method, $settings)
{
	$ldap = new LDAPDatabase($method['uri'], $method['base_dn'], $method['schema'], $method['options'], $method['query'], $method['access'], $method['bind_dn'], $method['bind_password'], $method['memberof']);
	$result = $ldap->check($username, $password);
	if ($result && is_array($result)) $result['disabled_features'] = $method['disabled_features'];
	return $result;
}
