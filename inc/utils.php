<?php
// This should already be included, but reinclude it anyways just to be sure
// that the autoloader and settings are initialized properly
require_once BASE.'/inc/core.php';

// Functions are grouped into smaller files for navigability
require_once BASE.'/inc/utils/hql.inc.php';
require_once BASE.'/inc/utils/soap.inc.php';
require_once BASE.'/inc/utils/soap_async.inc.php';
require_once BASE.'/inc/utils/mail.inc.php';
require_once BASE.'/inc/utils/view.inc.php';

function build_query_restrict($type = 'queue')
{
	$settings = Settings::Get();
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
	$access = Session::Get()->getAccess();
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

function restrict_mail($type, $node, $id, $die = true)
{
	$client = soap_client(intval($node));
	$query = array();
	$query['offset'] = 0;
	$query['limit'] = 2; // security measure; if two msgs are matched
	$query['filter'] = build_query_restrict();
	if ($type == 'history') {
		$query['filter'] .= ' && historyid='.intval($id);
		$res = $client->mailHistory($query);
	} else {
		$query['filter'] .= ' && queueid='.intval($id);
		$res = $client->mailQueue($query);
	}
	// XXX Very important; many depend on us to die if access is denied
	if (count($res->result->item) != 1) {
		if($die) die('Invalid mail');
		else throw new Exception('Invalid mail');
	}
	return $res->result->item[0];
}

// Currently only used by pages/index, exists because UNION/LIMIT is needed for OR query performance
function build_query_restrict_select($select, $where, $order, $limit, $offsets)
{
	$settings = Settings::Get();
	if ($settings->getFilterPattern() === null)
		die('you cannot combine filter-pattern and local sql history');

	$params = $where['params'];
	$i = 0;

	// summarize all accesses in one array
	$accesses = array();
	$access = Session::Get()->getAccess();
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

function build_query_restrict_local()
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

function restrict_local_mail($id)
{
	$settings = Settings::Get();
	$filters = array();
	$real_sql = 'SELECT *, UNIX_TIMESTAMP(msgts0) AS msgts0 FROM messagelog';
	$restrict_sql = build_query_restrict_local();
	$real_sql_params = $restrict_sql['params'];
	if ($restrict_sql['filter'])
		$filters[] = $restrict_sql['filter'];
	$real_sql_params[':id'] = intval($id);
	$filters[] = 'id = :id';
	// extremely important to use "(...) AND (...)" for access control
	if (count($filters))
		$real_sql .= ' WHERE ('.implode(') AND (', $filters).')';
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare($real_sql);
	$statement->execute($real_sql_params);
	$mail = $statement->fetchObject();
	if (!$mail) die('Invalid mail');
	return $mail;
}

function history_parse_scores($mail)
{
	$rpd = array();
	$rpd[0] = 'Unknown';
	$rpd[10] = 'Suspect';
	$rpd[40] = 'Valid bulk';
	$rpd[50] = 'Bulk';
	$rpd[100] = 'Spam';
	$ret = array();
	// SOAP score structure
	if (isset($mail->msgscore->item)) foreach ($mail->msgscore->item as $score) {
		list($num, $text) = explode('|', $score->second);
		if ($score->first == 0) {
			$ret['sa']['name'] = 'SpamAssassin';
			$ret['sa']['score'] = $num;
			$ret['sa']['text'] = str_replace(',', ', ', $text);
		}
		if ($score->first == 1) {
			$res = 'Ok';
			if ($text)
				$res = 'Virus';
			$ret['kav']['name'] = 'Kaspersky';
			$ret['kav']['score'] = $res;
			$ret['kav']['text'] = $text;
		}
		if ($score->first == 3) {
			$ret['rpd']['name'] = 'CYREN';
			$ret['rpd']['score'] = $rpd[$num];
			$ret['rpd']['text'] = $text;
		}
		if ($score->first == 4) {
			$res = 'Ok';
			if ($text)
				$res = 'Virus';
			$ret['clam']['name'] = 'ClamAV';
			$ret['clam']['score'] = $res;
			$ret['clam']['text'] = $text;
		}
	}
	// Local (SQL) log scores, from searchable columns
	if (isset($mail->score_sa) && $mail->score_sa) {
		$ret['sa']['name'] = 'SpamAssassin';
		$ret['sa']['score'] = floatval($mail->score_sa);

	}
	if (isset($mail->score_rpd) && $mail->score_rpd !== null) {
		$ret['rpd']['name'] = 'CYREN';
		$ret['rpd']['score'] = $rpd[intval($mail->score_rpd)];
	}
	// Local (SQL) log scores, from JSON blob
	if (isset($mail->scores) && $scores = json_decode($mail->scores, true)) {
		if (isset($scores['rpd'])) {
			$ret['rpd']['name'] = 'CYREN';
			$ret['rpd']['text'] = $scores['rpd'];
		}
		if (is_array($scores['sa'])) {
			$ret['sa']['name'] = 'SpamAssassin';
			$sas = array();
			foreach ($scores['sa'] as $key => $value)
				$sas[] = $key.'='.$value;
			$ret['sa']['text'] = implode(', ', $sas);
		}
		if ($scores['kav'] !== null) {
			$ret['kav']['name'] = 'Kaspersky';
			$viruses = $scores['kav'];
			if (is_array($viruses)) {
				$ret['kav']['score'] = 'Virus';
				$ret['kav']['text'] = implode(', ', $viruses);
			} else $ret['kav']['score'] = 'Ok';
		}
		if ($scores['clam'] !== null) {
			$ret['clam']['name'] = 'ClamAV';
			$viruses = $scores['clam'];
			if (is_array($viruses)) {
				$ret['clam']['score'] = 'Virus';
				$ret['clam']['text'] = implode(', ', $viruses);
			} else $ret['clam']['score'] = 'Ok';
		}
		if ($scores['rpdav'] != '') {
			$ret['rpdav']['name'] = 'RPDAV';
			if ($scores['rpdav'] == 0)
				$ret['rpdav']['score'] = 'Ok';
			if ($scores['rpdav'] == 50)
				$ret['rpdav']['score'] = 'Suspect';
			if ($scores['rpdav'] == 100)
				$ret['rpdav']['score'] = 'Virus';
		}
	}
	return $ret;
}

function generate_random_password()
{
	$pass = '';
	if (function_exists('openssl_random_pseudo_bytes'))
	{
		$pass = bin2hex(openssl_random_pseudo_bytes(32));
	}
	else
	{
		// The effective security of this is horrid, but unfortunately we can't
		// depend on OpenSSL being available on Windows
		$chars = '0123456789abcdef';
		srand((float) microtime() * 10000000);
		for ($i = 0; $i < 64; $i++) {
			$pass .= $chars[rand(0, strlen($chars)-1)];
		}
	}
	
	return $pass;
}

function ldap_escape($data)
{
	return str_replace(array('\\', '*', '(', ')', '\0'), array('\\5c', '\\2a', '\\28', '\\29', '\\00'), $data);
}

function has_auth_database() {
	$settings = Settings::Get();
	foreach ($settings->getAuthSources() as $a)
		if ($a['type'] == 'database')
			return true;
	return false;
}

/**
 * Merges two two-dimensional arrays together.
 * 
 * For example, this code:
 * 
 * $arr1 = array(
 *     'a' => array($a1, $a2),
 *     'b' => array($b1),
 * );
 * $arr2 = array(
 *     'a' => array($a3),
 *     'c' => array($c1),
 * );
 * $arr = merge_2d($arr1, $arr2);
 * 
 * Would result in:
 * 
 * $arr = array(
 *     'a' => array($a1, $a2, $a3),
 *     'b' => array($b1),
 *     'c' => array($c1),
 * );
 * 
 * While array_merge would produce:
 * 
 * $arr = array(
 *     'a' => array($a3),
 *     'b' => array($b1),
 *     'c' => array($c1),
 * );
 */
function merge_2d($a1, $a2) {
	foreach ($a2 as $k => $v) {
		if (!in_array($k, $a1, true)) {
			$a1[$k] = $v;
		} else {
			$a1[$k] = array_merge($a1[$k], $v);
		}
	}
	
	return $a1;
}

function mkquery($a1, $a2 = array(), $amps = false) {
	$arr = array_filter(array_merge($a1, $a2));
	return http_build_query($arr, '', ($amps ? '&amp;' : '&'));
}
