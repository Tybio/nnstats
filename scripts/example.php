<?php
require(dirname(__FILE__)."/../../../../www/config.php");
require_once(dirname(__FILE__)."/../lib/stats.php");
echo "Testing Category functions\n";

$stat = new Stats();

$pp = $stat->getStats('ppq', 'count');
$ts = $stat->getStats('trs', 'count');

$out1 = $stat->smallTable('ppq', $pp);
$out2 = $stat->smallTable('trs', $ts);

foreach ($out1 as $line) {
	echo "$line\n";
}
foreach ($out2 as $line) {
	echo "$line\n";
}
