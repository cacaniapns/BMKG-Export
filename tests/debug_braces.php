<?php
$lines = file(__DIR__ . '/Data.php');
$depth = 0;
foreach ($lines as $i => $line) {
    $numOpen = substr_count($line, '{');
    $numClose = substr_count($line, '}');
    $depth += $numOpen - $numClose;
    if ($depth < 0) {
        echo "Negative depth at line " . ($i+1) . ": " . trim($line) . "\n";
    }
}
echo "Final depth: $depth\n";
?>