# Migration Length Optimization Summary

## Tanggal: 3 November 2025

Optimasi dilakukan untuk mengatur maximum length yang ketat dan realistis pada setiap kolom di semua migration files.

## Prinsip Optimasi

1. **ID Fields**: Disesuaikan dengan format actual (contoh: US001 = 6 char, BB001 = 7 char)
2. **Email & Nama**: 50 karakter (standar realistis)
3. **Password**: 100 karakter (cukup untuk hash bcrypt)
4. **User Tracking Fields**: 6 karakter (created_by, updated_by, deleted_by)
5. **Detail IDs**: 12 karakter (untuk data yang bisa banyak)
6. **Transaction IDs**: 10-12 karakter tergantung volume ekspektasi
7. **Enum/Status**: 15-30 karakter sesuai nilai terpanjang
8. **Decimal**: 15,2 untuk harga (cukup untuk 999,999,999,999.99)

---

## Detail Optimasi Per Migration

### 1. Users Table (0001_01_01_000000)

**File**: `0001_01_01_000000_create_users_table.php`

| Kolom                              | Before | After   | Format |
| ---------------------------------- | ------ | ------- | ------ |
| user_id                            | 10     | **6**   | US001  |
| nama_lengkap                       | 100    | **50**  | -      |
| email                              | 255    | **50**  | -      |
| password                           | 255    | **100** | hash   |
| created_by, updated_by, deleted_by | 10     | **6**   | US001  |
| session.id                         | 255    | **50**  | -      |
| session.user_id                    | 10     | **6**   | US001  |
| password_reset_tokens.email        | 255    | **50**  | -      |
| password_reset_tokens.token        | 255    | **100** | -      |

---

### 2. Roles Table (2025_08_29_134402)

**File**: `2025_08_29_134402_create_roles_table.php`

| Kolom   | Before | After  | Format |
| ------- | ------ | ------ | ------ |
| role_id | 10     | **6**  | RL001  |
| name    | 50     | **30** | -      |

---

### 3. Add Role to Users (2025_08_29_134929)

**File**: `2025_08_29_134929_add_role_id_to_users_table.php`

| Kolom   | Before | After | Format |
| ------- | ------ | ----- | ------ |
| role_id | 10     | **6** | RL001  |

---

### 4. Bahan Baku Table (2025_08_29_152904)

**File**: `2025_08_29_152904_create_bahan_baku_table.php`

| Kolom                              | Before | After    | Format |
| ---------------------------------- | ------ | -------- | ------ |
| bahan_baku_id                      | 50     | **7**    | BB001  |
| nama_bahan                         | 100    | **50**   | -      |
| lokasi_bahan                       | 100    | **50**   | -      |
| harga_bahan                        | 20,2   | **15,2** | -      |
| biaya_pemesanan_bahan              | 20,2   | **15,2** | -      |
| biaya_penyimpanan_bahan            | 20,2   | **15,2** | -      |
| created_by, updated_by, deleted_by | 50     | **6**    | US001  |

---

### 5. Produk Table (2025_08_30_165241)

**File**: `2025_08_30_165241_create_produk_table.php`

| Kolom                              | Before | After    | Format |
| ---------------------------------- | ------ | -------- | ------ |
| produk_id                          | 50     | **7**    | PR001  |
| nama_produk                        | 100    | **50**   | -      |
| lokasi_produk                      | 100    | **50**   | -      |
| hpp_produk                         | 20,2   | **15,2** | -      |
| harga_jual                         | 20,2   | **15,2** | -      |
| biaya_pemesanan_produk             | 20,2   | **15,2** | -      |
| biaya_penyimpanan_produk           | 20,2   | **15,2** | -      |
| created_by, updated_by, deleted_by | 50     | **6**    | US001  |

---

### 6. Bahan Produksi Table (2025_08_30_165744)

**File**: `2025_08_30_165744_create_bahan_produksi_table.php`

| Kolom         | Before | After | Format |
| ------------- | ------ | ----- | ------ |
| produk_id     | 10     | **7** | PR001  |
| bahan_baku_id | 10     | **7** | BB001  |

---

### 7. Pelanggan Table (2025_08_31_073509)

**File**: `2025_08_31_073509_create_pelanggan_table.php`

| Kolom                              | Before | After  | Format |
| ---------------------------------- | ------ | ------ | ------ |
| pelanggan_id                       | 50     | **8**  | PL001  |
| email_pelanggan                    | 255    | **50** | -      |
| nama_pelanggan                     | 100    | **50** | -      |
| nomor_telepon                      | 20     | **15** | -      |
| created_by, updated_by, deleted_by | 50     | **6**  | US001  |

