# Summary: Perbaikan Status Pengadaan di Seluruh Codebase

## Status Correct Values (dari Migration)

```
draft
pending_approval_gudang
pending_supplier_allocation
pending_approval_pengadaan
pending_approval_keuangan
processed
received
cancelled
```

## Perubahan yang Telah Dilakukan

### 1. ✅ Backend Model (`app/Models/Pengadaan.php`)

- **canBeEdited()**: Updated untuk accept `draft`, `pending_approval_gudang`, `pending_supplier_allocation`
- **canBeCancelled()**: Fixed dari `['diterima', 'dibatalkan']` menjadi `['received', 'cancelled']`
- Status constants: SUDAH BENAR
- Status methods: SUDAH BENAR
- isValidStatusTransition(): SUDAH BENAR

### 2. ✅ Backend Authorization Trait (`app/Http/Traits/PengadaanAuthorization.php`)

- **canEditPengadaanDetail()**: Updated untuk check `pending_supplier_allocation` (bukan `disetujui_gudang`)
- **canApprovePengadaan()**:
    - R07 (Manajer Gudang): approve `pending_approval_gudang` → `pending_supplier_allocation`
    - R09 (Manajer Pengadaan): approve `pending_supplier_allocation` → `pending_approval_pengadaan`
    - R10 (Manajer Keuangan): approve `pending_approval_pengadaan` → `pending_approval_keuangan`

### 3. ✅ Backend Controller (`app/Http/Controllers/PengadaanController.php`)

- **Validator Rules**: Updated dari old status names ke new enum values
- **statusOptions**: Updated untuk edit page dengan correct labels dan values
- **updateStatus()**: Updated validator untuk accept all correct status values
- **Status Checks dalam update()**: Updated semua pemasok allocation checks ke `pending_supplier_allocation`
- **getStatusLabel()**: SUDAH BENAR dengan new status values

### 4. ✅ Frontend - Edit Page (`resources/js/pages/pengadaan/edit.tsx`)

- **canEditSupplier()**: Updated untuk check `pending_supplier_allocation`
- **canEditPrice()**: Updated untuk check `draft` atau `pending_supplier_allocation`
- **Info Message**: Updated untuk reflect new workflow

### 5. ✅ Frontend - Show Page (`resources/js/pages/pengadaan/show.tsx`)

- **getStatusColor()**: Updated untuk map semua new status values ke colors yang appropriate

### 6. ✅ Frontend - Index Page (`resources/js/pages/pengadaan/index.tsx`)

- **Status Config**: SUDAH CORRECT dengan new status values
- **Filter Options**: SUDAH CORRECT

## Status Workflow Flow

```
draft
  ↓ (approved by Manajer Gudang / R07)
pending_approval_gudang
  ↓ (approved by Manajer Gudang / R07)
pending_supplier_allocation  ← STAF/MANAJER PENGADAAN (R04/R09) DAPAT EDIT DI SINI
  ↓ (approved by Manajer Pengadaan / R09)
pending_approval_pengadaan
  ↓ (approved by Manajer Keuangan / R10)
pending_approval_keuangan
  ↓ (processed by Manajer Gudang / R07)
processed
  ↓ (received)
received
```

## Access Control untuk Staf Pengadaan (R04/R09)

Staf Pengadaan dapat:

- ✅ Edit pengadaan saat status: `pending_supplier_allocation`
- ✅ Mengalokasikan pemasok (hanya untuk item bahan_baku)
- ✅ Mengubah harga satuan
- ✅ Melihat tombol "Edit Pengadaan" di halaman detail

## Verifikasi

Semua status lama berikut sudah diperbaiki di seluruh codebase:

- ❌ `disetujui_gudang` → ✅ `pending_supplier_allocation`
- ❌ `disetujui_pengadaan` → ✅ `pending_approval_pengadaan`
- ❌ `disetujui_keuangan` → ✅ `pending_approval_keuangan`
- ❌ `diproses` → ✅ `processed`
- ❌ `diterima` → ✅ `received`
- ❌ `dibatalkan` → ✅ `cancelled`

Field names (`qty_disetujui`, `qty_diterima`) tetap unchanged karena itu adalah field database, bukan status.
