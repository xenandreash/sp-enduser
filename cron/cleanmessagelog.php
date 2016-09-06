<?php

/*
 * Don't invoke directly. Run as:
 * php cron.php.txt cleanmessagelog
 */

if (!isset($_SERVER['argc']))
        die('this file can only be run from command line');

define('BASE', dirname(__FILE__).'/..');
require_once BASE.'/inc/core.php';
require_once BASE.'/inc/utils.php';

$max = 5000000;

foreach ($settings->getMessagelogTables() as $table)
{
	$dbh = $settings->getDatabase();
	$statement = $dbh->prepare('SELECT id FROM '.$table.' ORDER BY id DESC LIMIT 1;');
	$statement->execute();
	$item = $statement->fetchObject();
	if ($item->id < $max)
	{
		echo "$table: id {$item->id} is less than $max (skip)\n";
		continue;
	}
	echo $table.': Delete older IDs than '.($item->id - $max)."\n";

	// Delete in chunks up to 5000*1000 in total, important not to leave gaps
	for ($i = 0; $i < 5000; $i++) {
		$newest = $item->id - $max - ($i * 1000);
		if ($newest < 0)
			break;
		$oldest = max($newest - 1000, 0);
		echo "$table: Delete between $newest and $oldest\n";

		$statement = $dbh->prepare('DELETE FROM '.$table.' WHERE id < :newest AND id >= :oldest;');
		$statement->execute(array(':newest' => $newest, ':oldest' => $oldest));
		$deleted = $statement->rowCount();
		echo "$table: Chunk $i deleted ".$deleted."\n";
		if ($deleted == 0)
			break;
	}
	echo "$table: Done\n";
}
