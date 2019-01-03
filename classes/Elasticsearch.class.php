<?php

use Elasticsearch\ClientBuilder;

class Elasticsearch
{
	private $_client = null;

	private $hosts;
	private $index;
	private $type;
	private $dateformat;
	private $username;
	private $password;
	private $tls;
	private $timeout;

	public function client() { return $this->_client; }
	public function getIndex() { return $this->index; }
	public function getType() { return $this->type; }
	public function getDateformat() { return $this->dateformat; }

	public function __construct($hosts, $index, $type, $dateformat, $username = null, $password = null, $tls = [], $timeout = null)
	{
		$this->hosts = $hosts;
		$this->index = $index;
		$this->type = $type;
		$this->dateformat = $dateformat;
		$this->username = $username;
		$this->password = $password;
		$this->tls = $tls;
		$this->timeout = is_numeric($timeout) ? $timeout : 5;

		try {
			$this->_client = ClientBuilder::create()->setHosts($this->hosts)->build();
		} catch(Exception $e) {
			die($e);
		}
	}
}
