<?php

/*
 * authenticate users against a LDAP database
 */

function halon_login_ldap($username, $password, $method, $settings)
{
	$method = new LDAPDatabase($method['uri'], $method['base_dn'], $method['schema'], $method['options'], $method['query'], $method['access']);
	$result = $method->check($username, $password);
	if (is_array($result)) $result['disabled_features'] = $method['disabled_features'];
	return $result;
}
