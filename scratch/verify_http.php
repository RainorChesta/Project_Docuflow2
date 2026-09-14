<?php

$toolbarUrl = 'http://localhost:8080/web-apps/apps/documenteditor/main/app/view/Toolbar.js';
$toolbarContent = @file_get_contents($toolbarUrl);

echo "Toolbar.js size: " . strlen($toolbarContent) . "\n";
echo "Has F4 in Toolbar.js: " . (strpos($toolbarContent, 'F4 (21 x 33 cm)') !== false ? "YES" : "NO") . "\n";

$apiUrl = 'http://localhost:8080/web-apps/apps/api/documents/api.js';
$apiContent = @file_get_contents($apiUrl);

echo "api.js size: " . strlen($apiContent) . "\n";
echo "Has dynamic _dc in api.js: " . (strpos($apiContent, '9.4.0-f4-') !== false ? "YES" : "NO") . "\n";

$appUrl = 'http://localhost:8080/web-apps/apps/documenteditor/main/app.js';
$appContent = @file_get_contents($appUrl);

echo "app.js size: " . strlen($appContent) . "\n";
echo "Has dynamic _dc in app.js: " . (strpos($appContent, 'urlArgs: \'_dc=f4_\'') !== false || strpos($appContent, '_dc=f4_') !== false ? "YES" : "NO") . "\n";

$swUrl = 'http://localhost:8080/sdkjs/common/serviceworker/document_editor_service_worker.js';
$swContent = @file_get_contents($swUrl);
echo "sw.js size: " . strlen($swContent) . "\n";
echo "Has cache unregister in sw.js: " . (strpos($swContent, 'unregister') !== false ? "YES" : "NO") . "\n";
