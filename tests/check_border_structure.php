<?php
require 'vendor/autoload.php';

// Check Border\BorderEdge class methods
$reflection = new ReflectionClass('\PhpOffice\PhpSpreadsheet\Style\Border');
echo "Border class methods:\n";
$methods = $reflection->getMethods();
foreach($methods as $method) {
    echo "  - " . $method->name . "()\n";
}

// Check what's in BorderEdge or Borders namespace
echo "\n\nChecking BorderEdge class:\n";
$edgeReflection = new ReflectionClass('\PhpOffice\PhpSpreadsheet\Style\Border\BorderEdge');
$edgeMethods = $edgeReflection->getMethods();
foreach($edgeMethods as $method) {
    echo "  - " . $method->name . "()\n";
}
?>
