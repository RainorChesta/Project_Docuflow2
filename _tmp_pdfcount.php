<?php
$p = file_get_contents('_tmp_margin_test2.pdf');
preg_match('/\/MediaBox\s*\[([^\]]+)\]/', $p, $m);
echo 'MediaBox: ' . ($m[1] ?? 'none') . PHP_EOL;
preg_match('/\/Count\s+(\d+)/', $p, $m2);
echo 'Count: ' . ($m2[1] ?? 'none') . PHP_EOL;