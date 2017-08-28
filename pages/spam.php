<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplaySpamSettings()) die("The setting display-spamsettings isn't enabled");

$dbh = $settings->getDatabase();

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
$pagesize = array(25, 50, 100, 500, 1000);

$total = null;
$access = Session::Get()->getAccess();
$search = [];
if (!empty($_GET['search'])) $search['recipient'] = $_GET['search'];
if (!empty($_GET['level'])) $search['level'] = $_GET['level'];
$in_access = $domain_access = array();
foreach (array_merge((array)$access['mail'], (array)$access['domain']) as $k => $v)
	$in_access[':access'.$k] = $v;
foreach ((array)$access['domain'] as $k => $v)
	$domain_access[':domain'.$k] = '%@'.$v;
$foundrows = $where = '';
$wheres = array();
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
	$foundrows = 'SQL_CALC_FOUND_ROWS';
if (isset($search['recipient']))
	$wheres[] = 'access LIKE :search';
if (isset($search['level']))
	$wheres[] = 'settings = :level';
if (count($access) != 0) {
	$restrict = '(access IN ('.implode(',', array_keys($in_access)).')';
	foreach (array_keys($domain_access) as $v)
		$restrict .= ' OR access LIKE '.$v;
	$restrict .= ')';
	$wheres[] = $restrict;
}

if (count($wheres))
	$where = 'WHERE '.implode(' AND ', $wheres);
$sql = "SELECT $foundrows * FROM spamsettings $where ORDER BY access ASC LIMIT :limit OFFSET :offset;";
$statement = $dbh->prepare($sql);
$statement->bindValue(':limit', (int)$limit + 1, PDO::PARAM_INT);
$statement->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
if (isset($search['recipient']))
	$statement->bindValue(':search', '%'.$search['recipient'].'%');
if (isset($search['level']))
	$statement->bindValue(':level', json_encode(["level" => $search['level']]));
foreach ($in_access as $k => $v)
	$statement->bindValue($k, $v);
foreach ($domain_access as $k => $v)
	$statement->bindValue($k, $v);
$statement->execute();
$result = array();
while ($row = $statement->fetch(PDO::FETCH_ASSOC))
	$result[] = array('access' => $row['access'], 'settings' => json_decode($row['settings']));

if ($offset > 0 and !count($result)) {
	$redirect = $_SERVER['PHP_SELF'].'?page='.$_GET['page'];
	if (isset($_GET['limit'])) $redirect .= "&limit=$limit";
	if (isset($search['recipient'])) $redirect .= "&search=".$search['recipient'];
	if (isset($search['level'])) $redirect .= "&level=".$search['level'];
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
		$total = $dbh->prepare("SELECT COUNT(*) FROM spamsettings $where;");
		if (isset($search['recipient']))
			$total->bindValue(':search', '%'.$search['recipient'].'%');
		if (isset($search['level']))
			$total->bindValue(':level', json_encode(["level" => $search['level']]));
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

$javascript[] = 'static/js/spam.js';

require_once BASE.'/inc/smarty.php';

$smarty->assign('page_active', $_GET['page']);
if ($search) $smarty->assign('search', $search);
$access = array();
foreach (Session::Get()->getAccess() as $a)
	$access = array_merge($access, $a);
$smarty->assign('useraccess', $access);
$smarty->assign('items', $result);
if ($total) $smarty->assign('total', $total);
if ($pages) $smarty->assign('pages', $pages);
$smarty->assign('currpage', $currpage);
$smarty->assign('limit', $limit);
$smarty->assign('offset', $offset);
$smarty->assign('pagesizes', $pagesize);
if ($pagemore) $smarty->assign('pagemore', true);

$smarty->display('spam.tpl');
