<?php
if (!isset($_SERVER['argc']))
        die('this file can only be run from command line');

define('BASE', dirname(__FILE__).'/..');
require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$dbh = $settings->getDatabase();
$path = '../rrd/';

$start = time();
$q = $dbh->query('SELECT SUM(reject) AS reject, SUM(deliver) AS deliver, domain FROM stat GROUP BY domain;');
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
	create_rrd($path.$row['domain'].'.rrd', array('reject', 'deliver'));
	update_rrd($path.$row['domain'].'.rrd', array('reject' => $row['reject'], 'deliver' => $row['deliver']));
}
$time = time() - $start;
if ($time < 0 || $time > 60) $time = 0; // sanity
$sleep = 60 - $time;
echo "done in $time s, sleeping $sleep s\n";
sleep($sleep);
function create_rrd($name, $legends) {
	if (file_exists($name)) return;
	$cmd = array();
	$cmd[] = '--step';
	$cmd[] = '60';
	foreach ($legends as $l)
		$cmd[] = 'DS:'.$l.':COUNTER:120:0:1000000';
	$cmd[] = 'RRA:AVERAGE:0.5:1:1440';
	$cmd[] = 'RRA:AVERAGE:0.5:30:1488';
	$cmd[] = 'RRA:AVERAGE:0.5:1440:365';
	rrd_create($name, $cmd);
}
function update_rrd($name, $data) {
	$cmd = array();
	$cmd[] = '-t';
	$cmd[] = implode(':', array_keys($data));
	$cmd[] = 'N:'.implode(':', $data);
	rrd_update($name, $cmd);
}
