<?php

/*
 * authenticate users using SMTP+SASL
 * - only supports mail access
 */

function halon_login_smtp($username, $password, $method, $settings)
{
	$tls = $method['tls'] ?: array();
	$context = stream_context_create(array('ssl' => $tls));

	$fp = stream_socket_client($method['host'].':'.$method['port'] ?: 25, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
	while ($line = fgets($fp)) {
		if (substr($line, 0, 1) != '2')
			goto smtp_fail;
		if (substr($line, 3, 1) == ' ')
			break;
	}
	$methods = [];
	$starttls = false;
smtp_ehlo:
	fwrite($fp, "EHLO ".gethostname()."\r\n");
	$found_starttls = false;
	while ($line = fgets($fp)) {
		if (substr($line, 0, 1) != '2')
			goto smtp_fail;
		if (substr($line, 4, 5) == 'AUTH ' && strpos($line, 'CRAM-MD5') !== false)
			$methods[] = 'CRAM-MD5';
		if (substr($line, 4, 5) == 'AUTH ' && strpos($line, 'LOGIN') !== false)
			$methods[] = 'LOGIN';
		if (substr($line, 4, 5) == 'AUTH ' && strpos($line, 'PLAIN') !== false)
			$methods[] = 'PLAIN';
		if (substr($line, 4, 8) == 'STARTTLS')
			$found_starttls = true;
		if (substr($line, 3, 1) == ' ')
			break;
	}
	if (!$starttls && $found_starttls) {
		fwrite($fp, "STARTTLS\r\n");
		$line = fgets($fp);
		if (substr($line, 0, 3) != '220')
			goto smtp_fail;
		stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
		$starttls = true;
		goto smtp_ehlo;
	}
	if (in_array('CRAM-MD5', $methods)) {
		fwrite($fp, "AUTH CRAM-MD5\r\n");
		$line = fgets($fp);
		$chall = substr($line, 4);
		$data = $username.' '.hash_hmac('md5', base64_decode($chall), $password);
		$data = base64_encode($data);
		fwrite($fp, "$data\r\n");
	} else if (in_array('PLAIN', $methods)) {
		$plain = base64_encode($username . "\0" . $username . "\0" . $password);
		fwrite($fp, "AUTH PLAIN $plain\r\n");
	} else if (in_array('LOGIN', $methods)) {
		fwrite($fp, "AUTH LOGIN\r\n");
		$line = fgets($fp);
		if (substr($line, 0, 3) != '334')
			goto smtp_fail;
		fwrite($fp, base64_encode($username)."\r\n");
		$line = fgets($fp);
		if (substr($line, 0, 3) != '334')
			goto smtp_fail;
		fwrite($fp, base64_encode($password)."\r\n");
	} else {
		goto smtp_fail;
	}
	while ($line = fgets($fp))
		if (substr($line, 3, 1) != '-')
			break;
	if (substr($line, 0, 3) != '235')
		goto smtp_fail;
	fwrite($fp, "QUIT\r\n");

	$result = array();
	$result['username'] = $username;
	$result['source'] = 'smtp';
	$result['access'] = array('mail' => array(strtolower($username)));
	$result['disabled_features'] = $method['disabled_features'];
	return $result;

smtp_fail:
	fwrite($fp, "QUIT\r\n");
	return false;
}
