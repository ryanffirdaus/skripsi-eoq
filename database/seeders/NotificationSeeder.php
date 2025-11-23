<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        $adminUser = User::where('role_id', 'R01')->first();
        $gudangUser = User::where('role_id', 'R02')->first();
        $penjualanUser = User::where('role_id', 'R05')->first();
        $pengadaanUser = User::where('role_id', 'R04')->first();
        $keuanganUser = User::where('role_id', 'R06')->first();

        $notifications = [
            // Notifications for Admin
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\SystemNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $adminUser?->user_id,
                'data' => json_encode([
                    'title' => 'Dashboard Baru Tersedia',
                    'message' => 'Dashboard interaktif dengan grafik dan KPI telah diaktifkan.',
                    'type' => 'info',
                    'url' => '/dashboard'
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\SystemNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $adminUser?->user_id,
                'data' => json_encode([
                    'title' => 'Sistem Berjalan Normal',
                    'message' => 'Semua modul sistem berfungsi dengan baik.',
                    'type' => 'success',
                    'url' => '/dashboard'
                ]),
                'read_at' => now()->subHour(),
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],

            // Notifications for Staf Gudang
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\InventoryNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $gudangUser?->user_id,
                'data' => json_encode([
                    'title' => 'Stok Rendah Terdeteksi',
                    'message' => '5 item bahan baku mencapai stok minimum. Segera lakukan pengadaan.',
                    'type' => 'warning',
                    'url' => '/bahan-baku'
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\ShipmentNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $gudangUser?->user_id,
                'data' => json_encode([
                    'title' => 'Pengiriman Menunggu',
                    'message' => '3 pesanan menunggu untuk dikirim hari ini.',
                    'type' => 'info',
                    'url' => '/pengiriman'
                ]),
                'read_at' => null,
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],

            // Notifications for Staf Penjualan
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\OrderNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $penjualanUser?->user_id,
                'data' => json_encode([
                    'title' => 'Pesanan Baru',
                    'message' => 'Pesanan baru dari pelanggan perlu dikonfirmasi.',
                    'type' => 'info',
                    'url' => '/pesanan'
                ]),
                'read_at' => null,
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\SalesNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $penjualanUser?->user_id,
                'data' => json_encode([
                    'title' => 'Target Penjualan Tercapai',
                    'message' => 'Selamat! Target penjualan bulan ini telah tercapai 100%.',
                    'type' => 'success',
                    'url' => '/dashboard'
                ]),
                'read_at' => now()->subMinutes(15),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],

            // Notifications for Staf Pengadaan
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\ProcurementNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $pengadaanUser?->user_id,
                'data' => json_encode([
                    'title' => 'Pengadaan Perlu Persetujuan',
                    'message' => '2 permintaan pengadaan menunggu persetujuan dari manajer.',
                    'type' => 'warning',
                    'url' => '/pengadaan'
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(4),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\SupplierNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $pengadaanUser?->user_id,
                'data' => json_encode([
                    'title' => 'Pembelian Dikonfirmasi',
                    'message' => 'Purchase Order PO-2511-0001 telah dikonfirmasi supplier.',
                    'type' => 'success',
                    'url' => '/pembelian'
                ]),
                'read_at' => null,
                'created_at' => now()->subMinutes(45),
                'updated_at' => now()->subMinutes(45),
            ],

            // Notifications for Staf Keuangan
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\PaymentNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $keuanganUser?->user_id,
                'data' => json_encode([
                    'title' => 'Pembayaran Jatuh Tempo',
                    'message' => '3 tagihan akan jatuh tempo dalam 3 hari. Segera proses pembayaran.',
                    'type' => 'warning',
                    'url' => '/transaksi-pembayaran'
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\FinanceNotification',
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $keuanganUser?->user_id,
                'data' => json_encode([
                    'title' => 'Laporan Keuangan Siap',
                    'message' => 'Laporan keuangan bulan ini telah dibuat dan siap untuk direview.',
                    'type' => 'info',
                    'url' => '/dashboard'
                ]),
                'read_at' => now()->subDays(1),
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
        ];

        // Filter out notifications with null notifiable_id
        $notifications = array_filter($notifications, function ($notification) {
            return $notification['notifiable_id'] !== null;
        });

        if (!empty($notifications)) {
            DB::table('notifications')->insert($notifications);
        }
    }
}
