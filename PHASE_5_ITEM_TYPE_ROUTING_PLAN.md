# Phase 5: Item Type Routing Implementation Plan

## Overview

Implementasi routing berbasis jenis_barang (bahan_baku vs produk) sehingga:

- **Bahan_baku** → Route through Pengadaan → Keuangan (financial approval)
- **Produk** → Route to RnD (production assignment / penugasan_produksi)

## Current Status Enum (dari migration)

```
pending
disetujui_gudang
disetujui_pengadaan (dituju untuk bahan_baku)
disetujui_keuangan (dituju untuk bahan_baku)
diproses
diterima
dibatalkan
```

## Architecture Decision

### Option 1: Different Status for Different Item Types (RECOMMENDED)

```
Bahan_baku flow:
pending → disetujui_gudang → disetujui_pengadaan → disetujui_keuangan → diproses → diterima

Produk flow:
pending → disetujui_gudang → [skip pengadaan/keuangan approval] → diproses → diterima → [create penugasan_produksi]
```

### Option 2: Single Status with Routing Flag

```
Tambah column: route_to (enum: 'pengadaan_keuangan', 'rnd')
Tapi lebih kompleks, recommend Option 1
```

## Implementation Steps

### 1. Update PengadaanController.updateStatus() Method

**File**: `app/Http/Controllers/PengadaanController.php`

Sebelum:

```php
public function updateStatus(Request $request, Pengadaan $pengadaan)
{
    // Validasi dan update status
}
```

Sesudah:

```php
public function updateStatus(Request $request, Pengadaan $pengadaan)
{
    $user = Auth::user();
    $newStatus = $request->validated()['status'];

    // Cek apakah pengadaan hanya berisi satu jenis item
    if ($pengadaan->isMixed()) {
        // Handle mixed case - maybe not allowed atau separate flow
        return redirect()->back()->with('flash', [
            'message' => 'Pengadaan dengan item campuran perlu approval khusus.',
            'type' => 'warning'
        ]);
    }

    // Route berbeda berdasarkan jenis_barang
    if ($pengadaan->hasBahanBakuOnly()) {
        return $this->routeBahanBakuFlow($pengadaan, $newStatus, $user);
    } elseif ($pengadaan->hasProdukOnly()) {
        return $this->routeProdukFlow($pengadaan, $newStatus, $user);
    }
}

/**
 * Handle routing untuk bahan_baku (through Pengadaan + Keuangan approval)
 */
private function routeBahanBakuFlow(Pengadaan $pengadaan, string $newStatus, $user)
{
    // Validasi status transition untuk bahan_baku
    $allowedStatusTransitions = [
        'pending' => ['disetujui_gudang'],
        'disetujui_gudang' => ['disetujui_pengadaan', 'dibatalkan'],
        'disetujui_pengadaan' => ['disetujui_keuangan', 'dibatalkan'],
        'disetujui_keuangan' => ['diproses', 'dibatalkan'],
        'diproses' => ['diterima', 'dibatalkan'],
    ];

    if (!isset($allowedStatusTransitions[$pengadaan->status]) ||
        !in_array($newStatus, $allowedStatusTransitions[$pengadaan->status])) {
        return redirect()->back()->with('flash', [
            'message' => "Transisi status dari {$pengadaan->status} ke {$newStatus} tidak diizinkan untuk bahan_baku.",
            'type' => 'error'
        ]);
    }

    // Check authorization berdasarkan status baru
    // - disetujui_pengadaan: hanya R04, R09
    // - disetujui_keuangan: hanya R06, R10
    // - diproses: hanya R02, R07 (gudang)

    if ($newStatus === 'disetujui_pengadaan' && !in_array($user->role_id, ['R04', 'R09'])) {
        return redirect()->back()->with('flash', [
            'message' => 'Hanya Staf/Manajer Pengadaan yang bisa approve disetujui_pengadaan.',
            'type' => 'error'
        ]);
    }

    if ($newStatus === 'disetujui_keuangan' && !in_array($user->role_id, ['R06', 'R10'])) {
        return redirect()->back()->with('flash', [
            'message' => 'Hanya Staf/Manajer Keuangan yang bisa approve disetujui_keuangan.',
            'type' => 'error'
        ]);
    }

    // Update status
    $pengadaan->update(['status' => $newStatus]);

    return redirect()->route('pengadaan.show', $pengadaan)->with('flash', [
        'message' => 'Status pengadaan berhasil diubah menjadi ' . $newStatus,
        'type' => 'success'
    ]);
}

/**
 * Handle routing untuk produk (to RnD for production assignment)
 */
private function routeProdukFlow(Pengadaan $pengadaan, string $newStatus, $user)
{
    // Validasi status transition untuk produk
    $allowedStatusTransitions = [
        'pending' => ['disetujui_gudang'],
        'disetujui_gudang' => ['diproses', 'dibatalkan'], // Skip pengadaan/keuangan approval
        'diproses' => ['diterima', 'dibatalkan'],
    ];

    if (!isset($allowedStatusTransitions[$pengadaan->status]) ||
        !in_array($newStatus, $allowedStatusTransitions[$pengadaan->status])) {
        return redirect()->back()->with('flash', [
            'message' => "Transisi status dari {$pengadaan->status} ke {$newStatus} tidak diizinkan untuk produk.",
            'type' => 'error'
        ]);
    }

    // Check authorization
    // - disetujui_gudang: R02, R07 (gudang)
    // - diproses: R08 (RnD/QC) untuk create penugasan_produksi

    if ($newStatus === 'diproses' && !in_array($user->role_id, ['R08'])) {
        return redirect()->back()->with('flash', [
            'message' => 'Hanya RnD yang bisa mengubah status ke diproses dan create penugasan produksi.',
            'type' => 'error'
        ]);
    }

    // Update status
    $pengadaan->update(['status' => $newStatus]);

    // Jika status diproses, auto-create penugasan_produksi
    if ($newStatus === 'diproses') {
        // TODO: Create penugasan_produksi dari pengadaan produk items
        // $this->createPenugasanFromPengadaan($pengadaan);
    }

    return redirect()->route('pengadaan.show', $pengadaan)->with('flash', [
        'message' => 'Status pengadaan berhasil diubah menjadi ' . $newStatus,
        'type' => 'success'
    ]);
}
```

