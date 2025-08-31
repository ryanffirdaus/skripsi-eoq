<?php

namespace App\Console\Commands;

use App\Jobs\CreateAutomaticPengadaan;
use Illuminate\Console\Command;

class CheckReorderPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pengadaan:check-reorder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check reorder points and create automatic pengadaan when stock is low';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking reorder points...');

        // Dispatch job untuk create automatic pengadaan
        CreateAutomaticPengadaan::dispatch();

        $this->info('Reorder points check completed and automatic pengadaan job dispatched.');

        return 0;
    }
}
