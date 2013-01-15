<?php
require(dirname(__FILE__)."/../../../../www/config.php");
require_once(dirname(__FILE__)."/../lib/stats.php");
echo "Testing Category functions\n";

$stat = new Stats();

$all = $stat->getAllStats();
//$ret = $stat->saveStats($all);

$table = $stat->statTable($all);

foreach ($table as $line) {
	echo "$line\n";
}
$stat->saveStats();
?>
