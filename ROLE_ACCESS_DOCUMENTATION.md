# Dokumentasi Role-Based Access Control (RBAC)

## Ringkasan Implementasi

Telah diimplementasikan sistem kontrol akses berbasis peran yang komprehensif untuk seluruh aplikasi. Sistem ini mencakup middleware untuk validasi akses umum dan trait untuk authorization logic khusus yang lebih kompleks.

---

## Daftar Role

| Role ID | Nama              | Keterangan                                           |
| ------- | ----------------- | ---------------------------------------------------- |
| R01     | Admin             | Akses penuh ke semua modul dan fitur                 |
| R02     | Staf Gudang       | Kelola stok, buat dan hapus pengadaan pending        |
| R03     | Staf RnD          | Kelola penugasan produksi                            |
| R04     | Staf Pengadaan    | Kelola detail pemasok & harga untuk pengadaan        |
| R05     | Staf Penjualan    | Kelola pelanggan dan pesanan                         |
| R06     | Staf Keuangan     | Kelola pembelian dan transaksi pembayaran            |
| R07     | Manajer Gudang    | CRUD penuh gudang, approve pengadaan tahap gudang    |
| R08     | Manajer RnD       | CRUD penugasan produksi                              |
| R09     | Manajer Pengadaan | CRUD pemasok, approve pengadaan tahap pengadaan      |
| R10     | Manajer Keuangan  | CRUD pembelian dan transaksi, approve tahap keuangan |

---

## Akses per Modul

### 1. Users (Manajemen Pengguna)

**CRUD hanya untuk Admin (R01)**

- Index: ✓ R01
- Create: ✓ R01
- Show: ✓ R01
- Edit: ✓ R01
- Update: ✓ R01
- Destroy: ✓ R01

### 2. Bahan Baku

**CRUD untuk Staf/Manajer Gudang, View untuk yang lain**

- Index/Show: ✓ R01, R02, R07, R08
- Create/Edit/Update/Destroy: ✓ R01, R07

### 3. Produk

**CRUD untuk Staf/Manajer Gudang, View untuk yang lain**

- Index/Show: ✓ R01, R02, R05, R07, R08
- Create/Edit/Update/Destroy: ✓ R01, R07

### 4. Pelanggan

**CRUD hanya untuk Staf Penjualan (R05)**

- Index/Create/Show/Edit/Update/Destroy: ✓ R01, R05

### 5. Pesanan

**CRUD hanya untuk Staf Penjualan (R05)**

- Index/Create/Show/Edit/Update/Destroy: ✓ R01, R05

### 6. Pemasok

**CRUD untuk Staf/Manajer Pengadaan**

- Index/Create/Show/Edit/Update/Destroy: ✓ R01, R04, R09

### 7. Pengiriman

**CRUD untuk Staf/Manajer Gudang**

- Index/Create/Show/Edit/Update/Destroy: ✓ R01, R02, R07

### 8. Pengadaan

**Complex multi-stage approval process**

#### 8a. Status Workflow

```
draft → pending_approval_gudang → disetujui_gudang → disetujui_pengadaan → disetujui_keuangan → diproses → diterima
```

#### 8b. Role Access by Status

| Status                  | Staf Gudang (R02)    | Manajer Gudang (R07)       | Staf Pengadaan (R04) | Manajer Pengadaan (R09)      | Staf Keuangan (R06) | Manajer Keuangan (R10) |
| ----------------------- | -------------------- | -------------------------- | -------------------- | ---------------------------- | ------------------- | ---------------------- |
| draft                   | Create, View, Delete | Create, View, Delete       | View                 | View                         | View                | View                   |
| pending_approval_gudang | View                 | Approve → disetujui_gudang | View                 | View                         | View                | View                   |
| disetujui_gudang        | View                 | View                       | Edit pemasok/harga   | Edit pemasok/harga           | View                | View                   |
| disetujui_pengadaan     | View                 | View                       | View                 | Approve → disetujui_keuangan | View                | View                   |
| disetujui_keuangan      | View                 | View                       | View                 | View                         | View                | Approve → diproses     |
| diproses                | View                 | View                       | View                 | View                         | View                | View                   |
| diterima                | View                 | View                       | View                 | View                         | View                | View                   |

### 9. Pembelian (Purchase Order)

