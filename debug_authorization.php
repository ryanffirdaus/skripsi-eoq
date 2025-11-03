<?php

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Get R04 user
$r04 = \App\Models\User::where('role_id', 'R04')->first();
echo "R04 User: " . $r04->nama_lengkap . " (ID: " . $r04->user_id . ")\n\n";

// Get pengadaan with pending_supplier_allocation
$pengadaan = \App\Models\Pengadaan::where('status', 'pending_supplier_allocation')->first();
echo "Pengadaan: " . $pengadaan->pengadaan_id . " (Status: " . $pengadaan->status . ")\n\n";

// Now test authorization - simulate being R04
\Illuminate\Support\Facades\Auth::login($r04);

// Create a mock controller instance to test methods
$controller = new class {
    use \App\Http\Traits\PengadaanAuthorization;
    use \App\Http\Traits\RoleAccess;
};

echo "=== Authorization Tests for R04 ===\n";
echo "isAdmin(): " . ($controller->isAdmin() ? 'TRUE' : 'FALSE') . "\n";
echo "canEditPengadaan(): " . ($controller->canEditPengadaan($pengadaan) ? 'TRUE' : 'FALSE') . "\n";
echo "canEditPengadaanDetail(): " . ($controller->canEditPengadaanDetail($pengadaan) ? 'TRUE' : 'FALSE') . "\n";
echo "canApprovePengadaan(): " . ($controller->canApprovePengadaan($pengadaan) ? 'TRUE' : 'FALSE') . "\n\n";

// Check what Auth::user() returns
$currentUser = \Illuminate\Support\Facades\Auth::user();
echo "Current Auth User: " . $currentUser->nama_lengkap . " (Role: " . $currentUser->role_id . ")\n";