---

### 8. Pesanan Table (2025_08_31_082847)

**File**: `2025_08_31_082847_create_pesanan_table.php`

| Kolom                              | Before | After    | Format |
| ---------------------------------- | ------ | -------- | ------ |
| pesanan_id                         | 50     | **10**   | PS001  |
| pelanggan_id                       | 50     | **8**    | PL001  |
| total_harga                        | 25,2   | **15,2** | -      |
| created_by, updated_by, deleted_by | 50     | **6**    | US001  |

---

### 9. Pesanan Detail Table (2025_08_31_092021)

**File**: `2025_08_31_092021_create_pesanan_detail_table.php`

| Kolom             | Before | After    | Format |
| ----------------- | ------ | -------- | ------ |
| pesanan_detail_id | 50     | **12**   | PSD001 |
| pesanan_id        | 50     | **10**   | PS001  |
| produk_id         | 50     | **7**    | PR001  |
| harga_satuan      | 25,2   | **15,2** | -      |
| subtotal          | 25,2   | **15,2** | -      |

---

### 10. Pengiriman Table (2025_08_31_123155)

**File**: `2025_08_31_123155_create_pengiriman_table.php`

| Kolom                              | Before | After    | Format |
| ---------------------------------- | ------ | -------- | ------ |
| pengiriman_id                      | 50     | **10**   | PG001  |
| pesanan_id                         | 50     | **10**   | PS001  |
| nomor_resi                         | 50     | **30**   | -      |
| biaya_pengiriman                   | 15,2   | **12,2** | -      |
| created_by, updated_by, deleted_by | 50     | **6**    | US001  |

---

### 11. Pemasok Table (2025_08_31_132800)

**File**: `2025_08_31_132800_create_pemasok_table.php`

| Kolom                              | Before | After  | Format |
| ---------------------------------- | ------ | ------ | ------ |
| pemasok_id                         | 50     | **8**  | PM001  |
| nama_pemasok                       | 100    | **50** | -      |
| narahubung                         | 100    | **50** | -      |
| email                              | 255    | **50** | -      |
| nomor_telepon                      | 20     | **15** | -      |
| created_by, updated_by, deleted_by | 50     | **6**  | US001  |

---

### 12. Pengadaan Table (2025_08_31_132818)

**File**: `2025_08_31_132818_create_pengadaan_table.php`

| Kolom                              | Before | After  | Format |
| ---------------------------------- | ------ | ------ | ------ |
| pengadaan_id                       | 50     | **10** | PA001  |
| pesanan_id                         | 50     | **10** | PS001  |
| created_by, updated_by, deleted_by | 50     | **6**  | US001  |

---

### 13. Pengadaan Detail Table (2025_08_31_132852)

**File**: `2025_08_31_132852_create_pengadaan_detail_table.php`

| Kolom               | Before | After    | Format      |
| ------------------- | ------ | -------- | ----------- |
| pengadaan_detail_id | 50     | **12**   | PAD001      |
| pengadaan_id        | 50     | **10**   | PA001       |
| pemasok_id          | 50     | **8**    | PM001       |
| barang_id           | 50     | **7**    | BB001/PR001 |
| harga_satuan        | 20,2   | **15,2** | -           |

---

### 14. Pembelian Table (2025_08_31_154934)

**File**: `2025_08_31_154934_create_pembelian_table.php`

| Kolom                              | Before | After  | Format |
| ---------------------------------- | ------ | ------ | ------ |
| pembelian_id                       | 50     | **10** | PB001  |
| pengadaan_id                       | 50     | **10** | PA001  |
| pemasok_id                         | 50     | **8**  | PM001  |
| metode_pembayaran                  | 20     | **15** | -      |
| status                             | 30     | **25** | -      |
| created_by, updated_by, deleted_by | 50     | **6**  | US001  |

---

### 15. Pembelian Detail Table (2025_08_31_154948)

**File**: `2025_08_31_154948_create_pembelian_detail_table.php`

| Kolom               | Before | After  | Format |
| ------------------- | ------ | ------ | ------ |
| pembelian_detail_id | 50     | **12** | PBD001 |
| pembelian_id        | 50     | **10** | PB001  |
| pengadaan_detail_id | 50     | **12** | PAD001 |

---

### 16. Penerimaan Bahan Baku Table (2025_09_05_171600)

**File**: `2025_09_05_171600_create_penerimaan_bahan_baku_table.php`

