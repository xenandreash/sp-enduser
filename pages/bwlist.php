<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';

function checkAccess($perm)
{
	$access = Session::Get()->getAccess();
	if (count($access) == 0)
		return true;
	foreach ($access as $type)
		foreach ($type as $item)
			if ($item == $perm)
				return true;
	if (strpos($perm, '@') !== false)
		if (Session::Get()->checkAccessMail($perm))
			return true;
	return false;
}

$dbh = $settings->getDatabase();

if ($_GET['list'] == 'delete') {
	foreach (explode(',', $_GET['access']) as $a) {
		if (!checkAccess($a))
			die('invalid access');
		$statement = $dbh->prepare("DELETE FROM bwlist WHERE access = :access AND bwlist.type = :type AND bwlist.value = :value;");
		$statement->execute(array(':access' => $a, ':type' => $_GET['type'], ':value' => $_GET['value']));
	}
	header("Location: ?page=bwlist");
	die();
}

if ($_GET['list'] == 'add') {
	foreach ($_POST['access'] as $access)
	{
		if (strpos($_POST['value'], ' ') !== false) die('Invalid email address, domain name or IP address.');
		if (strpos($access, ' ') !== false) die('Invalid email address or domain name.');
		if ($_POST['value'][0] == '@') $_POST['value'] = substr($_POST['value'], 1);
		if ($access[0] == '@') $access = substr($access, 1);
		if (!checkAccess($access)) {
			header("Location: ?page=bwlist&error=perm");
			die();
		}
		if ($_POST['type'] == 'whitelist' || $_POST['type'] == 'blacklist') {
			$statement = $dbh->prepare("INSERT INTO bwlist (access, type, value) VALUES(:access, :type, :value);");
			$statement->execute(array(':access' => strtolower($access), ':type' => $_POST['type'], ':value' => strtolower($_POST['value'])));
		}
	}
	header("Location: ?page=bwlist");
	die();
}

$title = 'Black/whitelist';
$javascript[] = 'static/js/bwlist.js';
require_once BASE.'/partials/header.php';

$row_classes = array(
	'whitelist' => 'success',
	'blacklist' => 'danger',
);

function print_row($type, $value, $accesses, $icon = '')
{
	$access = implode(', ', array_map(function($v) { return $v === '' ? '<span class="text-muted">everyone</span>' : htmlspecialchars($v);}, $accesses));
	if (count($accesses) > 1) $access = '<span class="badge">'.count($accesses).'</span> '.$access;
?>
							<td class="hidden-xs" style="width:30px"><?php echo $icon ?></td>
							<td class="hidden-xs"><?php p($type); ?></td>
							<td class="hidden-xs"><?php p($value); ?></td>
							<td class="hidden-xs"><?php echo $access; ?></td>
							<td class="visible-xs">
								<p>
									<span class="glyphicon glyphicon-pencil"></span>&nbsp;
									<?php p($value); ?>
								</p>
								<p>
									<span class="glyphicon glyphicon-inbox"></span>&nbsp;
									<?php echo $access; ?>
								</p>
							</td>
							<td style="width: 30px; vertical-align: middle">
								<a onclick="return confirm('Really delete <?php p($type) ?> <?php p($value) ?> for <?php echo count($accesses)?> recipients?')" title="Remove" href="?page=bwlist&list=delete&access=<?php echo urlencode(implode(',', $accesses)) ?>&type=<?php p($type) ?>&value=<?php echo urlencode($value) ?>"><i class="glyphicon glyphicon-remove"></i></a>
							</td>
<?php
}

$result = array();
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 500;

$total = null;
$access = Session::Get()->getAccess();
$search = $_GET['search'];
$in_access = $domain_access = array();
foreach (array_merge((array)$access['mail'], (array)$access['domain']) as $k => $v)
	$in_access[':access'.$k] = $v;
foreach ((array)$access['domain'] as $k => $v)
	$domain_access[':domain'.$k] = '%@'.$v;
$foundrows = $where = '';
$wheres = array();
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql')
	$foundrows = 'SQL_CALC_FOUND_ROWS';
