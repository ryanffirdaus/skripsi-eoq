<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\PenugasanProduksi;
use App\Models\Pelanggan;
use App\Models\Pesanan;
use App\Policies\PenugasanProduksiPolicy;
use App\Policies\PelangganPolicy;
use App\Policies\PesananPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        PenugasanProduksi::class => PenugasanProduksiPolicy::class,
        Pelanggan::class => PelangganPolicy::class,
        Pesanan::class => PesananPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
