<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\OnlyOfficeService::class);
$template = App\Models\DocumentTemplate::first();

if (!$template) {
    echo "No template found.\n";
    exit;
}

echo "Callback URL: " . $service->getTemplateCallbackUrl($template) . "\n";
echo "File URL: " . $service->getTemplateFileUrl($template) . "\n";
