<?php

/**
 * Wrapper around a database connection.
 */
class Database extends PDO
{
	public function __construct($credentials = null)
	{
		if($credentials === null)
		{
			$credentials = Settings::Get()->getDBCredentials();
			
			// This is ugly, but better than repeatedly repeating ourselves
			if(!$credentials['dsn'])
				die("No database configured");
		}
		
		$dsn = $credentials['dsn'];
		$username = isset($credentials['user']) ? $credentials['user'] : null;
		$password = isset($credentials['password']) ? $credentials['password'] : null;
		parent::__construct($dsn, $username, $password);
	}
}
