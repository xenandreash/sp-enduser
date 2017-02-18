<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayDataStore() || Session::Get()->checkDisabledFeature('display-datastore'))
	die("The setting display-datastore isn't enabled");
$access = Session::Get()->getAccess();
if (!Session::Get()->checkAccessAll() && count($access['domain']) == 0)
	die('Insufficient permissions');

$dbh = $settings->getDatabase();

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
$pagesize = array(25, 50, 100, 500, 1000);

$total = null;
$search = array();
if (isset($_GET['ns'])) $search['ns'] = $_GET['ns'];
if (isset($_GET['key'])) $search['key'] = $_GET['key'];
if (isset($_GET['value'])) $search['value'] = $_GET['value'];
foreach ((array)$access['domain'] as $k => $v)
	$in_access[':key'.$k] = $v;
$foundrows = $where = '';
$wheres = array();
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
	$foundrows = 'SQL_CALC_FOUND_ROWS';
if (isset($search['ns']))
	$wheres[] = 'namespace LIKE :namespace';
if (isset($search['key']))
	$wheres[] = 'keyname LIKE :key';
if (isset($search['value']))
	$wheres[] = 'value LIKE :value';
if (!Session::Get()->checkAccessAll()) {
	$restrict = 'keyname IN ('.implode(',', array_keys($in_access)).')';
	$wheres[] = $restrict;
}

if (count($wheres))
	$where = 'WHERE '.implode(' AND ', $wheres);
$sql = "SELECT $foundrows * FROM datastore $where ORDER BY namespace ASC, keyname ASC, value ASC LIMIT :limit OFFSET :offset;";
$statement = $dbh->prepare($sql);
$statement->bindValue(':limit', (int)$limit + 1, PDO::PARAM_INT);
$statement->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
if (isset($search['ns']))
	$statement->bindValue(':namespace', '%'.$search['ns'].'%');
if (isset($search['key']))
	$statement->bindValue(':key', '%'.$search['key'].'%');
if (isset($search['value']))
	$statement->bindValue(':value', '%'.$search['value'].'%');
foreach ((array)$in_access as $k => $v)
	$statement->bindValue($k, $v);
$statement->execute();
$result = array();
while ($row = $statement->fetch(PDO::FETCH_ASSOC))
	$result[] = array('namespace' => $row['namespace'], 'key' => $row['keyname'], 'value' => $row['value']);

if ($offset > 0 and !count($result)) {
	$redirect = $_SERVER['PHP_SELF'].'?page='.$_GET['page'];
	if (isset($_GET['limit'])) $redirect .= "&limit=$limit";
	if (isset($search['ns'])) $redirect .= '&ns='.$search['namespace'];
	if (isset($search['key'])) $redirect .= '&key='.$search['key'];
	if (isset($search['value'])) $redirect .= '&value='.$search['value'];
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
		$total = $dbh->prepare("SELECT COUNT(*) FROM datastore $where;");
		if (isset($search['ns']))
			$total->bindValue(':namespace', '%'.$search['ns'].'%');
		if (isset($search['key']))
			$total->bindValue(':key', '%'.$search['key'].'%');
		if (isset($search['value']))
			$total->bindValue(':value', '%'.$search['value'].'%');
		foreach ($in_access as $k => $v)
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

$javascript[] = 'static/js/datastore.js';

require_once BASE.'/inc/smarty.php';

$smarty->assign('page_active', $_GET['page']);
if (count($search)) $smarty->assign('search', $search);
$smarty->assign('items', $result);
if ($total) $smarty->assign('total', $total);
if ($pages) $smarty->assign('pages', $pages);
$smarty->assign('currpage', $currpage);
$smarty->assign('limit', $limit);
$smarty->assign('offset', $offset);
$smarty->assign('pagesizes', $pagesize);
if ($pagemore) $smarty->assign('pagemore', true);
if (Session::Get()->checkAccessAll()) $smarty->assign('full_access', true);

$smarty->display('datastore.tpl');
