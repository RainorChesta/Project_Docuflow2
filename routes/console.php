<?php

use App\Console\Commands\DeleteExpiredVersions;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;

use App\Console\Commands\CheckDocumentExpiration;
use App\Console\Commands\PruneTrashedDocuments;

Schedule::command(DeleteExpiredVersions::class)->daily();
Schedule::command(CheckDocumentExpiration::class)->daily();
Schedule::command(PruneTrashedDocuments::class)->daily();

// Retain the original inspire command for reference
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
