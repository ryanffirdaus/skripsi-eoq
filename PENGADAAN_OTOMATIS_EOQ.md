# Sistem Pengadaan Otomatis EOQ

## Ringkasan Perubahan

Sistem pengadaan telah diubah dari sistem manual menjadi sistem otomatis berdasarkan Economic Order Quantity (EOQ) dengan monitoring real-time menggunakan Observer Pattern.

## Fitur Utama

### 1. Pengadaan Otomatis

- **Tidak ada lagi pilihan "Manual"** dalam jenis pengadaan
- Hanya tersedia: "ROP" (Reorder Point) dan "Pesanan"
- **Quantity otomatis menggunakan EOQ** dari master data bahan baku/produk
- **Harga otomatis** menggunakan harga standard dari master data

### 2. Monitoring Real-time dengan Observer

Sistem menggunakan Observer Pattern untuk monitoring perubahan stok:

#### BahanBakuObserver

- Memantau perubahan `stok_bahan`
- Trigger otomatis ketika stok <= (ROP + Safety Stock)
- Cek apakah sudah ada pengadaan pending untuk item tersebut

#### ProdukObserver

- Memantau perubahan `stok_produk`
- Trigger otomatis ketika stok <= (ROP + Safety Stock)
- Cek apakah sudah ada pengadaan pending untuk item tersebut

#### PengirimanObserver

- Memantau perubahan status pengiriman
- Ketika status = 'delivered', otomatis mengurangi stok
- Pengurangan stok akan trigger BahanBaku/ProdukObserver

### 3. Job Queue untuk Pengadaan

- `CreateAutomaticPengadaan` job dapat dipanggil dengan parameter spesifik
- Mendukung batch processing atau item spesifik
- Logging lengkap untuk audit trail

## Komponen yang Dibuat/Dimodifikasi

### Observer Classes

- `app/Observers/BahanBakuObserver.php`
- `app/Observers/ProdukObserver.php`
- `app/Observers/PengirimanObserver.php`

### Job Classes

- `app/Jobs/CreateAutomaticPengadaan.php` (updated)

### Command Classes

- `app/Console/Commands/CheckReorderPoints.php` (untuk cron job backup)
- `app/Console/Commands/TestStockReduction.php` (untuk testing)

### Provider Registration

- `app/Providers/AppServiceProvider.php` - register observers

### Frontend Changes

- `resources/js/pages/pengadaan/create.tsx` - simplified form
- Hilangkan input manual quantity dan harga
- Tampilkan informasi EOQ, ROP, dan Safety Stock
- Warning untuk item dengan stok kritis

### Controller Updates

- `app/Http/Controllers/PengadaanController.php`
- Validation update (hilangkan qty_diminta, harga_satuan manual)
- Auto-calculate quantity menggunakan EOQ
- Auto-calculate harga menggunakan harga standard

## Cara Kerja Sistem

### Flow Pengadaan Otomatis

1. **Trigger Event**: Stok berkurang (pengiriman delivered, adjustment manual, dll)
2. **Observer Detection**: Observer mendeteksi perubahan stok
3. **Threshold Check**: Cek apakah stok <= (ROP + Safety Stock)
4. **Duplicate Check**: Cek apakah sudah ada pengadaan pending
5. **Job Dispatch**: Jika perlu, dispatch CreateAutomaticPengadaan job
6. **Pengadaan Creation**: Buat pengadaan dengan qty = EOQ, status = pending

### Prioritas Otomatis

Sistem menentukan prioritas berdasarkan tingkat stok:

- **Urgent**: Stok <= Safety Stock
- **High**: Stok <= 50% dari (ROP + Safety Stock)
- **Normal**: Stok rendah tapi masih aman

## Testing Commands

### Test Stock Reduction

```bash
# Test bahan baku
php artisan test:stock-reduction --type=bahan_baku --qty=50

# Test produk spesifik
php artisan test:stock-reduction --type=produk --id=PP001 --qty=20

# Test dengan ID spesifik
php artisan test:stock-reduction --type=bahan_baku --id=BB007 --qty=100
```

### Manual Trigger (Backup)

```bash
# Trigger manual check semua item
php artisan pengadaan:check-reorder
```

## Keuntungan Sistem Baru

1. **Real-time Response**: Pengadaan langsung dibuat ketika stok mencapai threshold
2. **Optimal Quantity**: Menggunakan EOQ untuk efisiensi biaya
3. **Consistent Pricing**: Harga standard dari master data
4. **Audit Trail**: Logging lengkap untuk tracking
5. **No Manual Errors**: Eliminasi kesalahan input manual
6. **Priority Management**: Prioritas otomatis berdasarkan tingkat kritis stok

## Monitoring & Logs

- Semua aktivitas tercatat di `storage/logs/laravel.log`
- Monitor perubahan stok dan trigger pengadaan
- Error handling untuk edge cases
- Performance tracking untuk optimization

## Database Schema Requirements

Pastikan tabel memiliki kolom yang diperlukan:

- `bahan_baku`: `eoq_bahan`, `safety_stock_bahan`, `rop_bahan`
- `produk`: `eoq_produk`, `safety_stock_produk`, `rop_produk`
- `pengadaan`: Support status workflow dan jenis_pengadaan
- `pengadaan_detail`: Item type, quantities, dan pricing

## Future Enhancements

1. **Machine Learning**: Prediksi demand dan adjustment EOQ otomatis
2. **Supplier Integration**: API integration untuk real-time pricing
3. **Advanced Analytics**: Dashboard untuk monitoring performa sistem
4. **Multi-location**: Support untuk multiple warehouse locations
5. **Approval Workflow**: Configurable approval untuk pengadaan high-value