if ($search != '')
	$wheres[] = 'value LIKE :search';
if (count($access) != 0) {
	$restrict = '(access IN ('.implode(',', array_keys($in_access)).')';
	foreach (array_keys($domain_access) as $v)
		$restrict .= ' OR access LIKE '.$v;
	$restrict .= ')';
	$wheres[] = $restrict;
}

if (count($wheres))
	$where = 'WHERE '.implode(' AND ', $wheres);
$sql = "SELECT $foundrows * FROM bwlist $where ORDER BY type DESC, value ASC LIMIT :offset, :limit;";
$statement = $dbh->prepare($sql);
$statement->bindValue(':limit', (int)$limit + 1, PDO::PARAM_INT);
$statement->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
if ($search != '')
	$statement->bindValue(':search', '%'.$search.'%');
foreach ($in_access as $k => $v)
	$statement->bindValue($k, $v);
foreach ($domain_access as $k => $v)
	$statement->bindValue($k, $v);
$statement->execute();
while ($row = $statement->fetch(PDO::FETCH_ASSOC))
	$result[] = $row;
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
	$total = $dbh->query('SELECT FOUND_ROWS();');
	$total = (int)$total->fetchColumn();
}
if ($dbh->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite') {
	if ($offset == 0 && count($result) < $limit + 1) {
		$total = count($result);
	} else {
		$total = $dbh->prepare("SELECT COUNT(*) FROM bwlist $where;");
		if ($search != '')
			$total->bindValue(':search', '%'.$search.'%');
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

// For users with many access levels; print them more condensed
$result2 = array();
foreach ($result as $row)
	$result2[$row['type']][$row['value']][] = $row['access'];

if ($_GET['error'] == 'perm') {
?>
	<div class="container-fluid">
		<div class="alert alert-danger" role="alert">
			You are not allowed to add a black/whitelist entry for that recipient.
		</div>
	</div>
<?php
}

?>
	<div class="container-fluid">
		<div class="col-md-6 col-lg-8">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">
						Black/whitelist
						<a class="pull-right" data-toggle="collapse" href="#search">
							<span class="glyphicon glyphicon-search"></span>
						</a>
					</h3>
				</div>
				<div id="search" class="<?php if (!$search) echo 'collapse'; ?>"><div class="panel-body">
					<form class="form-horizontal" method="get">
						<input type="hidden" name="page" value="bwlist">
						<div class="input-group">
							<span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
							<input type="text" class="form-control" placeholder="Search for..." name="search" value="<?php p($search) ?>">
							<span class="input-group-btn">
								<button class="btn btn-default" type="search">Search</button>
							</span>
						</div>
					</form>
				</div></div>
				<table class="table">
					<thead class="hidden-xs">
						<tr>
							<th class="hidden-xs" style="width: 30px"></th>
							<th class="hidden-xs" style="width: 100px">Type</th>
							<th class="hidden-xs">Sender</th>
							<th class="hidden-xs">For recipient</th>
							<th class="visible-xs"></th>
							<th style="width: 30px"></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$id = 0;
						foreach ($result2 as $type => $items) {
							foreach ($items as $value => $accesses) {
								if (count($accesses) > 1) {
									$id++;
						?>
						<tr style="cursor:pointer" data-toggle="<?php p($id) ?>" class="toggle <?php p($row_classes[$type] ?: 'info'); ?>">
						<?php print_row($type, $value, $accesses, '<span class="expand-icon glyphicon glyphicon-expand"></span>') ?>
						</tr>
						<?php
									foreach ($accesses as $access) {
						?>
						<tr style="display:none" class="hidden-<?php p($id) ?> <?php p($row_classes[$type] ?: 'info'); ?>">
						<?php print_row($type, $value, array($access), '<sup style="opacity:.5">L</sup>') ?>
						</tr>
						<?php
									}
								} else {
						?>
						<tr class="<?php p($row_classes[$type] ?: 'info'); ?>">
						<?php print_row($type, $value, $accesses) ?>
						</tr>
						<?php
								}
							}
						}
						if (count($result) == 0)
							echo '<tr><td colspan="4" class="text-muted">No black/whitelist</td></tr>';
						?>
					</tbody>
				</table>
			</div>
			<nav>
				<?php if ($total) { // MySQL SQL_CALC_FOUND_ROWS version?>
				<ul class="pagination">
					<?php foreach ($pages as $p) { ?>
						<?php if ($p === '...') { ?>
						<li class="disabled"><a href="#">...</a></li>
						<?php } else if ($p == $currpage) { ?>
						<li class="active"><a href="#"><?php p($p+1)?></a></li>
						<?php } else { ?>
						<li><a href="?page=bwlist&offset=<?php p($limit*$p) ?>&limit=<?php p($limit); ?>&search=<?php p($search); ?>"><?php p($p+1)?></a></li>
						<?php } ?>
					<?php } ?>
				</ul>
				<?php } else { ?>
				<ul class="pager">
					<li class="previous<?php if ($offset == 0) p(" disabled") ?>"><a href="javascript:history.go(-1);"><span aria-hidden="true">&larr;</span> Previous</a></li>
					<li class="next<?php if (!$pagemore) p(" disabled") ?>"><a href="?page=bwlist&offset=<?php p($offset + $limit); ?>&limit=<?php p($limit); ?>&search=<?php p($search); ?>">Next <span aria-hidden="true">&rarr;</span></a></li>
				</ul>
				<?php } ?>
			</nav>
		</div>
		<div class="col-md-6 col-lg-4">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Add...</h3>
				</div>
				<div class="panel-body">
					<form class="form-horizontal" action="?page=bwlist&list=add" method="post">
						<div class="form-group">
							<label for="type" class="control-label col-md-3">Action</label>
							<div class="col-md-9">
								<select name="type" class="form-control">
									<option value="blacklist">Blacklist</option>
									<option value="whitelist">Whitelist</option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3">Sender</label>
							<div class="col-md-9">
								<input type="text" class="form-control" name="value">
							</div>
						</div>
						<div class="form-group">
							<label class="control-label col-md-3" style="white-space: nowrap;">For recipient</label>
							<div class="col-md-9">
								<?php
									$_access = array();
									foreach (Session::Get()->getAccess() as $type => $accesses)
										if ($type == 'mail' || $type == 'domain')
											foreach ($accesses as $a)
												$_access[] = $a;
									if (count($_access) == 1) {
								?>
									<input type="hidden" class="form-control" name="access[]" value="<?php p($_access[0]); ?>">
									<p class="form-control-static"><?php p($_access[0]); ?></p>
								<?php
									} else if (count($_access) > 0) {
								?>
									<button id="check-all" class="btn btn-info">Select all</button>
									<button id="add-access" class="btn btn-default">Add custom</button>
									<?php if (count($_access) > 5) { ?>
									<div class="panel panel-default" style="height: 115px; padding-left: 10px; margin-top: 5px; overflow-y: scroll;">
									<?php } ?>
									<div id="extra-accesses"></div>
									<?php foreach ($_access as $a) { ?>
									<div class="checkbox">
										<label>
											<input type="checkbox" class="recipient" name="access[]" value="<?php p($a); ?>">
											<?php p($a); ?>
										</label>
									</div>
									<?php } ?>
									<?php if (count($_access) > 5) { ?>
									</div>
									<?php } ?>
								</select>
								<?php } else { ?>
									<input type="text" class="form-control" name="access[]" placeholder="everyone">
								<?php } ?>
								<p class="help-block">
									Sender may be an IP address, an e-mail address, a domain name or a wildcard domain name starting with a dot (eg. .co.uk).
								</p>
							</div>
						</div>
						<div class="col-md-offset-3 col-md-9">
							<button type="submit" class="btn btn-primary">Add</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<style>
		table {
			table-layout: fixed;
		}
		td {
			text-overflow: ellipsis;
			white-space: nowrap;
			overflow: hidden;
		}
	</style>
<?php require_once BASE.'/partials/footer.php'; ?>
