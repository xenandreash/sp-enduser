<?php

// This usually is the first file (for security), set error stuff
error_reporting(E_ALL ^ E_NOTICE);
session_start();

if (!isset($_SESSION['username'])) {
	header("Location: login.php");
	die();
}
?>
