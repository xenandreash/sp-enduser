<?php

class DatabaseBackend extends Backend
{
	private $database = null;
	
	public function __construct($db)
	{
		$this->database = $db;
	}
	
	// A database backend is valid if it has a database configured
	public function isValid() { return $this->database != NULL; }
	
	// Database backends support history; it's literally all they do
	public function supportsHistory() { return true; }
	
	public function loadMailHistory($search, $size, $param, &$errors = array())
	{
		// Note: this is ported straight out of index.php, improvements to come
		$results = array();
		
		// Create search/restrict query for SQL
		if ($this->database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
			$unix_time_sql = 'extract(epoch from msgts0)';
		} else {
			$unix_time_sql = 'UNIX_TIMESTAMP(msgts0)';
		}
		$sql_select = $unix_time_sql.' AS msgts0 FROM '.Session::Get()->getMessagelogTable();
		$sql_where = hql_to_sql($search, $this->database->getAttribute(PDO::ATTR_DRIVER_NAME));
		$real_sql = $this->restrict_select($sql_select, $sql_where, 'ORDER BY id DESC', intval($size + 1), $param);
		if (strpos($real_sql['sql'], ') UNION (') !== false) {
			$real_sql['sql'] .= ' ORDER BY id DESC';
		}
		
		// Fetch stuff
		try {
			$statement = $this->database->prepare($real_sql['sql']);
			$statement->execute($real_sql['params']);
			$dup = [];
			while ($item = $statement->fetchObject()) {
				if (isset($dup[$item->id]))
					continue;
				$dup[$item->id] = true;
				$results[$item->msgts0][] = array('id' => $item->union_id, 'type' => 'log', 'data' => $item);
			}
		} catch(Exception $e) {
			$errors[] = $e->getMessage();
		}
		
		return $results;
	}

	public function getMail($id)
	{
		$restrict_sql = $this->restrict_query();
		list($real_sql, $real_sql_params) = $this->restrict_mail($restrict_sql, $id);
		$statement = $this->database->prepare($real_sql);
		$statement->execute($real_sql_params);
		$mail = $statement->fetchObject();
		if (!$mail) return NULL;
		return $mail;
	}

	// Check the user's access to a specific message in SQL database
	// Testable by verifying return value
	private function restrict_mail($restrict_sql, $id)
	{
		$filters = array();
		if ($this->database->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
			$unix_time_sql = 'extract(epoch from msgts0)';
		} else {
			$unix_time_sql = 'UNIX_TIMESTAMP(msgts0)';
		}
		$real_sql = 'SELECT *, '.$unix_time_sql.' AS msgts0 FROM '.Session::Get()->getMessagelogTable();
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
	private function restrict_query()
	{
		$settings = Settings::Get();
		$access = Session::Get()->getAccess();

		if (count($settings->getQuarantineFilter()) > 0)
			die('you cannot combine filter-pattern and local history');
		$filter = array();
		$params = array();
		$i = 0;
		$access = Session::Get()->getAccess();
		if (isset($access['userid'])) {
			$i++;
			$filter[] = 'userid = :restrict'.$i;
			$params[':restrict'.$i] = $access['userid'];
		}
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
	private function restrict_select($select, $where, $order, $limit, $offsets)
	{
		$settings = Settings::Get();
		$access = Session::Get()->getAccess();

		if ($settings->getFilterPattern() === null)
			throw new Exception('you cannot combine filter-pattern and local sql history');

		$params = $where['params'];
		$i = 0;

		// summarize all accesses in one array
		$accesses = array();
		if (isset($access['userid'])) {
			$i++;
			$accesses[] = 'userid = :restrict'.$i;
			$params[':restrict'.$i] = $access['userid'];
		}
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
}
