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
		$sql_select = 'UNIX_TIMESTAMP(msgts0) AS msgts0 FROM messagelog';
		$sql_where = hql_to_sql($search);
		$real_sql = restrict_sql_select($sql_select, $sql_where, 'ORDER BY id DESC', intval($size + 1), $param);
		$real_sql['sql'] .= ' ORDER BY id DESC LIMIT '.intval($size + 1); // don't send unnecessary
		
		// Fetch stuff
		try {
			$statement = $this->database->prepare($real_sql['sql']);
			$statement->execute($real_sql['params']);
			while ($item = $statement->fetchObject())
				$results[$item->msgts0][] = array('id' => $item->union_id, 'type' => 'log', 'data' => $item);
		} catch(Exception $e) {
			$errors[] = $e->getMessage();
		}
		
		return $results;
	}
}
