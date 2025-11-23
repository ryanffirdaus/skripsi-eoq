<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\InventoryCalculationService;

class UpdateInventoryMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:update-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update EOQ, ROP, and Safety Stock based on recent data';

    /**
     * Execute the console command.
     */
    public function handle(InventoryCalculationService $service)
    {
        $this->info('Starting inventory metrics update...');
        
        $service->updateAllMetrics();
        
        $this->info('Inventory metrics updated successfully!');
    }
}
