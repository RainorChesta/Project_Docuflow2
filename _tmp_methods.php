<?php
require 'vendor/autoload.php';
$r = new ReflectionClass('Dompdf\Options');
$out = [];
foreach ($r->getMethods() as $m) {
    $out[] = $m->getName();
}
file_put_contents(__DIR__ . '/_tmp_out4.txt', implode("\n", $out));
echo "DONE " . count($out) . "\n";
