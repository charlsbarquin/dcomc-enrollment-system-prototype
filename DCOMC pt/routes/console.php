<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily database auto backup at 5:00 PM (only runs if admin enabled it in System → Backup)
Schedule::command('backup:database')->dailyAt('17:00');