**CRUD hanya untuk Staf/Manajer Keuangan**

- Index/Create/Show/Edit/Update/Destroy: ✓ R01, R06, R10
- Syarat: Hanya dari pengadaan dengan status `disetujui_keuangan`

### 10. Penerimaan Bahan Baku

**Create/Store hanya Gudang, Lihat untuk yang akses Gudang**

- Index/Show: ✓ R01, R02, R07
- Create/Store: ✓ R01, R02, R07

### 11. Transaksi Pembayaran

**CRUD hanya untuk Staf/Manajer Keuangan**

- Index/Create/Show/Edit/Update/Destroy: ✓ R01, R06, R10

### 12. Penugasan Produksi

**CRUD untuk RnD roles**

- Index/Show/Edit/Update: ✓ R03 (Staff RnD)
- Index/Create/Show/Edit/Update/Destroy: ✓ R01, R08 (Manajer RnD)

---

## File-File yang Dimodifikasi

### Backend - Middleware

1. **`app/Http/Middleware/CheckRoleBasedAccess.php`**
    - Updated role-based routes configuration
    - Added Users route block untuk non-Admin
    - Comprehensive route and action validation

### Backend - Traits

1. **`app/Http/Traits/RoleAccess.php`**
    - Added helper methods untuk semua role combinations
    - New methods: `isKeuanganRelated()`, `isManajerKeuangan()`, `isPengadaanRelated()`, `isManajerPengadaan()`, `isRnDRelated()`

2. **`app/Http/Traits/PengadaanAuthorization.php`** (NEW)
    - `canCreatePengadaan()` - Check create permission
    - `canDeletePengadaan()` - Check delete permission based on status
    - `canApprovePengadaan()` - Check approval permission per role
    - `canEditPengadaanDetail()` - Check detail edit permission
    - `isPengadaanDetailFilled()` - Validate detail completeness
    - `canViewPengadaan()` - Check view permission

### Backend - Controllers

1. **`app/Http/Controllers/UserController.php`**
    - Added `use RoleAccess` trait
    - Authorization checks di semua methods
    - Restrict ke R01 (Admin) saja

2. **`app/Http/Controllers/PengadaanController.php`**
    - Added `use RoleAccess, PengadaanAuthorization` traits
    - Authorization checks di create, store, edit, update, destroy
    - Complex status transition validation
    - Per-role approval logic

3. **`app/Http/Controllers/PembelianController.php`**
    - Enhanced authorization checks
    - `isKeuanganRelated()` checks di create, store, edit, update, destroy

4. **`app/Http/Controllers/TransaksiPembayaranController.php`**
    - Enhanced authorization checks
    - `isKeuanganRelated()` checks di create, store, edit, update, destroy

5. **`app/Http/Controllers/PenerimaanBahanBakuController.php`**
    - Added `use RoleAccess` trait
    - `isGudangRelated()` checks di create, store

---

## Requirement Fulfillment

### ✅ Requirement 1: Admin bisa semuanya dan CRUD pengguna hanya untuk Admin

- **Middleware**: Users route di-block untuk non-R01
- **Controller**: UserController memiliki authorization checks di semua methods

### ✅ Requirement 2: CRUD bahan baku dan produk ada untuk Staf & Manajer Gudang

- **Middleware**: R02, R07 punya akses create, store, edit, update, destroy
- **Routes**: Configured untuk gudang roles

### ✅ Requirement 3: CRUD Pelanggan dan pesanan hanya untuk Staf Penjualan

- **Middleware**: Only R05 (Staf Penjualan) punya full CRUD
- **Routes**: R01 (Admin) juga bisa untuk override

### ✅ Requirement 4: CRUD pemasok hanya untuk Staf & Manajer Pengadaan

- **Middleware**: R04, R09 punya akses full CRUD pemasok
- **Routes**: Configured untuk pengadaan roles

### ✅ Requirement 5: CRUD pengiriman hanya untuk Staf & Manajer Gudang

- **Middleware**: R02, R07 punya akses full CRUD pengiriman
- **Routes**: Configured untuk gudang roles

### ✅ Requirement 6: Staf gudang bisa menambahkan pengadaan dan menghapus yang statusnya pending

