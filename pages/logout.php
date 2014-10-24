<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once 'inc/core.php';

$session_name = settings('session-name');
if ($session_name)
	session_name($session_name);
session_start();
session_destroy();
header("Location: .");
die();

?>
