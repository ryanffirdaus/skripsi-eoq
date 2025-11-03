# Fix: Access Control Authorization untuk Edit Pengadaan

## Masalah

Staf Pengadaan (R04/R09) ketika click "Edit Pengadaan" langsung mendapat 302 redirect ke dashboard.

## Root Cause

Ada logic error di PengadaanController.edit() method yang menggunakan `&&` (AND) seharusnya menggunakan lebih fleksibel logic.

Juga `can_edit` di show page masih menggunakan `canBeEdited()` yang limited.

## Solusi

### 1. ✅ Tambah method baru di Trait (`app/Http/Traits/PengadaanAuthorization.php`)

**Method baru: `canEditPengadaan()`**

```php
public function canEditPengadaan(Pengadaan $pengadaan): bool
```

Ini method comprehensive yang handle semua role yang bisa edit:

- **R01 (Admin)**: Dapat edit semua status
- **R02/R07 (Staf/Manajer Gudang)**: Edit di `draft` dan `pending_approval_gudang`
- **R04/R09 (Staf/Manajer Pengadaan)**: Edit detail di `pending_supplier_allocation`

### 2. ✅ Simplify logic di `PengadaanController.edit()`

**Sebelum:**

```php
if (!$this->isAdmin()) {
    if (!$this->canEditPengadaanDetail($pengadaan) && !$this->canApprovePengadaan($pengadaan)) {
        // reject
    }
    if (!$pengadaan->canBeEdited()) {
        // reject
    }
}
```

**Sesudah:**

```php
if (!$this->isAdmin() && !$this->canEditPengadaan($pengadaan)) {
    // reject
}
```

### 3. ✅ Update `can_edit` flag di show page

**Sebelum:**

```php
'can_edit' => $pengadaan->canBeEdited(),
```

**Sesudah:**

```php
'can_edit' => $this->isAdmin() ? true : $this->canEditPengadaan($pengadaan),
```

## Access Control Matrix Sekarang

| Role                | Status                      | Can Edit | Notes                   |
| ------------------- | --------------------------- | -------- | ----------------------- |
| R01 (Admin)         | ANY                         | ✅       | Dapat edit semua status |
| R02/R07 (Gudang)    | draft                       | ✅       | Staf/Manajer Gudang     |
| R02/R07 (Gudang)    | pending_approval_gudang     | ✅       |                         |
| R04/R09 (Pengadaan) | pending_supplier_allocation | ✅       | Edit pemasok/harga      |
| R06/R10 (Keuangan)  | ANY                         | ❌       | View only               |
| Others              | ANY                         | ❌       | No permission           |

## Workflow Untuk Staf Pengadaan (R04)

1. **Manajer Gudang (R07)** approve di status `draft` → `pending_approval_gudang`
2. **Manajer Gudang (R07)** approve di status `pending_approval_gudang` → `pending_supplier_allocation`
3. **STAF PENGADAAN (R04)** NOW CAN EDIT! ✅
    - Click "Edit Pengadaan" di show page
    - Allocate pemasok untuk bahan_baku items
    - Update harga satuan
    - Save changes
4. **Manajer Pengadaan (R09)** approve → `pending_approval_pengadaan`
5. ... workflow continues

## Test Cases

### Test 1: Staf Pengadaan (R04) Akses di Status `pending_supplier_allocation`

```
1. Buat pengadaan (as Gudang)
2. Approve to pending_approval_gudang (as Manajer Gudang)
3. Approve to pending_supplier_allocation (as Manajer Gudang)
4. Login as Staf Pengadaan (R04)
5. Go to /pengadaan/{id}
6. ✅ Should see "Edit Pengadaan" button
7. Click button
8. ✅ Should NOT redirect to dashboard
9. Should show edit form with pemasok fields
```

### Test 2: Staf Pengadaan (R04) Akses di Status Lain

```
1. Go to pengadaan dengan status draft
2. Login as R04
3. Click "Edit Pengadaan"
4. ❌ Should redirect with message: "Anda tidak memiliki izin..."
```

### Test 3: Manajer Gudang (R07) Akses di Status Correct

```
1. Go to pengadaan dengan status draft
2. Login as R07
3. Click "Edit Pengadaan"
4. ✅ Should show edit form
5. Can change status
```

## Files Changed

- ✅ `app/Http/Traits/PengadaanAuthorization.php` - Add `canEditPengadaan()` method
- ✅ `app/Http/Controllers/PengadaanController.php`:
    - Simplify edit() authorization logic
    - Update can_edit flag di show page
