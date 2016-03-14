<?php

/*
 * authenticate users against a Halon system over SOAP
 * - only supports full-access users
 */

function halon_login_server($username, $password, $method, $settings)
{
	// Loop through all the configured nodes; the primary node going
	// down shouldn't take all auth down with it, merely slow it
	for ($i = 0; $i < count($settings->getNodes()); $i++)
	{
		try {
			// Attempt to connect to the node
			soap_client($i, false, $username, $password)->login();
						
			// Set the client to be logged in
			$result = array();
			$result['username'] = $username;
			$result['source'] = 'server';
			$result['access'] = array();

			// Use the user's credentials instead of the config's
			$result['soap_username'] = $username;
			$result['soap_password'] = $password;
			return $result;
		} catch (SoapFault $e) {
			// If the node is unavailable, skip to the next one
			if ($e->getMessage() != "Unauthorized")
				continue;
		}
		break;
	}
	return false;
}
