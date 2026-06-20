<?php
$s = file_get_contents(__DIR__ . '/Data.php');
$needle = "if (\$timeType === 'daily' || \$timeType === 'monthly')";
// Instead search for a simpler pattern
$pos = strpos($s, "if (") ;
$pos2 = strpos($s, "if (", 200);
$start = strpos($s, "if (") ;
$start = strpos($s, "if (", strpos($s, "getMergedDataByVariablesAndTime"));
echo "start pos: " . $start . "\n";
$substr = substr($s, $start, 1000);
echo "substr:\n" . substr($substr,0,200) . "\n";
// find matching brace
$len = strlen($s);
$depth = 0;
for($i=$start;$i<$len;$i++){
    $ch = $s[$i];
    if($ch=='{') $depth++;
    if($ch=='}') $depth--;
    if($depth==0){
        echo "matching at pos " . $i . "\n";
        break;
    }
}
echo "final depth: $depth\n";
?>