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

// Schedule automatic inventory metrics update
// Runs daily at midnight to recalculate EOQ, ROP, and Safety Stock based on historical data
Schedule::command('inventory:update-metrics')
    ->weekly()
    ->mondays()
    ->at('00:00')
    ->appendOutputTo(storage_path('logs/inventory-metrics-update.log'));
