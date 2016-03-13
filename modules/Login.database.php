<?php

/*
 * authenticate users against database users
 */

function halon_login_database($username, $password, $method, $settings)
{
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare("SELECT * FROM users WHERE username = :username;");
	$statement->execute(array(':username' => $username));
	$row = $statement->fetch(PDO::FETCH_ASSOC);
	if (!$row || !password_verify($password, $row['password']))
		return false;

	$result = array();
	$result['username'] = $row['username'];
	$result['source'] = 'database';
	$result['access'] = array();
	$statement = $dbh->prepare("SELECT * FROM users_relations WHERE username = :username;");
	$statement->execute(array(':username' => $row['username']));
	while ($row = $statement->fetch(PDO::FETCH_ASSOC))
		$result['access'][$row['type']][] = $row['access'];
	return $result;
}
