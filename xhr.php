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

if ($_POST['page'] == 'rates')
{
	if (!$settings->getDisplayRateLimits())
		die(json_encode(array('error' => "The setting display-ratelimits isn't enabled")));
	if (!Session::Get()->checkAccessAll()) die(json_encode(array('error' => 'Insufficient permissions')));

	if ($_POST['list'] == 'clear')
	{
		$nodeBackend = new NodeBackend($settings->getNodes());
		$results = $nodeBackend->clearRate(['ns' => $_POST['ns'], 'entry' => $_POST['entry']], $errors);
		if ($errors)
			die(json_encode(array('error' => 'soap', 'value' => $errors)));
		die(json_encode(array('status' => 'ok')));
	}

	if (isset($_POST['rate']))
	{
		$ratelimits = $settings->getRateLimits();
		if (!isset($ratelimits[$_POST['rate']])) die(json_encode(array('error' => 'Invalid ratelimit')));
		$rate = $ratelimits[$_POST['rate']];

		$nodeBackend = new NodeBackend($settings->getNode(0));

		// Compensate for count being '> X' in the API
		if (isset($rate['count_min']))
			$rate['count_min'] = max($rate['count_min'] - 1, 0);

		$errors = array();
		if ($_POST['search'])
			$result = $nodeBackend->getRate(array('ns' => $rate['ns'], 'entry' => $_POST['search'], 'matchexact' => array('ns' => true, 'entry' => false)), $errors)[0];
		else
			$result = $nodeBackend->getRate(array('ns' => $rate['ns'], 'count' => $rate['count_min']), $errors)[0];

		if ($errors)
			die(json_encode(array('error' => 'soap', 'value' => $errors)));

		$items = array();

		if (count($result->result->item) > 0)
		{
			foreach ($result->result->item as $item) {
				$items[] = array(
						'entry' => $item->entry,
						'ns' => $item->ns,
						'count' => $item->count,
						'search_filter' => urlencode(str_replace('$entry', $item->entry, $rate['search_filter'])),
						);
			}
		}

		function cmp($a, $b) {
			return $b['count'] - $a['count'];
		}
		usort($items, 'cmp');

		$actions = array(
				'QUARANTINE' => array('color' => '#f70', 'icon' => 'inbox'),
				'REJECT' => array('color' => '#ba0f4b', 'icon' => 'ban'),
				'DELETE' => array('color' => '#333', 'icon' => 'trash-o'),
				'DEFER' => array('color' => '#b5b', 'icon' => 'clock-o'),
				);

		$action = strtoupper($rate['action']);
		if (!array_key_exists($action, $actions)) $action = '';

		$data = [];
		$data['action_type'] = $action;
		$data['action_icon'] = isset($actions[$action]['icon']) ? $actions[$action]['icon'] : 'exclamation';
		$data['action_color'] = isset($actions[$action]['color']) ? $actions[$action]['color'] : '#9d9d9d';
		$data['count_limit'] = intval($rate['count_limit']);
		$data['items'] = $items;

		die(json_encode($data));
	}
}

die(json_encode(array('error' => 'unsupported request')));
