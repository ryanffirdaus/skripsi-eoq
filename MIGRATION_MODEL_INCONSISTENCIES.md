# Migration vs Model Inconsistencies Report

## Tanggal: 3 November 2025

### 📋 Ringkasan Inkonsistensi Ditemukan

| Model                     | Prefix di Model        | Prefix di Migration | Max Length Model | Max Length Migration | Status            |
| ------------------------- | ---------------------- | ------------------- | ---------------- | -------------------- | ----------------- |
| Produk                    | **PP** (PP001)         | **PR** (PR001)      | 5 char           | 7 char               | ❌ KONFLIK        |
| Pelanggan                 | **CU** (CU001)         | **PL** (PL001)      | 5 char           | 8 char               | ❌ KONFLIK        |
| Pemasok                   | **PMS** (PMS0000001)   | **PM** (PM001)      | 10 char          | 8 char               | ❌ KONFLIK        |
| Pengadaan                 | **PGD** (PGD0000001)   | **PA** (PA001)      | 10 char          | 10 char              | ❌ KONFLIK PREFIX |
| PesananDetail             | **PSND** (PSND0000001) | **PSD** (PSD001)    | 11 char          | 12 char              | ❌ KONFLIK        |
| PengadaanDetail           | **PGDD** (PGDD0000001) | **PAD** (PAD001)    | 11 char          | 12 char              | ❌ KONFLIK        |
| Pembelian                 | **PO-YYYYMM-XXXX**     | **PB** (PB001)      | 15 char          | 10 char              | ❌ KONFLIK FORMAT |
| PembelianDetail           | **PBLD** (PBLD0000001) | **PBD** (PBD001)    | 11 char          | 12 char              | ❌ KONFLIK        |
| PenerimaanBahanBaku       | **RBM** (RBM0000001)   | **PN** (PN001)      | 10 char          | 10 char              | ❌ KONFLIK PREFIX |
| PenerimaanBahanBakuDetail | **RBMD** (RBMD0000001) | **PND** (PND001)    | 11 char          | 12 char              | ❌ KONFLIK        |
| TransaksiPembayaran       | **TRP** (TRP00000001)  | **TP** (TP001)      | 11 char          | 12 char              | ❌ KONFLIK        |
| PenugasanProduksi         | **PPD** (PPD00001)     | **PT** (PT001)      | 8 char           | 10 char              | ❌ KONFLIK        |

---

## 🔧 Detail Perbaikan yang Dibutuhkan

### 1. **Produk Model**

- **Model saat ini**: `'PP' . str_pad($nextId, 3, '0', STR_PAD_LEFT)` → PP001
- **Migration**: length 7
- **Rekomendasi**: Tetap PP001 (5 char sudah cukup)
- **Action**: Update migration dari 7 ke **5**

### 2. **Pelanggan Model**

- **Model saat ini**: `'CU' . str_pad($nextId, 3, '0', STR_PAD_LEFT)` → CU001
- **Migration**: length 8, expecting PL001
- **Rekomendasi**: Ubah model ke PL (5 char)
- **Action**: Update model prefix dari CU ke **PL** dan migration dari 8 ke **5**

### 3. **Pemasok Model**

- **Model saat ini**: `'PMS' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT)` → PMS0000001 (10 char)
- **Migration**: length 8, expecting PM001
- **Rekomendasi**: Ubah model ke PM dengan padding 3 (5 char)
- **Action**: Update model prefix dari PMS ke **PM**, padding dari 7 ke **3**, dan migration dari 8 ke **5**

### 4. **Pengadaan Model**

- **Model saat ini**: `'PGD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT)` → PGD0000001 (10 char)
- **Migration**: length 10, expecting PA001
- **Rekomendasi**: Ubah model ke PA dengan padding 3 (5 char untuk start, tapi migration 10 char untuk skalabilitas)
- **Action**: Update model prefix dari PGD ke **PA**, padding dari 7 ke **7** (PA0000001), migration tetap 10

### 5. **PesananDetail Model**

- **Model saat ini**: `'PSND' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT)` → PSND0000001 (11 char)
- **Migration**: length 12, expecting PSD001
- **Rekomendasi**: Ubah model ke PSD dengan padding 7 untuk volume besar
- **Action**: Update model prefix dari PSND ke **PSD**, migration tetap 12

### 6. **PengadaanDetail Model**

- **Model saat ini**: `'PGDD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT)` → PGDD0000001 (11 char)
- **Migration**: length 12, expecting PAD001
- **Rekomendasi**: Ubah model ke PAD dengan padding 7 untuk volume besar
- **Action**: Update model prefix dari PGDD ke **PAD**, migration tetap 12

### 7. **Pembelian Model** (CRITICAL!)

