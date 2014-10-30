<?php

class Node
{
	private $address;
	private $username;
	private $password;
	
	public function __construct($address, $username = null, $password = null)
	{
		$this->address = $address;
		$this->username = $username;
		$this->password = $password;
	}
	
	public function soap($async = false, $username = null, $password = null)
	{
		$session = Session::Get();
		
		if(!$username) $username = $session->getSOAPUsername() ?: $this->getUsername();
		if(!$password) $password = $session->getSOAPPassword() ?: $this->getPassword();
		
		$options = array(
			'location' => $this->getAddress().'/remote/',
			'uri' => 'urn:halon',
			'login' => $username,
			'password' => $password,
			'connection_timeout' => 15,
			'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
			'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
			);
		
		if ($async)
			return new SoapClientAsync($options['location'].'?wsdl', $options);
		return new SoapClient($options['location'].'?wsdl', $options);
	}
	
	public function getAddress()
	{
		return $this->address;
	}
	
	public function getUsername()
	{
		return $this->username;
	}
	
	public function getPassword()
	{
		return $this->password;
	}
}