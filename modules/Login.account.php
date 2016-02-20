<?php

function halon_login_account($username, $password, $method, $settings)
{
	if ($username === $method['username'] && $password === $method['password'])
	{
		$result = [];
		$result['username'] = $method['username'];
		$result['source'] = 'local';
		$result['access'] = $method['access'];
		return $result;
	}
	return false;
}
