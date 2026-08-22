<?php

use App\Console\Commands\DeleteExpiredVersions;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;

use App\Console\Commands\CheckDocumentExpiration;

Schedule::command(DeleteExpiredVersions::class)->daily();
Schedule::command(CheckDocumentExpiration::class)->daily();

// Retain the original inspire command for reference
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
