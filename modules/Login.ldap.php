<?php

/*
 * authenticate users against a LDAP database
 */

function halon_login_ldap($username, $password, $method, $settings)
{
	$method = new LDAPDatabase($method['uri'], $method['base_dn'], $method['schema'], $method['options'], $method['query'], $method['access']);
	return $method->check($username, $password);
}
