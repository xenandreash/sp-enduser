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
// foreach (array(10, 50, 100, 500, 1000, 5000) as $n)
// 	$pagesize[$n] = $n.' results';
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
	$backend = ($dbBackend->isValid() ? $dbBackend : $nodeBackend);
	$results = $backend->loadMailHistory($real_search, $size, $param['history'], $errors);
	$timesort = merge_2d($timesort, $results);
}
else if ($source == 'queue' || $source == 'quarantine') {
	$results = $nodeBackend->loadMailQueue($real_search, $size, $param['queue'], $errors);
	$timesort = merge_2d($timesort, $results);
}

krsort($timesort);
ksort($errors);
?>
	<nav class="navbar navbar-toolbar navbar-static-top">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#toolbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<i class="glyphicon glyphicon-search"></i>
				</button>
				<div class="navbar-brand">
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
				<!-- <a class="navbar-brand visible-xs"><?php p($sources[$source]); ?></a>
				<a class="navbar-brand hidden-xs hidden-sm"><?php p($title); ?></a> -->
			</div>
			<div class="collapse navbar-collapse" id="toolbar-collapse">
				<form class="navbar-form navbar-left" role="search">
					<input type="hidden" name="source" value="<?php p($source); ?>">
					<div class="form-group">
						<div class="input-group">
							<input type="search" class="form-control" size="40" placeholder="Search" name="search" value="<?php p($_GET['search']) ?>">
							<div class="input-group-btn">
								<button class="btn btn-default">Search</button>
							</div>
						</div>
						<!-- <label>Search</label> -->
					</div>
				</form>
				<ul class="nav navbar-nav navbar-left hidden-xs hidden-sm">
					<li class="divider"></li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Actions <span class="caret"></span></a>
						<ul class="dropdown-menu" role="menu">
							<li><a href="#" data-bulk-action="delete">Delete selected</a></li>
							<li><a href="#" data-bulk-action="bounce">Bounce selected</a></li>
							<li><a href="#" data-bulk-action="retry">Retry/release selected</a></li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
	</nav>
	<nav class="navbar navbar-default navbar-fixed-bottom visible-xs" id="bottom-bar" style="display:none;">
		<div class="container-fluid">
			<ul class="nav navbar-nav">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Actions <span class="caret"></span></a>
					<ul class="dropdown-menu" role="menu">
						<li><a href="#" data-bulk-action="delete">Delete selected</a></li>
						<li><a href="#" data-bulk-action="bounce">Bounce selected</a></li>
						<li><a href="#" data-bulk-action="retry">Retry/release selected</a></li>
					</ul>
				</li>
			</ul>
		</div>
	</nav>
	<div class="container-fluid">
		<?php if (count($errors)) { ?>
		<p style="padding-left: 17px; padding-top: 17px;">
			<span class="semitrans">
				Some messages might not be available at the moment due to maintenance.
			</span>
		</p>
		<?php } ?>
		<div class="row">
			<!-- <table class="list pad fixed"> -->
			<table class="table nowrap">
				<thead>
					<tr>
						<!-- <th style="width: 17px; padding: 0"></th> -->
						<!-- <th style="width: 20px" class="action">
							<?php if ($m['type'] == 'queue') { ?><input type="checkbox" id="select-all"><?php } ?>
						</th> -->
						<?php if ($source == 'queue') { ?>
							<th>&nbsp;</th>
						<?php } ?>
						<th style="width: 125px">Date<span class="hidden-sm hidden-xs"> and time</span></th>
						<th class="hidden-xs">From</th>
						<th class="hidden-xs">To</th>
						<th style="width:100%;">Subject</th>
						<?php if ($display_scores) { $cols++ ?><th class="hidden-xs hidden-sm">Scores</th><?php } ?>
						<th class="hidden-xs hidden-sm">Details</th>
						<th class="hidden-xs hidden-sm" style="width: 60px"></th>
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
						$preview = http_build_query(array(
							'page' => 'preview',
							'node' => $m['id'],
							'id' => $m['data']->id,
							'type' => $m['type']));
						
						$action_classes = array(
							'DELIVER' => 'success',
							'QUEUE' => 'success',
							'QUARANTINE' => 'warning',
							'BOUNCE' => 'danger',
							'REJECT' => 'danger',
							'ERROR' => 'danger',
							'DEFER' => 'info'
						);
					?>
					<tr class="<?php p($action_classes[$m['data']->msgaction]); ?>">
						<!-- <td style="width: 17px; padding: 0"></td> -->
						<!-- <td class="action <?php p($m['data']->msgaction.' '.$m['type']) ?>" title="<?php p($m['data']->msgaction) ?>">
						<?php if ($m['type'] == 'queue') { // queue or quarantine ?>
							<input type="checkbox" name="multiselect-<?php p($m['data']->id) ?>" value="<?php p($m['id']) ?>">
						<?php } else { // history ?>
							<strong><?php p($m['data']->msgaction[0]) ?></strong>
						<?php } ?>
						</td> -->
						<?php if ($source == 'queue') { ?>
							<td class="pad-child-instead">
								<label>
									<input type="checkbox" name="multiselect-<?php p($m['data']->id); ?>" value="<?php p($m['id']); ?>">
								</label>
							</td>
						<?php } ?>
						<td class="semitrans">
							<?php p(strftime('%Y-%m-%d', $m['data']->msgts0 - $_SESSION['timezone'] * 60)) ?>
							<span class="hidden-sm hidden-xs">
								<?php p(strftime('%H:%M:%S', $m['data']->msgts0 - $_SESSION['timezone'] * 60)) ?>
							</span>
						</td>
						<td class="hidden-xs"><?php p($m['data']->msgfrom) ?></td>
						<td class="hidden-xs"><?php p($m['data']->msgto) ?></td>
						<td class="overflowhack">
							<div><p><?php p($m['data']->msgsubject) ?></p></div>
						</td>
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
						<td class="hidden-xs hidden-sm"><?php p(implode(', ', array_unique($printscores))) ?></td>
						<?php } ?>
						<td class="hidden-xs hidden-sm">
						<?php if ($m['type'] == 'queue' && $m['data']->msgaction == 'DELIVER') { // queue ?>
							In queue (retry <?php p($m['data']->msgretries) ?>)
							<span class="semitrans"><?php p($m['data']->msgerror) ?></span>
						<?php } else { // history or quarantine ?>
							<span class="semitrans"><?php p($m['data']->msgdescription) ?></span>
						<?php } ?>
						</td>
						<td class="hidden-xs hidden-sm">
							<a title="Details" class="icon mail" href="?<?php echo $preview?>"></a>
						<?php if ($m['type'] != 'history') { ?>
							<div title="Release/retry" class="icon go"></div>
						<?php } ?>
						</td>
					</tr>
				<?php }} ?>
				<?php if (empty($timesort)) { ?>
					<tr>
						<td colspan="<?php p($cols) ?>"><span class="semitrans">No matches</span></td>
					</tr>
				<?php } ?>
				</form>
				</tbody>
			</table>
		</div>
		
		<form id="nav-form">
			<nav>
				<ul class="pager">
					<li class="previous"><a href="#" onclick="history.go(-1); return false;" <?php echo $prev_button ?>><span aria-hidden="true">&larr;</span> Previous</a></li>
					<li class="next"><a href="#" onclick="$('#nav-form').submit(); return false;" <?php echo $next_button; ?>>Next <span aria-hidden="true">&rarr;</span></a></li>
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
			<span class="semitrans">
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
