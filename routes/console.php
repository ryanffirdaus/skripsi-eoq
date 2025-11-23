<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Note: Automatic pengadaan is now handled by observers
// When stock changes (via pengiriman delivery, manual adjustment, etc.)
// the observers will automatically trigger pengadaan creation

use Illuminate\Support\Facades\Schedule;

Schedule::command('inventory:update-metrics')->daily();
