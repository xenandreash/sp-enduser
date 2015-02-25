<?php

// Returns a "param-ized" SQL filter for logged-in users access rights
function restrict_sql_query() {
	$settings = Settings::Get();
	$access = Session::Get()->getAccess();
	return _restrict_sql_query($settings, $access);
}

// Currently only used by pages/index, exists because UNION/LIMIT is needed for OR query performance
function restrict_sql_select($select, $where, $order, $limit, $offsets) {
	$settings = Settings::Get();
	$access = Session::Get()->getAccess();
	return _restrict_sql_select($settings, $access, $select, $where, $order, $limit, $offsets);
}

// XXX below are testable functions

// Check the user's access to a specific message in SQL database
// Testable by verifying return value
function _restrict_sql_mail($restrict_sql, $id)
{
	$filters = array();
	$real_sql = 'SELECT *, UNIX_TIMESTAMP(msgts0) AS msgts0 FROM messagelog';
	$real_sql_params = $restrict_sql['params'];
	if ($restrict_sql['filter'])
		$filters[] = $restrict_sql['filter'];
	$real_sql_params[':id'] = intval($id);
	$filters[] = 'id = :id';
	// extremely important to use "(...) AND (...)" for access control
	if (count($filters))
		$real_sql .= ' WHERE ('.implode(') AND (', $filters).')';
	return array($real_sql, $real_sql_params);
}

// Returns a "param-ized" SQL filter for $access's access rights
function _restrict_sql_query($settings, $access)
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
function _restrict_sql_select($settings, $access, $select, $where, $order, $limit, $offsets)
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