### 2. Update Pengadaan Policy

**File**: `app/Policies/PengadaanPolicy.php`

Tambahkan:

```php
/**
 * Determine if user can approve bahan_baku flow at each stage
 */
public function canApproveBahanBakuStage(User $user, Pengadaan $pengadaan, string $status): bool
{
    // Check if is bahan_baku only
    if (!$pengadaan->hasBahanBakuOnly()) {
        return false;
    }

    return match($status) {
        'disetujui_pengadaan' => in_array($user->role_id, ['R04', 'R09']),
        'disetujui_keuangan' => in_array($user->role_id, ['R06', 'R10']),
        'diproses' => in_array($user->role_id, ['R02', 'R07']),
        default => false,
    };
}

/**
 * Determine if user can approve produk flow at each stage
 */
public function canApproveProdukStage(User $user, Pengadaan $pengadaan, string $status): bool
{
    // Check if is produk only
    if (!$pengadaan->hasProdukOnly()) {
        return false;
    }

    return match($status) {
        'disetujui_gudang' => in_array($user->role_id, ['R02', 'R07']),
        'diproses' => in_array($user->role_id, ['R08']),
        default => false,
    };
}
```

### 3. Update Edit View Status Options

**File**: `resources/js/pages/pengadaan/edit.tsx`

Conditional status options based on item type:

```tsx
const getAvailableStatusOptions = (): StatusOption[] => {
    const allOptions = statusOptions;

    // Filter based on item type
    if (pengadaan.detail.length === 0) {
        return allOptions;
    }

    const itemTypes = pengadaan.detail.map((d) => d.jenis_barang);
    const hasOnlyBahanBaku = itemTypes.every((t) => t === 'bahan_baku');
    const hasOnlyProduk = itemTypes.every((t) => t === 'produk');

    if (hasOnlyBahanBaku) {
        // For bahan_baku: show full approval chain
        return ['pending', 'disetujui_gudang', 'disetujui_pengadaan', 'disetujui_keuangan', 'diproses', 'diterima', 'dibatalkan']
            .map((status) => allOptions.find((o) => o.value === status))
            .filter(Boolean);
    } else if (hasOnlyProduk) {
        // For produk: skip pengadaan/keuangan approvals
        return ['pending', 'disetujui_gudang', 'diproses', 'diterima', 'dibatalkan']
            .map((status) => allOptions.find((o) => o.value === status))
            .filter(Boolean);
    }

    return allOptions;
};
```

