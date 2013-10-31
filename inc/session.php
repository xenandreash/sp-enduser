<?php

class Session
{
	private $username = null;
	private $source = null;
	private $access = null;
	public static function Get()
	{
		static $inst = null;
		if ($inst === null)
			$inst = new Session();
		return $inst;
	}
	private function __construct()
	{
		session_start();
		$this->username = $_SESSION['username'];
		$this->source = $_SESSION['source'];
		$this->access = $_SESSION['access'];
	}
	public function getUsername()
	{
		return $this->username;
	}
	public function getSource()
	{
		return $this->source;
	}
	public function getAccess()
	{
		return $this->access;
	}
}

if (Session::Get()->getUsername() === null) {
	session_destroy();
	header("Location: ?page=login");
	die();
}

?>
