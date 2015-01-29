<?php
if (!defined('SP_ENDUSER')) die('File not included');

require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

if (isset($_POST['delete']) || isset($_POST['bounce']) || isset($_POST['retry'])) {
	$actions = array();
	foreach ($_POST as $k => $v) {
		if (!preg_match('/^multiselect-(\d+)$/', $k, $m))
			continue;

		$node = $v;
		$id = intval($m[1]);
		$client = soap_client($node);

		// Access permission
		restrict_mail('queue', $node, $id); // Dies if access is denied
		$actions[$v][] = $id;
	}
	foreach ($actions as $soapid => $list)
	{
		$id = implode(',', $list);
		if (isset($_POST['bounce']))
			soap_client($soapid)->mailQueueBounce(array('id' => $id));
		if (isset($_POST['delete']))
			soap_client($soapid)->mailQueueDelete(array('id' => $id));
		if (isset($_POST['retry']))
			soap_client($soapid)->mailQueueRetry(array('id' => $id));
	}
	header('Location: '.$_SERVER['REQUEST_URI']);
	die();
}

$title = 'Messages';
$javascript[] = 'static/js/index.js';
require_once BASE.'/partials/header.php';

$action_classes = array(
	'DELIVER' => 'default',
	'QUEUE' => 'default',
	'QUARANTINE' => 'warning',
	'BOUNCE' => 'danger',
	'REJECT' => 'danger',
	'ERROR' => 'danger',
	'DEFER' => 'info'
);

function get_preview_link($m) {
	return '?'.http_build_query(array(
		'page' => 'preview',
		'node' => $m['id'],
		'id' => $m['data']->id,
		'msgid' => $m['data']->msgid,
		'msgactionid' => $m['data']->msgactionid,
		'type' => $m['type']
	));
}

// Backends
$dbBackend = new DatabaseBackend($settings->getDatabase());
$nodeBackend = new NodeBackend($settings->getNodes());

// Default values
$search = isset($_GET['search']) ? hql_transform($_GET['search']) : '';
$size = isset($_GET['size']) ? $_GET['size'] : 50;
$size = $size > 5000 ? 5000 : $size;
$source = isset($_GET['source']) ? $_GET['source'] : $settings->getDefaultSource();
$display_scores = $settings->getDisplayScores();

// // Select box arrays
$pagesize = array(10, 50, 100, 500, 1000, 5000);
$sources = array('history' => 'History');
if($nodeBackend->isValid())
	$sources += array('queue' => 'Queue', 'quarantine' => 'Quarantine');

// Create actual search query for SOAP, in order of importance (for security)
$queries = array();
$restrict = build_query_restrict();
if ($restrict != '')
	$queries[] = $restrict;
if ($source == 'queue')
	$queries[] = 'action=DELIVER';
if ($source == 'quarantine')
	$queries[] = 'action=QUARANTINE';
if ($search != '')
	$queries[] = $search;
$real_search = implode(' && ', $queries);

// Initial settings
$timesort = array();
$tasks = array();
$prev_button = ' disabled';
$next_button = ' disabled';
$param = array();
$errors = array();

// Override offset with GET
$totaloffset = 0;
foreach ($_GET as $k => $v) {
	if (!preg_match('/^(history|queue|log)offset(\d+)$/', $k, $m))
		continue;
	if ($v < 1)
		continue;
	$param[$m[1]][$m[2]]['offset'] = $v;
	$totaloffset += $v;
	$prev_button = '';
}

$cols = 8;

if ($source == 'history') {
	$backend = ($settings->getUseDatabaseLog() && $dbBackend->isValid() ? $dbBackend : $nodeBackend);
	$results = $backend->loadMailHistory($real_search, $size, $param['history'], $errors);
	$timesort = merge_2d($timesort, $results);
}
else if ($source == 'queue' || $source == 'quarantine') {
	$results = $nodeBackend->loadMailQueue($real_search, $size, $param['queue'], $errors);
	$timesort = merge_2d($timesort, $results);
}