Update Select:

```tsx
<Select value={data.status} onValueChange={(value) => setData('status', value)}>
    <SelectTrigger>
        <SelectValue placeholder="Pilih Status" />
    </SelectTrigger>
    <SelectContent>
        {getAvailableStatusOptions().map((option) => (
            <SelectItem key={option.value} value={option.value}>
                {option.label}
            </SelectItem>
        ))}
    </SelectContent>
</Select>
```

### 4. Create Penugasan Produksi (for produk items)

**File**: New method in `PengadaanController.php`

```php
/**
 * Create penugasan_produksi from produk pengadaan
 */
private function createPenugasanFromPengadaan(Pengadaan $pengadaan): void
{
    // Get all produk items from pengadaan
    $produkItems = $pengadaan->detail()
        ->where('jenis_barang', 'produk')
        ->get();

    foreach ($produkItems as $item) {
        // Create penugasan_produksi entry
        PenugasanProduksi::create([
            'pesanan_detail_id' => null, // Dari pengadaan, bukan pesanan
            'pengadaan_detail_id' => $item->pengadaan_detail_id,
            'produk_id' => $item->barang_id, // Assuming barang_id is produk_id
            'qty_diminta' => $item->qty_diminta,
            'qty_dihasilkan' => 0,
            'status' => 'pending', // Initial status
            'catatan' => $item->catatan,
            // ... other fields
        ]);
    }
}
```

## Status Flow Diagrams

### Bahan_baku Flow:

```
[pending]
   ↓ (Gudang approval)
[disetujui_gudang] ← Staf/Manajer Pengadaan allocates suppliers here
   ↓ (Pengadaan approval)
[disetujui_pengadaan]
   ↓ (Keuangan approval)
[disetujui_keuangan]
   ↓ (Gudang processing)
[diproses]
   ↓ (Gudang confirms receipt)
[diterima] ✓

   Alternative: [X] → [dibatalkan]
```

### Produk Flow:

```
[pending]
   ↓ (Gudang approval)
[disetujui_gudang]
   ↓ (SKIP Pengadaan/Keuangan approval) ← Direct to RnD
[diproses] ← RnD creates penugasan_produksi
   ↓ (RnD assigns production)
[diterima] ✓

   Alternative: [X] → [dibatalkan]
```

## Testing Checklist (Phase 5)

- [ ] Create pengadaan dengan hanya bahan_baku items
- [ ] Create pengadaan dengan hanya produk items
- [ ] Update status untuk bahan_baku: pending → disetujui_gudang → disetujui_pengadaan → disetujui_keuangan → diproses → diterima
- [ ] Update status untuk produk: pending → disetujui_gudang → diproses → diterima (skip middle approvals)
- [ ] Test authorization untuk setiap status transition
- [ ] Verify penugasan_produksi created otomatis saat produk diproses
- [ ] Test dengan mixed item types (should show warning/error)
- [ ] Test API authorization untuk each status transition

## Role-Based Status Approval Matrix (Phase 5)

| Status                           | R02 (Staf Gudang) | R04 (Staf Pengadaan) | R06 (Staf Keuangan) | R07 (Manajer Gudang) | R08 (RnD) | R09 (Manajer Pengadaan) | R10 (Manajer Keuangan) |
| -------------------------------- | :---------------: | :------------------: | :-----------------: | :------------------: | :-------: | :---------------------: | :--------------------: |
| disetujui_gudang                 |         ✓         |          -           |          -          |          ✓           |     -     |            -            |           -            |
| disetujui_pengadaan (bahan_baku) |         -         |          ✓           |          -          |          -           |     -     |            ✓            |           -            |
| disetujui_keuangan (bahan_baku)  |         -         |          -           |          ✓          |          -           |     -     |            -            |           ✓            |
| diproses (bahan_baku)            |         ✓         |          -           |          -          |          ✓           |     -     |            -            |           -            |
| diproses (produk)                |         -         |          -           |          -          |          -           |     ✓     |            -            |           -            |

---

Dokumentasi lengkap: `PERBAIKAN_PENGADAAN_AKSES_PEMASOK.md`
