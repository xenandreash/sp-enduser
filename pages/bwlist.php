<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayBWlist()) die("The setting display-bwlist isn't enabled");

$dbh = $settings->getDatabase();

$result = array();
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
$pagesize = array(25, 50, 100, 500, 1000);

$total = null;
$access = Session::Get()->getAccess();
$search = array();
if (!empty($_GET['action'])) $search['action'] = $_GET['action'];
if (!empty($_GET['sender'])) $search['sender'] = $_GET['sender'];
if (!empty($_GET['recipient'])) $search['recipient'] = $_GET['recipient'];
$in_access = $domain_access = array();
foreach (array_merge((array)$access['mail'], (array)$access['domain']) as $k => $v)
	$in_access[':access'.$k] = $v;
foreach ((array)$access['domain'] as $k => $v)
	$domain_access[':domain'.$k] = '%@'.$v;
$foundrows = $where = '';
$wheres = array();
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
	$foundrows = 'SQL_CALC_FOUND_ROWS';
if (isset($search['action']))
	$wheres[] = 'type = :action';
if (isset($search['sender']))
	$wheres[] = 'value LIKE :sender';
if (isset($search['recipient']))
	$wheres[] = 'access LIKE :recipient';
if (!Session::Get()->checkAccessAll()) {
	$restrict = '(access IN ('.implode(',', array_keys($in_access)).')';
	foreach (array_keys($domain_access) as $v)
		$restrict .= ' OR access LIKE '.$v;
	$restrict .= ')';
	$wheres[] = $restrict;
}

if (count($wheres))
	$where = 'WHERE '.implode(' AND ', $wheres);
$sql = "SELECT $foundrows * FROM bwlist $where ORDER BY type DESC, value ASC LIMIT :limit OFFSET :offset;";
$statement = $dbh->prepare($sql);
$statement->bindValue(':limit', (int)$limit + 1, PDO::PARAM_INT);
$statement->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
if (isset($search['action']))
	$statement->bindValue(':action', $search['action']);
if (isset($search['sender']))
	$statement->bindValue(':sender', '%'.$search['sender'].'%');
if (isset($search['recipient']))
	$statement->bindValue(':recipient', '%'.$search['recipient'].'%');
foreach ($in_access as $k => $v)
	$statement->bindValue($k, $v);
foreach ($domain_access as $k => $v)
	$statement->bindValue($k, $v);
$statement->execute();
while ($row = $statement->fetch(PDO::FETCH_ASSOC))
	$result[] = $row;

if ($offset > 0 and !count($result)) {
	$redirect = $_SERVER['PHP_SELF'].'?page='.$_GET['page'];
	if (isset($_GET['limit'])) $redirect .= "&limit=$limit";
	if (isset($search['action'])) $redirect .= '&action='.$search['action'];
	if (isset($search['sender'])) $redirect .= '&sender='.$search['sender'];
	if (isset($search['recipient'])) $redirect .= '&recipient='.$search['recipient'];
	header("Location: $redirect");
	die();
}

if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
	$total = $dbh->query('SELECT FOUND_ROWS();');
	$total = (int)$total->fetchColumn();
}
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite' || $dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
	if ($offset == 0 && count($result) < $limit + 1) {
		$total = count($result);
	} else {
		$total = $dbh->prepare("SELECT COUNT(*) FROM bwlist $where;");
		if (isset($search['action']))
			$total->bindValue(':action', $search['action']);
		if (isset($search['sender']))
			$total->bindValue(':sender', '%'.$search['sender'].'%');
		if (isset($search['recipient']))
			$total->bindValue(':recipient', '%'.$search['recipient'].'%');
		foreach ($in_access as $k => $v)
			$total->bindValue($k, $v);
		foreach ($domain_access as $k => $v)
			$total->bindValue($k, $v);
		$total->execute();
		$total = (int)$total->fetchColumn();
	}
}
$pagemore = count($result) > $limit;
if ($pagemore)
	array_pop($result);
if ($total) {
	$currpage = intval($offset/$limit);
	$lastpage = intval(($total-1)/$limit);
	$pages = range(0, $lastpage);
	if (count($pages) == 1) $pages = array();
	if ($lastpage > 10) {
		// start or end (first4 ... last4)
		$pages = array_merge(range(0, 2), array('...', intval($lastpage/2), '...'), range($lastpage - 2, $lastpage));
		// middle (first .. middle5 .. last)
		if ($currpage > 2 && $currpage < ($lastpage - 2))
			$pages = array_merge(array(0, '...'), range($currpage - 2, $currpage + 2), array('...', $lastpage));
		// beginning (first5 ... last3)
		if ($currpage > 1 && $currpage < 4)
			$pages = array_merge(range(0, 4), array('...'), range($lastpage - 2, $lastpage));
		// ending (first3 .. last5)
		if ($currpage > ($lastpage - 4) && $currpage < ($lastpage - 1))
			$pages = array_merge(range(0, 2), array('...'), range($lastpage - 4, $lastpage));
	}
}

// For users with many access levels; print them more condensed
$result2 = array();
foreach ($result as $row) {
	$i = 0;
	if (isset($result2[$row['type']][$row['value']]))
		$i = count($result2[$row['type']][$row['value']]['accesses']);
	$result2[$row['type']][$row['value']]['accesses'][$i] = $row['access'];
	$result2[$row['type']][$row['value']]['comments'][$i] = $row['comment'];
}

$javascript[] = 'static/js/bwlist.js';

require_once BASE.'/inc/smarty.php';

$smarty->assign('page_active', $_GET['page']);
if ($search) $smarty->assign('search', $search);
$access = array();
foreach (Session::Get()->getAccess() as $a)
	$access = array_merge($access, $a);
$smarty->assign('useraccess', $access);
$smarty->assign('items', $result2);
if ($total) $smarty->assign('total', $total);
if ($pages) $smarty->assign('pages', $pages);
$smarty->assign('currpage', $currpage);
$smarty->assign('limit', $limit);
$smarty->assign('offset', $offset);
$smarty->assign('pagesizes', $pagesize);
if ($pagemore) $smarty->assign('pagemore', true);

$smarty->display('bwlist.tpl');
