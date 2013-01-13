<?php
require(dirname(__FILE__)."/../../../../www/config.php");
require_once(dirname(__FILE__)."/../lib/stats.php");
echo "Testing Category functions\n";

$stat = new Stats();

$list = $stat->getList('count');
$ret = $stat->getStats('count', 'ALL');

$output = buildTable($ret);

function buildTable($array) {
	$count = count($array);
	echo "Building table for $count items\n";
	$width = (int) (80/$count);
	echo "Using $width width\n";
	$format = '|%-10.9s|%-15.15s|%-13.13s|%-13.13s|%-13.13s|';

	foreach ( $array as $key => $i ) {
		$number = number_format($i);
		$width = 
		$out[] = '+'.str_repeat('-',68).'+';
	}
}
?>
