<?php
require("../config.php");
require_once("stats.php");
//require_once("category.php");
echo "Testing Category functions\n";

$stat = new Stats();

$list = $stat->getList('count');
$nfocnt = $stat->getStats('count', 'ALL');
var_dump($nfocnt);
?>
