<?php
$lines = file(__DIR__ . '/Data.php');
$depth = 0;
for($i=0;$i<count($lines);$i++){
    $lineNum = $i+1;
    $line = rtrim($lines[$i]);
    $numOpen = substr_count($line,'{');
    $numClose = substr_count($line,'}');
    $depth += $numOpen - $numClose;
    if($lineNum>=300 && $lineNum<=380){
        echo str_pad($lineNum,4,' ',STR_PAD_LEFT) . ' depth=' . str_pad($depth,4,' ',STR_PAD_LEFT) . ' | ' . trim($line) . "\n";
    }
}
echo "final depth: $depth\n";
?>