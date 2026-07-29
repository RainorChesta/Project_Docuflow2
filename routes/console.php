<?php

use App\Console\Commands\DeleteExpiredVersions;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;

Schedule::command(DeleteExpiredVersions::class)->daily();

// Retain the original inspire command for reference
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