| Kolom                              | Before | After  | Format |
| ---------------------------------- | ------ | ------ | ------ |
| penerimaan_id                      | 50     | **10** | PN001  |
| pembelian_detail_id                | 50     | **12** | PBD001 |
| created_by, updated_by, deleted_by | 50     | **6**  | US001  |

---

### 17. Penerimaan Bahan Baku Detail Table (2025_09_07_100043)

**File**: `2025_09_07_100043_create_penerimaan_bahan_baku_detail_table.php`

| Kolom                | Before | After  | Format |
| -------------------- | ------ | ------ | ------ |
| penerimaan_detail_id | 50     | **12** | PND001 |
| penerimaan_id        | 50     | **10** | PN001  |
| pembelian_detail_id  | 50     | **12** | PBD001 |
| bahan_baku_id        | 50     | **7**  | BB001  |

---

### 18. Transaksi Pembayaran Table (2025_09_17_161137)

**File**: `2025_09_17_161137_create_transaksi_pembayaran_table.php`

| Kolom                              | Before | After   | Format |
| ---------------------------------- | ------ | ------- | ------ |
| transaksi_pembayaran_id            | 50     | **12**  | TP001  |
| pembelian_id                       | 50     | **10**  | PB001  |
| bukti_pembayaran                   | 255    | **100** | -      |
| created_by, updated_by, deleted_by | 50     | **6**   | US001  |

---

### 19. Penugasan Produksi Table (2025_10_19)

**File**: `2025_10_19_create_penugasan_produksi.php`

| Kolom                              | Before | After  | Format |
| ---------------------------------- | ------ | ------ | ------ |
| penugasan_id                       | 50     | **10** | PT001  |
| pengadaan_detail_id                | 50     | **12** | PAD001 |
| user_id                            | 50     | **6**  | US001  |
| created_by, updated_by, deleted_by | 50     | **6**  | US001  |

---

### 20. Cache Table (0001_01_01_000001)

**File**: `0001_01_01_000001_create_cache_table.php`

| Kolom             | Before | After   | Format |
| ----------------- | ------ | ------- | ------ |
| cache.key         | 255    | **100** | -      |
| cache_locks.key   | 255    | **100** | -      |
| cache_locks.owner | 255    | **100** | -      |

---

### 21. Jobs Table (0001_01_01_000002)

**File**: `0001_01_01_000002_create_jobs_table.php`

| Kolom                  | Before | After   | Format |
| ---------------------- | ------ | ------- | ------ |
| jobs.queue             | 255    | **50**  | -      |
| job_batches.id         | 255    | **50**  | -      |
| job_batches.name       | 255    | **100** | -      |
| failed_jobs.uuid       | 255    | **50**  | -      |
| failed_jobs.connection | text   | **50**  | -      |
| failed_jobs.queue      | text   | **50**  | -      |

---

## Kesimpulan

### Total Optimasi

- **21 migration files** dioptimalkan
- **Penghematan storage** yang signifikan
- **Konsistensi** ID format di semua tabel
- **Validasi lebih ketat** di database level

### ID Format Standar

```
US001   - User (6 char)
RL001   - Role (6 char)
BB001   - Bahan Baku (7 char)
PR001   - Produk (7 char)
PL001   - Pelanggan (8 char)
PM001   - Pemasok (8 char)
PS001   - Pesanan (10 char)
PG001   - Pengiriman (10 char)
PA001   - Pengadaan (10 char)
PB001   - Pembelian (10 char)
PN001   - Penerimaan (10 char)
PT001   - Penugasan (10 char)
PSD001  - Pesanan Detail (12 char)
PAD001  - Pengadaan Detail (12 char)
PBD001  - Pembelian Detail (12 char)
PND001  - Penerimaan Detail (12 char)
TP001   - Transaksi Pembayaran (12 char)
```

### Field Length Standar

```
Email: 50
Nama/Name: 50
Password: 100
Telepon: 15
Decimal (Harga): 15,2
Decimal (Biaya Kecil): 12,2
Status/Enum: 15-30 (sesuai kebutuhan)
Queue/Connection: 50
Token/Hash: 100
File Path: 100
```

### Next Steps

1. ✅ Run `php artisan migrate:fresh` untuk apply perubahan
2. ✅ Update semua Factories untuk generate ID sesuai format baru
3. ✅ Update Seeders
4. ✅ Test aplikasi secara menyeluruh

---

**Catatan**: Semua perubahan telah disesuaikan untuk mendukung ID string dengan format konsisten dan length yang ketat namun realistis.
