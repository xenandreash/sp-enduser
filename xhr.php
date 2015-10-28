<?php

if (!defined('SP_ENDUSER')) die('File not included');
header('Content-Type: application/json; charset=UTF-8');

function checkAccess($perm)
{
	$access = Session::Get()->getAccess();
	if (count($access) == 0)
		return true;
	foreach ($access as $type)
		foreach ($type as $item)
			if ($item == $perm)
				return true;
	if (strpos($perm, '@') !== false)
		if (Session::Get()->checkAccessMail($perm))
			return true;
	return false;
}

if ($_POST['page'] == 'bwlist')
{
	if (!$settings->getDisplayBWlist())
		die(json_encode(array('error' => "The setting display-bwlist isn't enabled")));
	$dbh = $settings->getDatabase();

	if ($_POST['list'] == 'delete')
	{
		foreach (explode(',', $_POST['access']) as $a)
		{
			if (!checkAccess($a))
				die(json_encode(array('error' => 'permission', 'value' => $a)));

			$statement = $dbh->prepare("DELETE FROM bwlist WHERE access = :access AND bwlist.type = :type AND bwlist.value = :value;");
			$statement->execute(array(':access' => $a, ':type' => $_POST['type'], ':value' => $_POST['value']));
		}
		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'add')
	{
		$value = strtolower(trim($_POST['value']));
		if ($value[0] == '@') $value = substr($value, 1);

		$type = $_POST['type'];

		$added = false;
		foreach ($_POST['access'] as $access)
		{
			if (strpos($value, ' ') !== false)
				die(json_encode(array('error' => 'syntax', 'field' => 'value', 'reason' => 'Field contained whitespace')));
			if (strpos($access, ' ') !== false)
				die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'Field contained whitespace')));

			if ($access[0] == '@')
				$access = substr($access, 1);

			if (!checkAccess($access))
				die(json_encode(array('error' => 'permission', 'value' => $access)));

			if ($type == 'whitelist' || $type == 'blacklist') {
				$statement = $dbh->prepare("INSERT INTO bwlist (access, type, value) VALUES(:access, :type, :value);");
				$statement->execute(array(':access' => strtolower($access), ':type' => $type, ':value' => $value));
				$added = true;
			} else
				die(json_encode(array('error' => 'syntax', 'field' => 'type', 'reason' => 'Type not black or whitelist')));
		}
		if (!$added)
			die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'No recipients')));
		die(json_encode(array('status' => 'ok')));
	}
}

if ($_POST['page'] == 'spam')
{
	if (!$settings->getDisplaySpamSettings())
		die(json_encode(array('error' => "The setting display-spamsettings isn't enabled")));
	$dbh = $settings->getDatabase();

	if ($_POST['list'] == 'delete')
	{
		foreach (explode(',', $_POST['access']) as $a)
		{
			if (!checkAccess($a))
				die(json_encode(array('error' => 'permission', 'value' => $a)));

			$statement = $dbh->prepare("DELETE FROM spamsettings WHERE access = :access;");
			$statement->execute(array(':access' => $a));
		}
		die(json_encode(array('status' => 'ok')));
	}

	if ($_POST['list'] == 'add' || $_POST['list'] == 'edit')
	{
		$spamsettings = array();
		$spamsettings['level'] = $_POST['level'];

		$added = false;
		foreach ($_POST['access'] as $access)
		{
			if ($spamsettings['level'] == '')
				die(json_encode(array('error' => 'syntax', 'field' => 'level', 'reason' => 'No level selected')));
			if (strpos($access, ' ') !== false)
				die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'Field contained whitespace')));

			if ($access[0] == '@')
				$access = substr($access, 1);

			if (!checkAccess($access))
				die(json_encode(array('error' => 'permission', 'value' => $access)));

			if ($_POST['list'] == 'add') {
				$statement = $dbh->prepare("INSERT INTO spamsettings (access, settings) VALUES(:access, :settings);");
				$statement->execute(array(':access' => strtolower($access), ':settings' => json_encode($spamsettings)));
			}
			if ($_POST['list'] == 'edit') {
				$statement = $dbh->prepare("UPDATE spamsettings SET settings = :settings WHERE access = :access;");
				$statement->execute(array(':access' => strtolower($access), ':settings' => json_encode($spamsettings)));
			}
			$added = true;
		}
		if (!$added)
			die(json_encode(array('error' => 'syntax', 'field' => 'access', 'reason' => 'No recipients')));
		die(json_encode(array('status' => 'ok')));
	}
}

die(json_encode(array('error' => 'unsupported request')));
