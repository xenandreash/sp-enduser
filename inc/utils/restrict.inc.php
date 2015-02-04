<?php

// Check the user's access to a specific message in SQL database
function user_restrict_sql_mail($id, $actionid = NULL) {
	$restrict_sql = user_restrict_sql_query();
	list($real_sql, $real_sql_params) = restrict_sql_mail($restrict_sql, $id, $actionid);
	$settings = Settings::Get();
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare($real_sql);
	$statement->execute($real_sql_params);
	$mail = $statement->fetchObject();
	if (!$mail) die('Invalid mail');
	return $mail;
}

// Check the user's access to a specific message over SOAP/HQL
function user_restrict_soap_mail($type, $node, $id, $die = true) {
	try {
		$client = soap_client(intval($node));
		$soaptype = $type;
		if ($type == 'historyqueue')
			$soaptype = 'history';
		$restrict = user_restrict_soap_query($soaptype);
		$res = restrict_soap_mail($restrict, $type, $id, $client);
	} catch (Exception $e) {
		// XXX Very important; many depend on us to die if access is denied
		if ($die)
			die('Invalid mail');
		else
			throw new Exception('Invalid mail');

	}
	return $res;
}

// Returns the SOAP HQL syntax for logged-in users access rights
function user_restrict_soap_query($type = 'queue') {
	$settings = Settings::Get();
	$access = Session::Get()->getAccess();
	return restrict_soap_query($settings, $access, $type);
}

// Returns a "param-ized" SQL filter for logged-in users access rights
function user_restrict_sql_query() {
	$settings = Settings::Get();
	$access = Session::Get()->getAccess();
	return restrict_sql_query($settings, $access);
}

// Currently only used by pages/index, exists because UNION/LIMIT is needed for OR query performance
function user_restrict_sql_select($select, $where, $order, $limit, $offsets) {
	$settings = Settings::Get();
	$access = Session::Get()->getAccess();
	restrict_sql_select($settings, $access, $select, $where, $order, $limit, $offsets);
}

// XXX below are testable functions

// Check the user's access to a specific message in SQL database
// Testable using custom $client
function restrict_soap_mail($restrict, $type, $id, $client)
{
	$query = array();
	$query['filter'] = $restrict;
	$query['offset'] = 0;
	$query['limit'] = 2; // security measure; if two msgs are matched
	if ($type == 'history') {
		$query['filter'] .= ' && historyid='.intval($id);
		$res = $client->mailHistory($query);
	} else if ($type == 'historyqueue') {
		$query['filter'] .= ' && queueid='.intval($id);
		$res = $client->mailHistory($query);
	} else {
		$query['filter'] .= ' && queueid='.intval($id);
		$res = $client->mailQueue($query);
	}

	if (count($res->result->item) != 1)
		throw new Exception('Invalid mail');

	return $res->result->item[0];
}

// Check the user's access to a specific message in SQL database
// Testable by verifying return value
function restrict_sql_mail($restrict_sql, $id, $actionid = NULL)
{
	$filters = array();
	$real_sql = 'SELECT *, UNIX_TIMESTAMP(msgts0) AS msgts0 FROM messagelog';
	$real_sql_params = $restrict_sql['params'];
	if ($restrict_sql['filter'])
		$filters[] = $restrict_sql['filter'];
	if (!$actionid) {
		$real_sql_params[':id'] = intval($id);
		$filters[] = 'id = :id';
	} else {
		$real_sql_params[':id'] = intval($id);
		$real_sql_params[':actionid'] = intval($actionid);
		$filters[] = 'msgid = :id';
		$filters[] = 'msgactionid = :actionid';
	}
	// extremely important to use "(...) AND (...)" for access control
	if (count($filters))
		$real_sql .= ' WHERE ('.implode(') AND (', $filters).')';
	return array($real_sql, $real_sql_params);
}

// Returns the SOAP HQL syntax for $access's access rights
function restrict_soap_query($settings, $access, $type = 'queue')
{
	$globalfilter = "";
	if (count($settings->getQuarantineFilter()) > 0 && $type != 'history')
	{
		foreach ($settings->getQuarantineFilter() as $q)
		{
			if ($globalfilter != "")
				$globalfilter .= " or ";
			$globalfilter .= "quarantine=$q";
		}
		$globalfilter .= ' or not action=QUARANTINE ';
	}

	$pattern = $settings->getFilterPattern();

	$filter = "";
	if (is_array($access['domain'])) {
		foreach ($access['domain'] as $domain) {
			if ($filter != "")
				$filter .= " or ";
			$filter .= str_replace(array('{from}', '{to}'), array("from~%@$domain", "to~%@$domain"), $pattern);
		} 
	}

	if (is_array($access['mail'])) {
		foreach ($access['mail'] as $mail) {
			if ($filter != "")
				$filter .= " or ";
			$filter .= str_replace(array('{from}', '{to}'), array("from=$mail", "to=$mail"), $pattern);
		} 
	}
	return $globalfilter.($globalfilter?" && ":"").$filter;
}

// Returns a "param-ized" SQL filter for $access's access rights
function restrict_sql_query($settings, $access)
{
	$settings = Settings::Get();
	if (count($settings->getQuarantineFilter()) > 0)
		die('you cannot combine filter-pattern and local history');
	$filter = array();
	$params = array();
	$i = 0;
	$access = Session::Get()->getAccess();
	if (is_array($access['domain'])) {
		foreach ($access['domain'] as $domain) {
			$i++;
			$filter[] = 'owner_domain = :restrict'.$i;
			$params[':restrict'.$i] = $domain;
		}
	}
	if (is_array($access['mail'])) {
		foreach ($access['mail'] as $mail) {
			$i++;
			$filter[] = 'owner = :restrict'.$i;
			$params[':restrict'.$i] = $mail;
		}
	}
	return array('filter' => implode(' or ', $filter), 'params' => $params);
}

// Currently only used by pages/index, exists because UNION/LIMIT is needed for OR query performance
function restrict_sql_select($settings, $access, $select, $where, $order, $limit, $offsets)
{
	if ($settings->getFilterPattern() === null)
		throw new Exception('you cannot combine filter-pattern and local sql history');

	$params = $where['params'];
	$i = 0;

	// summarize all accesses in one array
	$accesses = array();
	if (is_array($access['domain'])) {
		foreach ($access['domain'] as $domain) {
			$i++;
			$accesses[] = 'owner_domain = :restrict'.$i;
			$params[':restrict'.$i] = $domain;
		}
	}
	if (is_array($access['mail'])) {
		foreach ($access['mail'] as $mail) {
			$i++;
			$accesses[] = 'owner = :restrict'.$i;
			$params[':restrict'.$i] = $mail;
		}
	}
	// no access? add special "full access" item
	if (count($accesses) == 0)
		$accesses[] = '';

	// create UNION of all accesses (in order to efficiently use LIMIT)
	$unions = array();
	foreach ($accesses as $i => $a) {
		$tmp_sql = 'SELECT *, '.$i.' AS union_id, ';
		$tmp_sql .= $select;
		$tmp_where = array_filter(array($a, $where['filter']));
		// important to use "(...) AND (...)" for access control
		if (!empty($tmp_where))
			$tmp_sql .= ' WHERE ('.implode(') AND (', $tmp_where).')';
		$tmp_sql .= ' '.$order;
		$tmp_sql .= ' LIMIT '.intval($limit);
		$tmp_sql .= ' OFFSET '.intval($offsets[$i]['offset']);
		$unions[] = $tmp_sql;
	}
	$sql = '('.implode(') UNION (', $unions).')';
	return array('sql' => $sql, 'params' => $params);
}
