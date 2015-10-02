<?php
if (!defined('SP_ENDUSER')) die('File not included');

$session_name = $settings->getSessionName();
if ($session_name)
	session_name($session_name);
session_start();
session_destroy();
header("Location: .");
