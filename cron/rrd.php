<?php
if (!isset($_SERVER['argc']))
        die('this file can only be run from command line');

define('BASE', dirname(__FILE__).'/..');
require_once BASE.'/inc/core.php';

$dbh = $settings->getDatabase();

$q = $dbh->query('SELECT SUM(reject) AS reject, SUM(deliver) AS deliver, domain FROM stat GROUP BY domain;');
while ($row = $q->fetch(PDO::FETCH_ASSOC))
{
	$graph = $settings->getGraphPath().'/'.$row['domain'].'.rrd';
	$r = halon_rrd_create($graph, array('reject', 'deliver'));
	if (!$r) {
		echo rrd_error()."\n";
		continue;
	}
	$r = halon_rrd_update($graph, array('reject' => $row['reject'], 'deliver' => $row['deliver']));
	if (!$r) {
		echo rrd_error()."\n";
	}
}

function halon_rrd_create($name, $legends)
{
	if (file_exists($name))
		return true;
	$cmd = array();
	$cmd[] = '--step';
	$cmd[] = '60';
	foreach ($legends as $l)
		$cmd[] = 'DS:'.$l.':COUNTER:120:0:1000000';
	$cmd[] = 'RRA:AVERAGE:0.5:1:1440';
	$cmd[] = 'RRA:AVERAGE:0.5:30:1488';
	$cmd[] = 'RRA:AVERAGE:0.5:1440:365';
	return rrd_create($name, $cmd);
}

function halon_rrd_update($name, $data)
{
	$cmd = array();
	$cmd[] = '-t';
	$cmd[] = implode(':', array_keys($data));
	$cmd[] = 'N:'.implode(':', $data);
	return rrd_update($name, $cmd);
}