- **Model saat ini**: `"PO-" . $yearMonth . "-" . str_pad($nextPoNumber, 4, '0', STR_PAD_LEFT)` → PO-202511-0001 (15 char)
- **Migration**: length 10, expecting PB001
- **Rekomendasi**: Ubah model ke format PB dengan Year-Month atau tetap PO format
- **Options**:
    - Option A: Simple format PB001 (5 char) - sederhana
    - Option B: PO-YYMM-XXXX (13 char) - dengan year-month
- **Action**: PERLU DISKUSI, saya sarankan **Option B dengan migration 15 char**

### 8. **PembelianDetail Model**

- **Model saat ini**: `'PBLD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT)` → PBLD0000001 (11 char)
- **Migration**: length 12, expecting PBD001
- **Rekomendasi**: Ubah model ke PBD dengan padding 7 untuk volume besar
- **Action**: Update model prefix dari PBLD ke **PBD**, migration tetap 12

### 9. **PenerimaanBahanBaku Model**

- **Model saat ini**: `'RBM' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT)` → RBM0000001 (10 char)
- **Migration**: length 10, expecting PN001
- **Rekomendasi**: Ubah model ke PN dengan padding 7 untuk volume besar
- **Action**: Update model prefix dari RBM ke **PN**, migration tetap 10

### 10. **PenerimaanBahanBakuDetail Model**

- **Model saat ini**: `'RBMD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT)` → RBMD0000001 (11 char)
- **Migration**: length 12, expecting PND001
- **Rekomendasi**: Ubah model ke PND dengan padding 7 untuk volume besar
- **Action**: Update model prefix dari RBMD ke **PND**, migration tetap 12

### 11. **TransaksiPembayaran Model**

- **Model saat ini**: `'TRP' . str_pad($nextNumber, 8, '0', STR_PAD_LEFT)` → TRP00000001 (11 char)
- **Migration**: length 12, expecting TP001
- **Rekomendasi**: Ubah model ke TP dengan padding 8 untuk volume besar
- **Action**: Update model prefix dari TRP ke **TP**, migration tetap 12

### 12. **PenugasanProduksi Model**

- **Model saat ini**: `'PPD' . str_pad($nextId, 5, '0', STR_PAD_LEFT)` → PPD00001 (8 char)
- **Migration**: length 10, expecting PT001
- **Rekomendasi**: Ubah model ke PT dengan padding 5-7 untuk volume
- **Action**: Update model prefix dari PPD ke **PT**, padding 7, migration tetap 10

---

## 📊 Standar Baru ID Format

### Master Data (Volume Kecil-Medium)

```
US001    - User (5 char)
RL001    - Role (5 char)
BB001    - Bahan Baku (5 char)
PP001    - Produk (5 char)
PL001    - Pelanggan (5 char)
PM001    - Pemasok (5 char)
```

### Transaksi Header (Volume Medium)

```
PS001      - Pesanan (5 char)
PG001      - Pengiriman (5 char)
PA0000001  - Pengadaan (10 char) - bisa sangat banyak
```

### Transaksi Detail (Volume Besar)

```
PSD0000001 - Pesanan Detail (11 char)
PAD0000001 - Pengadaan Detail (11 char)
PN0000001  - Penerimaan (10 char)
PND0000001 - Penerimaan Detail (11 char)
PT0000001  - Penugasan Produksi (10 char)
```

### Purchase Order (Format Khusus)

```
PO-2511-0001 - Pembelian/PO dengan Year-Month (13 char)
PBD0000001   - Pembelian Detail (11 char)
TP00000001   - Transaksi Pembayaran (11 char)
```

---

## ✅ Migration Files - COMPLETED

Semua migration files sudah diperbaiki dengan length yang sesuai:

