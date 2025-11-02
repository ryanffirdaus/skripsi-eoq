# Restructuring Pengadaan Workflow - Detailed Plan

## 🎯 Tujuan

1. **Restrict Pemasok Input**: Hanya Staf/Manajer Pengadaan (R04, R09) yang bisa input pemasok
2. **Route by Item Type**:
    - **Bahan Baku**: Gudang → Pengadaan → Keuangan (untuk PO dan pembayaran)
    - **Produk**: Gudang → RnD (untuk penugasan produksi)

## 📋 Current Workflow Analysis

```
draft
  ↓ (Gudang approve)
pending_approval_gudang
  ↓ (Gudang pass)
pending_supplier_allocation (STAF/MANAJER PENGADAAN INPUT PEMASOK)
  ↓ (Pengadaan approve)
pending_approval_pengadaan
  ↓ (Keuangan approve)
pending_approval_keuangan
  ↓ (Processed)
processed
  ↓ (PO selesai, barang diterima)
received
```

## 🔄 Proposed New Workflow

### **BAHAN BAKU Flow**:

```
draft (Gudang create)
  ↓ Manajer Gudang approve
pending_approval_gudang
  ↓ Staf/Manajer Pengadaan input pemasok + harga
pending_supplier_allocation
  ↓ Manajer Pengadaan approve
pending_approval_pengadaan
  ↓ Manajer Keuangan approve
pending_approval_keuangan
  ↓ PO created + processed
processed (STOP di Pengadaan/Keuangan)
```

### **PRODUK Flow**:

```
draft (Gudang create)
  ↓ Manajer Gudang approve
pending_approval_gudang
  ↓ Manajer Gudang set status → untuk_rnd
untuk_rnd (STOP di Gudang, diteruskan ke RnD)
  ↓ Manajer RnD create Penugasan Produksi
penugasan_created
  ↓ Staf RnD execute penugasan
penugasan_selesai (produk siap)
  ↓
received
```

## 🛠️ Implementation Strategy

### File yang perlu diubah:

1. **Model Pengadaan**
    - Tambah status baru: `untuk_rnd`, `penugasan_created`, `penugasan_selesai`
    - Tambah method untuk check jenis_barang dari detail

2. **PengadaanController**
    - Add pemasok input authorization checks
    - Filter visibility by jenis_barang untuk index
    - Update status transitions berdasarkan jenis_barang
    - Tambah logic untuk routing ke RnD

3. **PengadaanPolicy** (create jika belum ada)
    - canEditSupplier() - hanya R04, R09
    - canEditPrice() - hanya R04, R09, R07
    - canApprove() - berbeda untuk setiap status

4. **Middleware CheckRoleBasedAccess**
    - Update untuk route visibility berdasarkan jenis_barang + status

5. **View (create.tsx / edit.tsx)**
    - Tampilkan pemasok field HANYA untuk R04, R09
    - Disable pemasok field saat status pending
    - Conditional rendering berdasarkan jenis_barang

6. **PenugasanProduksiController** (update)
    - Logic untuk create penugasan dari pengadaan produk
    - Update pengadaan status saat penugasan dibuat

## 📊 Status Matrix

| Status                      | Gudang      | Pengadaan       | Keuangan  | RnD         | Visibility                    |
| --------------------------- | ----------- | --------------- | --------- | ----------- | ----------------------------- |
| draft                       | ✓ view/edit | -               | -         | -           | Gudang                        |
| pending_approval_gudang     | ✓ approve   | -               | -         | -           | Gudang                        |
| pending_supplier_allocation | ✓ view      | ✓ edit supplier | -         | -           | Gudang + Pengadaan            |
| pending_approval_pengadaan  | ✓ view      | ✓ approve       | -         | -           | Gudang + Pengadaan            |
| pending_approval_keuangan   | ✓ view      | ✓ view          | ✓ approve | -           | Gudang + Pengadaan + Keuangan |
| processed                   | ✓ view      | ✓ view          | ✓ view    | -           | Gudang + Pengadaan + Keuangan |
| untuk_rnd                   | ✓ view      | -               | -         | ✓ view      | Gudang + RnD                  |
| penugasan_created           | ✓ view      | -               | -         | ✓ view/edit | Gudang + RnD                  |
| penugasan_selesai           | ✓ view      | -               | -         | ✓ view      | Gudang + RnD                  |
| received                    | ✓ view      | -               | -         | -           | All                           |

## 🔐 Authorization Rules

### Pemasok Input:

- HANYA Staf Pengadaan (R04) atau Manajer Pengadaan (R09)
- HANYA saat status = pending_supplier_allocation
- HANYA untuk pengadaan jenis_barang = bahan_baku

### Status Transitions:

- Bahan Baku: pending_approval_keuangan → processed (stop)
- Produk: pending_approval_gudang → untuk_rnd (redirect to RnD)

## 📈 Benefits

✅ Clear separation of concerns
✅ Gudang fokus on pembelian
✅ Pengadaan fokus on supplier allocation
✅ Keuangan fokus on approvals
✅ RnD fokus on production assignments
✅ Better audit trail
