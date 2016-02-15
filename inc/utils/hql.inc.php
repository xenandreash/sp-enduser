<?php

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
		if (preg_match("/^[A-Za-z0-9-]+\.[A-Za-z]{2,5}/", $string))
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
			// domain search from~%@
			if ($field == 'msgfrom' && substr($value, 0, 2) == '%@' && substr_count($value, '%') == 1) {
				$field = 'msgfrom_domain';
				$value = substr($value, 2); // strip %@
				$type = '=';
			}
			// domain search to~%@
			if ($field == 'msgto' && substr($value, 0, 2) == '%@' && substr_count($value, '%') == 1) {
				$field = 'msgto_domain'; // strip %@
				$value = substr($value, 2);
				$type = '=';
			}
			if ($type == '~') {
				$type = 'LIKE';
				if (strpos($value, '%') === false)
					$value = '%'.$value.'%';
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