- **Middleware**: R02 punya create, destroy actions
- **Controller**: `PengadaanController::destroy()` validates status sebelum delete
- **Trait**: `canDeletePengadaan()` checks untuk pending statuses

### ✅ Requirement 7: Manajer gudang bisa CRUD yang statusnya pending dan menaikkan ke disetujui_gudang

- **Middleware**: R07 punya create, store, edit, update, destroy
- **Controller**: Status transition validation per role
- **Trait**: `canApprovePengadaan()` untuk R07 dari pending → disetujui_gudang

### ✅ Requirement 8: Staf & Manajer Pengadaan bisa mengisi detail pemasok/harga untuk status disetujui_gudang

- **Middleware**: R04, R09 dapat edit pengadaan dengan status disetujui_gudang
- **Controller**: Conditional update logic based on status
- **Trait**: `canEditPengadaanDetail()` validates status

### ✅ Requirement 9: Manajer Pengadaan dapat menaikkan status dari disetujui_gudang ke disetujui_pengadaan

- **Controller**: Role-based approval logic untuk R09
- **Trait**: `canApprovePengadaan()` untuk R09 dengan status disetujui_gudang
- **Validation**: Memastikan detail sudah lengkap sebelum approve

### ✅ Requirement 10: Manajer Keuangan dapat menaikkan status dari disetujui_pengadaan ke disetujui_keuangan

- **Controller**: Role-based approval logic untuk R10
- **Trait**: `canApprovePengadaan()` untuk R10 dengan status disetujui_pengadaan

### ✅ Requirement 11: Pembelian CRUD hanya untuk Staf & Manajer Keuangan

- **Middleware**: R06, R10 punya akses full CRUD pembelian
- **Controller**: `isKeuanganRelated()` checks di semua methods

### ✅ Requirement 12: Penerimaan bahan baku tambah dan lihat detail hanya untuk Staf Gudang

- **Middleware**: R02, R07 punya akses index, create, store, show
- **Controller**: `isGudangRelated()` checks di create, store

### ✅ Requirement 13: Transaksi Pembayaran CRUD hanya untuk Staf & Manajer Keuangan

- **Middleware**: R06, R10 punya akses full CRUD transaksi pembayaran
- **Controller**: `isKeuanganRelated()` checks di semua methods

---

## Contoh Authorization Checks

### Di Controller

```php
// Check single role
if (!$this->isAdmin()) {
    return redirect()->back()->with('error', 'Unauthorized');
}

// Check multiple roles
if (!$this->isKeuanganRelated()) {
    return redirect()->back()->with('error', 'Unauthorized');
}

// Check complex logic
if (!$this->canApprovePengadaan($pengadaan)) {
    return redirect()->back()->with('error', 'Cannot approve this procurement');
}
```

### Di Middleware

```php
// CheckRoleBasedAccess middleware akan otomatis:
// 1. Check user role
// 2. Check route access
// 3. Check action permission
// 4. Return 403 atau redirect jika unauthorized
```

---

## Testing Checklist

- [ ] Login sebagai Admin (R01) - dapat akses semua
- [ ] Login sebagai Staf Gudang (R02) - dapat buat pengadaan, tidak bisa akses Users
- [ ] Login sebagai Staf Pengadaan (R04) - dapat edit detail pengadaan, tidak bisa hapus
- [ ] Login sebagai Staf Penjualan (R05) - dapat CRUD pelanggan dan pesanan
- [ ] Login sebagai Staf Keuangan (R06) - dapat CRUD pembelian dan transaksi
- [ ] Login sebagai Manajer Gudang (R07) - dapat approve pengadaan ke disetujui_gudang
- [ ] Login sebagai Manajer Pengadaan (R09) - dapat approve ke disetujui_pengadaan
- [ ] Login sebagai Manajer Keuangan (R10) - dapat approve ke disetujui_keuangan
- [ ] Test status transition validation - hanya role yang tepat bisa approve
- [ ] Test detail completeness - pengadaan harus lengkap sebelum approve

---

## Notes

- Semua authorization checks sudah di-implement di middleware layer (global)
- Additional checks di controller layer untuk business logic yang lebih kompleks
- Trait `PengadaanAuthorization` menyediakan reusable methods untuk pengadaan logic
- Trait `RoleAccess` menyediakan helper methods untuk semua role checking
