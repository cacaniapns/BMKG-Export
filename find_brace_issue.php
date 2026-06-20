<?php
$lines = file(__DIR__ . '/Data.php');
$depth=0;
foreach($lines as $i=>$line){
    $numOpen = substr_count($line,'{');
    $numClose = substr_count($line,'}');
    $depth += $numOpen - $numClose;
    if($depth<0){
        echo "Line ".($i+1)." depth=".$depth." => ".trim($line)."\n";
        break;
    }
}
if($depth>=0) echo "No negative depth found, final depth: $depth\n";
?>