<?php
if (!defined('SP_ENDUSER')) die('File not included');
if (!$settings->getDisplayUsers() || Session::Get()->checkDisabledFeature('display-users'))
	die("The setting display-users isn't enabled");
if (!Session::Get()->checkAccessAll())
	die('Insufficient permissions');

$dbh = $settings->getDatabase();

$result = array();
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
$pagesize = array(25, 50, 100, 500, 1000);

$total = null;
$search = $_GET['search'];
$foundrows = $having = $concat = '';
$havings = array();
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
	$foundrows = 'SQL_CALC_FOUND_ROWS';
if ($search != '')
	$havings[] = 'users.username LIKE :search';
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql') {
	$concat = "string_agg(users_relations.access, ',')";
} else {
	$concat = 'GROUP_CONCAT(users_relations.access)';
}

if (count($havings))
	$having = 'HAVING '.implode(' AND ', $havings);
$sql = "SELECT $foundrows users.username, $concat AS accesses FROM users LEFT JOIN users_relations ON users.username = users_relations.username GROUP BY users.username $having ORDER BY users.username ASC LIMIT :limit OFFSET :offset;";
$statement = $dbh->prepare($sql);
$statement->bindValue(':limit', (int)$limit + 1, PDO::PARAM_INT);
$statement->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
if ($search != '')
	$statement->bindValue(':search', '%'.$search.'%');
$statement->execute();
while ($row = $statement->fetch(PDO::FETCH_ASSOC))
	$result[] = $row;

if ($offset > 0 and !count($result)) {
	$redirect = $_SERVER['PHP_SELF'].'?page='.$_GET['page'];
	if (isset($_GET['limit'])) $redirect .= "&limit=$limit";
	if ($search) $redirect .= "&search=$search";
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
		$total = $dbh->prepare("SELECT COUNT(*) FROM (SELECT users.username, $concat AS accesses FROM users LEFT JOIN users_relations ON users.username = users_relations.username GROUP BY users.username) AS subquery;");
		if ($search != '')
			$total->bindValue(':search', '%'.$search.'%');
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
	$result2[$row['username']] = array();
	$row_accesses = explode(',', $row['accesses']);
	foreach ($row_accesses as $row_access) {
		if ($row_access != "") $result2[$row['username']][] = $row_access;
	}
}

$javascript[] = 'static/js/users.js';

require_once BASE.'/inc/smarty.php';

$smarty->assign('page_active', $_GET['page']);
if ($search) $smarty->assign('search', $search);
$smarty->assign('items', $result2);
if ($total) $smarty->assign('total', $total);
if ($pages) $smarty->assign('pages', $pages);
$smarty->assign('currpage', $currpage);
$smarty->assign('limit', $limit);
$smarty->assign('offset', $offset);
$smarty->assign('pagesizes', $pagesize);
if ($pagemore) $smarty->assign('pagemore', true);

$smarty->display('users.tpl');
