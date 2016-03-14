<?php

// 5.5.0 >=
if (!function_exists('password_verify')) {
	function password_verify($password, $hash) {
		return ($hash === crypt($password, $hash));
	}
}

// 5.5.0 >=
if (!function_exists('password_hash')) {
	function password_hash($password, $ignored) {
		return crypt($password);
	}
}

// 5.6.0 >=
if (!function_exists('ldap_escape'))
{
	return str_replace(array('\\', '*', '(', ')', '\0'), array('\\5c', '\\2a', '\\28', '\\29', '\\00'), $data);
}
