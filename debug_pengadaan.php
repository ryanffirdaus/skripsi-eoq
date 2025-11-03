<?php

define('LARAVEL_START', microtime(true));

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Get all pengadaan
$pengadaans = \App\Models\Pengadaan::select('pengadaan_id', 'status', 'created_at')->orderBy('created_at', 'desc')->limit(10)->get();

echo "=== Last 10 Pengadaan ===\n";
foreach ($pengadaans as $p) {
    echo $p->pengadaan_id . " => " . $p->status . " (created: " . $p->created_at->format('Y-m-d H:i:s') . ")\n";
}

// Check if any pending_supplier_allocation exists
$existing = \App\Models\Pengadaan::where('status', 'pending_supplier_allocation')->count();
echo "\n=== Count Status 'pending_supplier_allocation': " . $existing . "\n";

// Check all distinct statuses
$statuses = \App\Models\Pengadaan::select('status')->distinct()->get();
echo "\n=== All Distinct Statuses in Database ===\n";
foreach ($statuses as $s) {
    echo "- " . $s->status . "\n";
}

// Check user roles
echo "\n=== User Roles ===\n";
$users = \App\Models\User::select('user_id', 'nama_lengkap', 'role_id')->get();
foreach ($users as $u) {
    echo $u->user_id . " => " . $u->nama_lengkap . " (role: " . $u->role_id . ")\n";
}
