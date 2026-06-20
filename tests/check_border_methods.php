<?php
require 'vendor/autoload.php';

$reflection = new ReflectionClass('\PhpOffice\PhpSpreadsheet\Style\Border');
$methods = $reflection->getMethods();
echo "Border Methods:\n";
foreach($methods as $method) {
    if(strpos($method->name, 'set') === 0) {
        echo "  - " . $method->name . PHP_EOL;
    }
}
?>