krsort($timesort);
ksort($errors);

$has_multiple_addresses = count(Session::Get()->getAccess('mail')) != 1;
?>
	<nav class="navbar navbar-toolbar navbar-static-top">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#toolbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<i class="glyphicon glyphicon-search"></i>
				</button>
				<div class="navbar-brand visible-xs">
					<div class="dropdown">
						<a class="dropdown-toggle navbar-brand-link" id="source-select" data-toggle="dropdown" aria-expanded="true">
							<?php p($sources[$source]); ?>
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu" role="menu" aria-labelledby="source-select">
							<?php foreach ($sources as $sid => $sname) { ?>
							<li role="presentation"><a role="menuitem" tabindex="-1" href="?<?php p(mkquery(array('source' => $sid, 'search' => $_GET['search'], 'size' => $_GET['size']))); ?>"><?php p($sname); ?></a></li>
							<?php } ?>
						</ul>
					</div>
				</div>
			</div>
			<div class="collapse navbar-collapse" id="toolbar-collapse">
				<ul class="nav navbar-nav navbar-left hidden-xs">
					<li class="dropdown">
						<a href="#" class="dropdown-toggle navbar-brand" data-toggle="dropdown" role="button" aria-expanded="false"><?php p($sources[$source]); ?> <span class="caret"></span></a>
						<ul class="dropdown-menu" role="menu">
							<?php foreach ($sources as $sid => $sname) { ?>
							<li><a href="?<?php p(mkquery(array('source' => $sid, 'search' => $_GET['search'], 'size' => $_GET['size']))); ?>"><?php p($sname); ?></a></li>
							<?php } ?>
						</ul>
					</li>
				</ul>
				<form class="navbar-form navbar-left" role="search">
					<input type="hidden" name="source" value="<?php p($source); ?>">
					<div class="form-group">
						<div class="input-group">
							<input type="search" class="form-control" size="40" placeholder="Search" name="search" value="<?php p($_GET['search']) ?>">
							<div class="input-group-btn">
								<button class="btn btn-default">Search</button>
							</div>
						</div>
					</div>
				</form>
				<?php if ($source != 'history') { ?>
				<ul class="nav navbar-nav navbar-left hidden-xs hidden-sm">
					<li class="divider"></li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Actions <span class="caret"></span></a>
						<ul class="dropdown-menu" role="menu">
							<li><a data-bulk-action="delete"><i class="glyphicon glyphicon-trash"></i>&nbsp;Delete selected</a></li>
							<li><a data-bulk-action="bounce"><i class="glyphicon glyphicon-repeat"></i>&nbsp;Bounce selected</a></li>
							<li><a data-bulk-action="retry"><i class="glyphicon glyphicon-play"></i>&nbsp;Retry/release selected</a></li>
						</ul>
					</li>
				</ul>
				<?php } ?>
			</div>
		</div>
	</nav>
	<?php if ($source != 'history') { ?>
	<nav class="navbar navbar-default navbar-fixed-bottom hidden-xs hidden-md hidden-lg" id="bottom-bar" style="display:none;">
		<div class="container-fluid">
			<ul class="nav navbar-nav">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Actions <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a data-bulk-action="delete"><i class="glyphicon glyphicon-trash"></i>&nbsp;Delete selected</a></li>
						<li><a data-bulk-action="bounce"><i class="glyphicon glyphicon-repeat"></i>&nbsp;Bounce selected</a></li>
						<li><a data-bulk-action="retry"><i class="glyphicon glyphicon-play"></i>&nbsp;Retry/release selected</a></li>
					</ul>
				</li>
			</ul>
		</div>
	</nav>
	<?php } ?>
	<div class="container-fluid">
		<?php if (count($errors)) { ?>
		<p style="padding-left: 17px; padding-top: 17px;">
			<span class="text-muted">
				Some messages might not be available at the moment due to maintenance.
			</span>
		</p>
		<?php } ?>
		<div class="row">
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
			<table class="table table-hover table-condensed hidden-xs">
				<thead>
					<tr>
						<?php if ($source == 'queue') { ?>
							<th style="width:30px">&nbsp;</th>
						<?php } ?>
						<th class="hidden-xs">From</th>
						<?php if ($has_multiple_addresses) { ?>
						<th class="hidden-xs">To</th>
						<?php } ?>
						<th>Subject</th>
						<?php if ($display_scores) { $cols++ ?><th class="visible-lg" style="width: 120px;">Scores</th><?php } ?>
						<th class="hidden-xs hidden-sm">Status</th>
						<th>&nbsp;</th>
						<th style="width: 25px;" class="hidden-xs hidden-sm"></th>
						<?php if ($source != 'history') { ?>
						<th style="width: 25px;" class="hidden-xs hidden-sm"></th>
						<?php } ?>
						<!-- Padding column to avoid having the OSX scrollbar cover the rightmost button -->
						<th style="width: 20px;">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
				<form method="post" id="multiform">
				<?php
				$i = 1;
				foreach ($timesort as $t) {
					if ($i > $size) {
						$next_button = ''; // enable "next" page button
						break;
					}
					foreach ($t as $m) {
						if ($i > $size) {
							$next_button = ''; // enable "next" page button
							break;
						}
						$i++;
						$param[$m['type']][$m['id']]['offset']++;
						$preview = get_preview_link($m);
					?>
					<tr class="<?php p($action_classes[$m['data']->msgaction]); ?>">
						<?php if ($source == 'queue') { ?>
							<td>
								<input type="checkbox" name="multiselect-<?php p($m['data']->id); ?>" value="<?php p($m['id']); ?>">
							</td>
						<?php } ?>
						<td class="hidden-xs" data-href="<?php p($preview); ?>"><?php p($m['data']->msgfrom) ?></td>
						<?php if ($has_multiple_addresses) { ?>
						<td class="hidden-xs" data-href="<?php p($preview); ?>"><?php p($m['data']->msgto) ?></td>
						<?php } ?>
						<td data-href="<?php p($preview); ?>"><?php p($m['data']->msgsubject) ?></td>
						<?php if ($display_scores) {
							$printscores = array();
							$scores = history_parse_scores($m['data']);
							foreach ($scores as $engine => $s) {
								if ($engine == 'rpd' && $s['score'] != 'Unknown')
									$printscores[] = strtolower($s['score']);
								if ($engine == 'kav' && $s['score'] != 'Ok')
									$printscores[] = 'virus';
								if ($engine == 'clam' && $s['score'] != 'Ok')
									$printscores[] = 'virus';
								if ($engine == 'rpdav' && $s['score'] != 'Ok')
									$printscores[] = 'virus';
								if ($engine == 'sa')
									$printscores[] = $s['score'];
							}
						?>
						<td class="visible-lg" data-href="<?php p($preview); ?>"><?php p(implode(', ', array_unique($printscores))) ?></td>
						<?php } ?>
						<td class="hidden-xs hidden-sm" data-href="<?php p($preview); ?>">
							<span title="<?php p(long_msg_status($m)); ?>"><?php p(short_msg_status($m)); ?></span>
						</td>
						<td data-href="<?php p($preview); ?>">
							<?php echo strftime('%b %e <span class="hidden-xs">%Y</span><span class="hidden-sm hidden-xs">, %H:%M:%S</span>', $m['data']->msgts0 - $_SESSION['timezone'] * 60); ?>
						</td>
						<td class="hidden-xs hidden-sm">
							<a title="Details" href="<?php echo $preview?>"><i class="glyphicon glyphicon-envelope"></i></a>
						</td>
						<?php if ($source != 'history') { ?>
						<td class="hidden-xs hidden-sm">
							<a title="Release/retry" data-action="retry"><i class="glyphicon glyphicon-play"></i></a>
						</td>
						<?php } ?>
						<td data-href="<?php p($preview); ?>">&nbsp;</td>
					</tr>
				<?php }} ?>
				<?php if (empty($timesort)) { ?>
					<tr>
						<td colspan="<?php p($cols) ?>" class="text-muted text-center">No matches</td>
					</tr>
				<?php } ?>
				</form>
				</tbody>
			</table>
			
			<div class="list-group not-rounded visible-xs">
				<?php foreach ($timesort as $t) { ?>
					<?php foreach ($t as $m) { ?>
						<a href="<?php p(get_preview_link($m)); ?>" class="list-group-item list-group-item-<?php p($action_classes[$m['data']->msgaction]); ?>">
							<h4 class="list-group-item-heading">
								<small class="pull-right">
								<?php
									if ($m['data']->msgts0 + (3600 * 24) > time())
										echo strftime('%H:%M', $m['data']->msgts0 - $_SESSION['timezone'] * 60);
									else
										echo strftime('%b %e %Y', $m['data']->msgts0 - $_SESSION['timezone'] * 60);
								?>
								</small>
								<?php p($m['data']->msgfrom) or pp('<span class="text-muted">Empty sender</span>'); ?>
								<?php if ($has_multiple_addresses) { ?>
									<br /><small>&rarr;&nbsp;<?php p($m['data']->msgto); ?></small>
								<?php } ?>
							</h4>
							<p class="list-group-item-text clearfix">
								<?php if ($m['type'] != 'log') { ?>
									<small class="pull-right text-right"><?php p(long_msg_status($m)); ?></small>
								<?php } ?>
								<?php p($m['data']->msgsubject); ?>
							</p>
						</a>
					<?php } ?>
				<?php } ?>
				<?php if (empty($timesort)) { ?>
					<a class="list-group-item disabled text-center">No matches</a>
				<?php } ?>
			</div>
		</div>
		
		<form id="nav-form">
			<nav>
				<ul class="pager">
					<li class="previous <?php echo $prev_button ?>"><a href="#" onclick="history.go(-1); return false;"><span aria-hidden="true">&larr;</span> Newer</a></li>
					<li class="next <?php echo $next_button; ?>"><a href="#" onclick="$('#nav-form').submit(); return false;">Older <span aria-hidden="true">&rarr;</span></a></li>
				</ul>
			</nav>
			<input type="hidden" name="size" value="<?php p($size) ?>">
			<input type="hidden" name="search" value="<?php p($search) ?>">
			<input type="hidden" name="source" value="<?php p($source) ?>">
			<?php foreach ($param as $type => $nodes) foreach ($nodes as $node => $args) if ($args['offset'] > 0) { ?>
				<input type="hidden" name="<?php p($type) ?>offset<?php p($node) ?>" value="<?php p($args['offset']) ?>">
			<?php } ?>
		</form>
		
		<hr />
		<p class="text-muted small">
			Results per page:
		</p>
		<div class="btn-group" role="group" aria-label="Results per page">
			<?php foreach ($pagesize as $s) {
				$classes = 'btn btn-sm btn-default';
				$href = '?'.mkquery(array('size' => $s, 'source' => $_GET['source'], 'search' => $_GET['search']));
				if ($s == $size) {
					$classes .= ' active';
					$href = '';
				}
			?>
				<a class="<?php p($classes); ?>" href="<?php p($href); ?>"><?php p($s); ?></a>
			<?php } ?>
		</div>
		
		<?php if (count($errors)) { ?>
		<div style="padding-left: 17px;">
			<span class="text-muted">
				Diagnostic information:
				<ul>
				<?php foreach ($errors as $n => $error) { ?>
					<li><?php p($n.': '.$error); ?></li>
				<?php } ?>
				</ul>
			</span>
		</div>
		<?php } ?>
	</div>
<?php require_once BASE.'/partials/footer.php'; ?>
