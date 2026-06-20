<?php
$s = file_get_contents(__DIR__ . '/Data.php');
echo "{ count: " . substr_count($s, '{') . ", } count: " . substr_count($s, '}') . "\n";
$diff = substr_count($s,'{') - substr_count($s,'}');
echo "diff: " . $diff . "\n";
?>