| Table                        | ID Column                | Old Length | New Length | Format Example |
| ---------------------------- | ------------------------ | ---------- | ---------- | -------------- |
| bahan_baku                   | bahan_baku_id            | 7          | **5**      | BB001          |
| produk                       | produk_id                | 7          | **5**      | PP001          |
| bahan_produksi               | produk_id, bahan_baku_id | 7          | **5**      | PP001, BB001   |
| pelanggan                    | pelanggan_id             | 8          | **5**      | PL001          |
| pemasok                      | pemasok_id               | 8          | **5**      | PM001          |
| pesanan                      | pesanan_id               | 10         | **5**      | PS001          |
| pesanan                      | pelanggan_id             | 8          | **5**      | PL001          |
| pesanan_detail               | pesanan_detail_id        | 12         | **11**     | PSD0000001     |
| pesanan_detail               | pesanan_id               | 10         | **5**      | PS001          |
| pesanan_detail               | produk_id                | 7          | **5**      | PP001          |
| pengiriman                   | pengiriman_id            | 10         | **5**      | PG001          |
| pengiriman                   | pesanan_id               | 10         | **5**      | PS001          |
| pengadaan                    | pengadaan_id             | 10         | **10**     | PA0000001      |
| pengadaan                    | pesanan_id               | 10         | **5**      | PS001          |
| pengadaan_detail             | pengadaan_detail_id      | 12         | **11**     | PAD0000001     |
| pengadaan_detail             | pengadaan_id             | 10         | **10**     | PA0000001      |
| pengadaan_detail             | pemasok_id               | 8          | **5**      | PM001          |
| pengadaan_detail             | barang_id                | 7          | **5**      | BB001/PP001    |
| pembelian                    | pembelian_id             | 10         | **15**     | PO-2511-0001   |
| pembelian                    | pengadaan_id             | 10         | **10**     | PA0000001      |
| pembelian                    | pemasok_id               | 8          | **5**      | PM001          |
| pembelian_detail             | pembelian_detail_id      | 12         | **11**     | PBD0000001     |
| pembelian_detail             | pembelian_id             | 10         | **15**     | PO-2511-0001   |
| pembelian_detail             | pengadaan_detail_id      | 12         | **11**     | PAD0000001     |
| penerimaan_bahan_baku        | penerimaan_id            | 10         | **10**     | PN0000001      |
| penerimaan_bahan_baku        | pembelian_detail_id      | 12         | **11**     | PBD0000001     |
| penerimaan_bahan_baku_detail | penerimaan_detail_id     | 50         | **11**     | PND0000001     |
| penerimaan_bahan_baku_detail | penerimaan_id            | 50         | **10**     | PN0000001      |
| penerimaan_bahan_baku_detail | pembelian_detail_id      | 50         | **11**     | PBD0000001     |
| penerimaan_bahan_baku_detail | bahan_baku_id            | 50         | **5**      | BB001          |
| transaksi_pembayaran         | transaksi_pembayaran_id  | 12         | **11**     | TP00000001     |
| transaksi_pembayaran         | pembelian_id             | 10         | **15**     | PO-2511-0001   |
| penugasan_produksi           | penugasan_id             | 10         | **10**     | PT0000001      |
| penugasan_produksi           | pengadaan_detail_id      | 12         | **11**     | PAD0000001     |

---

## 📋 Next Actions - MODEL UPDATES NEEDED

**CRITICAL**: Model files masih menggunakan format lama! Perlu update:

### Models yang Perlu Diubah:

1. ✅ **User.php** - Sudah benar (US001)
2. ✅ **Role.php** - Sudah benar (RL001)
3. ✅ **BahanBaku.php** - Sudah benar (BB001)
4. ❌ **Produk.php** - Ubah PP ke PP (sudah benar tapi perlu verifikasi)
5. ❌ **Pelanggan.php** - Ubah CU ke **PL**
6. ❌ **Pemasok.php** - Ubah PMS + padding 7 ke **PM + padding 3**
7. ✅ **Pesanan.php** - Sudah benar (PS001)
8. ❌ **PesananDetail.php** - Ubah PSND + padding 7 ke **PSD + padding 7**
9. ✅ **Pengiriman.php** - Sudah benar (PG001)
10. ❌ **Pengadaan.php** - Ubah PGD + padding 7 ke **PA + padding 7**
11. ❌ **PengadaanDetail.php** - Ubah PGDD + padding 7 ke **PAD + padding 7**
12. ❌ **Pembelian.php** - Tetap PO-YYYYMM-XXXX (sudah benar, cuma ganti YYYYMM ke YYMM)
13. ❌ **PembelianDetail.php** - Ubah PBLD + padding 7 ke **PBD + padding 7**
14. ❌ **PenerimaanBahanBaku.php** - Ubah RBM + padding 7 ke **PN + padding 7**
15. ❌ **PenerimaanBahanBakuDetail.php** - Ubah RBMD + padding 7 ke **PND + padding 7**
16. ❌ **TransaksiPembayaran.php** - Ubah TRP + padding 8 ke **TP + padding 8**
17. ❌ **PenugasanProduksi.php** - Ubah PPD + padding 5 ke **PT + padding 7**

### Actions:

1. ✅ **Update Migration Files** - COMPLETED
2. ⏳ **Update Model Files** - PENDING (Perlu update 12 models)
3. ⏳ **Update Factories** - PENDING
4. ⏳ **Update Seeders** - PENDING
5. ⏳ **Run Migration Fresh** - PENDING
6. ⏳ **Test Full Flow** - PENDING

---

**IMPORTANT NOTE**: Migration files sudah diperbaiki. Sekarang perlu update Models agar match dengan migration!
