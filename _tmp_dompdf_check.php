<?php
require 'vendor/autoload.php';
echo class_exists('Dompdf\Dompdf') ? 'dompdf OK' : 'MISSING';
