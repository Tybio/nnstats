<?php
require(dirname(__FILE__)."/../../../../www/config.php");
require_once(dirname(__FILE__)."/../lib/stats.php");
echo "Testing Category functions\n";

$stat = new Stats();

$ret = $stat->getStats('ppq', 'count');

drawSmallTable($ret);

function drawSmallTable($array) {
        $i=0;
	$line = '+'.str_repeat('-', '35').'+';
	echo "$line\n";
	foreach ($array as $key => $value) {
		$number = number_format($value);
		printf("| %-15s |",   $key );
		printf(" %-15s |",  $number);
		echo "\n";
	}
	echo "$line\n";
}

function drawBigTable($array) {
	$i=0;
	foreach( $array as $items) {
		foreach ($items as $key => $value) {
			if ($i++==0) { printf("[%-10s]|",   $key ); }
			printf("[%-10s]|",   $value);
		}
		echo "\n"; 
	}
}
?>
