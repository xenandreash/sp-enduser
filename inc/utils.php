<?php
require_once BASE.'/vendor/autoload.php';
require_once BASE.'/inc/core.php';
require_once BASE.'/inc/soap.php';

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

function restrict_mail($type, $node, $id)
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
	if (count($res->result->item) != 1)
		die('Invalid mail');
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
	$dbh = new Database();
	$statement = $dbh->prepare($real_sql);
	$statement->execute($real_sql_params);
	$mail = $statement->fetchObject();
	if (!$mail) die('Invalid mail');
	return $mail;
}

function soap_client($n, $async = false, $username = null, $password = null) {
	$settings = Settings::Get();
	$r = $settings->getNodes()[$n];
	if (!$r)
		throw new Exception("Node not configured");
	
	if(!$username)
		$username = isset($_SESSION['soap_username']) ? $_SESSION['soap_username'] : $r['username'];
	if(!$password)
		$password = isset($_SESSION['soap_password']) ? $_SESSION['soap_password'] : $r['password'];
	
	$options = array(
		'location' => $r['address'].'/remote/',
		'uri' => 'urn:halon',
		'login' => $username,
		'password' => $password,
		'connection_timeout' => 15,
		'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
		'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP
		);
	if ($async)
		return new SoapClientAsync($r['address'].'/remote/?wsdl', $options);
	return new SoapClient($r['address'].'/remote/?wsdl', $options);
}

function soap_exec($argv, $c)
{
	$data = '';
	try {
		$id = $c->commandRun(array('argv' => $argv, 'cols' => 80, 'rows' => 24))->result;
		do {
			$result = $c->commandPoll(array('commandid' => $id))->result;
			if ($result && @$result->item)
				$data .= implode("", $result->item);
		} while (true);
	} catch (SoapFault $f) {
		if (!$id)
			return false;
	}
	return $data;
}

function p($str) {
	echo htmlspecialchars($str);
}

function p_select($name, $selected, $options) {
	echo '<select id="'.$name.'" name="'.$name.'">';
	foreach ($options as $value => $label) {
		$extra = '';
		if ((string)$value == $selected)
			$extra = ' selected';
		echo '<option value="'.$value.'"'.$extra.'>'.$label.'</option>';
	}
	echo '</select>';
}

function hql_transform($string)
{
	$string = trim($string);
	if ($string == "")
		return "";
	$messageid = "[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}";
	if (preg_match("/^($messageid)$/", $string, $result))
		return "messageid={$result[1]}";
	if (preg_match("/^($messageid):([0-9]+)$/", $string, $result))
		return "messageid={$result[1]} and queueid={$result[2]}";
	if (preg_match("/^([0-9]+)$/", $string, $result))
		return "queueid={$result[1]}";
	if (@inet_pton($string) !== false)
		return "ip=$string";
	if (!preg_match("/[=~><]/", $string)) {
		/* contain a @ either in the beginning or somewhere within */
		$mail = strpos($string, "@");
		if ($mail !== false)
		{
			if ($mail > 0)
				return "from=$string or to=$string";
			else
				return "from~$string or to~$string";
		}
		/* looks like a domain */
		if (preg_match("/^[a-z0-9-]+\.[a-z]{2,5}/", $string))
				return "from~%@$string or to~%@$string";
		/* add quotes */
		if (strpos($string, " ") !== false)
			$string = '"'.$string.'"';
		/* try as subject */
		return "subject~$string";
	}
	return $string;
}

// this function only implements a subset of HQL
function hql_to_sql($str, $prefix = 'hql')
{
	// allowed HQL fields, need to exist in messagelog table
	$fields = array();
	$fields['messageid'] = 'msgid';
	$fields['from'] = 'msgfrom';
	$fields['to'] = 'msgto';
	$fields['subject'] = 'msgsubject';
	$fields['ip'] = 'msgfromserver';
	$fields['action'] = 'msgaction';
	$fields['transport'] = 'msgtransport';
	$fields['server'] = 'msglistener';
	$fields['sasl'] = 'msgsasl';
	$fields['rpdscore'] = 'score_rpd';
	$fields['sascore'] = 'score_sa';
	$fields['time'] = 'UNIX_TIMESTAMP(msgts0)'; // XXX MySQL only

	preg_match_all('/\s*([a-z]+([=~><])("(\"|[^"])*?"|[^\s]*)|and|or|not|&&)\s*/', $str, $parts);
	$parts = $parts[1]; // because of the regex above, index 1 contains what we want
	$filter = '';
	$params = array();
	$i = 0;
	$ftok = 0; // filter token
	foreach ($parts as $p) {
		if ($p == 'and') { if ($ftok != 1) die('no filter condition before and'); $filter .= 'AND '; $ftok = 0; }
		else if ($p == 'or') { if ($ftok != 1) die('no filter condition before or'); $filter .= 'OR '; $ftok = 0; }
		else if ($p == 'not') { if ($ftok == 1) $filter .= 'AND '; $filter .= 'NOT '; $ftok = 0; }
		else if (preg_match('/^([a-z]+)([=~><])(.*?)$/', $p, $m)) {
			if ($ftok == 1) $filter .= 'AND ';
			$i++;
			list($tmp, $field, $type, $value) = $m;
			// unescape
			if ($value[0] == '"' && substr($value, -1) == '"') {
				$value = substr($value, 1, strlen($value) - 1);
				$value = str_replace('\"', '"', $value);
			}
			if (!isset($fields[$field])) die('unknown field '.htmlspecialchars($field));
			$field = $fields[$field];
			if ($type == '~') {
				$type = 'LIKE';
				if (strpos($value, '%') === false)
					$value = '%'.$value.'%';
			}
			// domain search from~%@
			if ($field == 'msgfrom' && substr($value, 0, 2) == '%@') {
				$field = 'msgfrom_domain';
				$value = substr($value, 2); // strip %@
				$type = '=';
			}
			// domain search to~%@
			if ($field == 'msgto' && substr($value, 0, 2) == '%@') {
				$field = 'msgto_domain'; // strip %@
				$value = substr($value, 2);
				$type = '=';
			}
			// fully rewrite fulltext search
			if ($field == 'msgsubject' && $type == 'LIKE') {
				$filter .= 'MATCH (msgsubject) AGAINST (:'.$prefix.$i.' IN BOOLEAN MODE)';
				$value = str_replace('%', ' ', $value); // remove all % in fulltext search
			} else {
				$filter .= $field.' '.$type.' :'.$prefix.$i.' ';
			}
			$params[':'.$prefix.$i] = $value;
			$ftok = 1;
		} else die('unexpected token '.htmlspecialchars($p));
	}
	if ($str && !$filter) die('invalid query');
	return array('filter' => $filter, 'params' => $params);
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
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	$pass = '';
	srand((float) microtime() * 10000000);
	for ($i = 0; $i < rand(10, 12); $i++) {
		$pass .= $chars[rand(0, strlen($chars)-1)];
	}
	return $pass;
}

function mail2($recipient, $subject, $message, $in_headers = null)
{
	$settings = Settings::Get();
	$headers = array();
	$headers[] = 'Message-ID: <'.uniqid().'@sp-enduser>';
	if ($settings->getMailSender())
		$headers[] = "From: ".$settings->getMailSender();
	if ($in_headers !== null)
		$headers = array_merge($headers, $in_headers);
	mail($recipient, $subject, $message, implode("\r\n", $headers));
}

function self_url()
{
	if (isset($_SERVER['HTTPS']))
		$protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
	else
		$protocol = 'http';
	$url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	return preg_replace("#[^/]*$#", "", $url);
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
