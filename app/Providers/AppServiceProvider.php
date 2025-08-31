<?php

namespace App\Providers;

use App\Models\BahanBaku;
use App\Models\Produk;
use App\Models\Pengiriman;
use App\Observers\BahanBakuObserver;
use App\Observers\ProdukObserver;
use App\Observers\PengirimanObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        BahanBaku::observe(BahanBakuObserver::class);
        Produk::observe(ProdukObserver::class);
        Pengiriman::observe(PengirimanObserver::class);
    }
}
