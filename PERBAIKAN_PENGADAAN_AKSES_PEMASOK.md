# Perbaikan Akses Input Pemasok dalam Pengadaan

## Overview

Implementasi pembatasan akses input pemasok (supplier) pada modul Pengadaan sehingga hanya Staf Pengadaan (R04) dan Manajer Pengadaan (R09) yang dapat mengalokasikan pemasok, dan hanya selama status pengadaan adalah "disetujui_gudang" untuk item bahan_baku.

## Perubahan yang Dilakukan

### 1. **app/Models/Pengadaan.php** (Helper Methods)

Menambahkan metode helper untuk memudahkan pengecekan jenis item:

```php
/**
 * Cek apakah pengadaan hanya berisi item bahan_baku
 */
public function hasBahanBakuOnly(): bool {
    $types = $this->detail->pluck('jenis_barang')->unique();
    return $types->count() === 1 && $types->first() === 'bahan_baku';
}

/**
 * Cek apakah pengadaan hanya berisi item produk
 */
public function hasProdukOnly(): bool {
    $types = $this->detail->pluck('jenis_barang')->unique();
    return $types->count() === 1 && $types->first() === 'produk';
}

/**
 * Cek apakah pengadaan berisi campuran item
 */
public function isMixed(): bool {
    $types = $this->detail->pluck('jenis_barang')->unique();
    return $types->count() > 1;
}

/**
 * Dapatkan array jenis_barang yang ada di pengadaan
 */
public function getItemTypes(): array {
    return $this->detail->pluck('jenis_barang')->unique()->toArray();
}
```

### 2. **app/Policies/PengadaanPolicy.php** (NEW - Authorization Policy)

File baru berisi semua authorization logic untuk modul Pengadaan:

```php
<?php

namespace App\Policies;

use App\Models\Pengadaan;
use App\Models\User;

class PengadaanPolicy
{
    /**
     * Determine if the user can edit the supplier allocation
     *
     * Only R04 (Staf Pengadaan) and R09 (Manajer Pengadaan)
     * during "disetujui_gudang" status for bahan_baku items
     */
    public function editSupplier(User $user, Pengadaan $pengadaan): bool
    {
        $isPengadaanStaff = in_array($user->role_id, ['R04', 'R09']);
        $isCorrectStatus = $pengadaan->status === 'disetujui_gudang';

        return $isPengadaanStaff && $isCorrectStatus;
    }

    /**
     * Determine if the user can edit prices
     *
     * R02 (Staf Gudang), R04 (Staf Pengadaan),
     * R07 (Manajer Gudang), R09 (Manajer Pengadaan)
     * during pending or disetujui_gudang status
     */
    public function editPrice(User $user, Pengadaan $pengadaan): bool
    {
        $isAuthorizedRole = in_array($user->role_id, ['R02', 'R04', 'R07', 'R09']);
        $isEditableStatus = in_array($pengadaan->status, ['pending', 'disetujui_gudang']);

        return $isAuthorizedRole && $isEditableStatus;
    }

    /**
     * Determine if user can route to RnD (produk items only)
     */
    public function canRouteToRnd(User $user, Pengadaan $pengadaan): bool
    {
        // Only RnD/QC roles can handle produk
        return in_array($user->role_id, ['R08']) && $pengadaan->hasProdukOnly();
    }

    /**
     * Determine if user can route to supplier allocation (bahan_baku only)
     */
    public function canRouteToSupplierAllocation(User $user, Pengadaan $pengadaan): bool
    {
        // Staf/Manajer Pengadaan can handle bahan_baku flow
        return in_array($user->role_id, ['R04', 'R09']) && $pengadaan->hasBahanBakuOnly();
    }

    /**
     * Get item types in pengadaan for routing logic
     */
    public function getItemTypes(User $user, Pengadaan $pengadaan): array
    {
        return $pengadaan->getItemTypes();
    }
}
```

### 3. **app/Http/Controllers/PengadaanController.php**

#### Perubahan di Method `update()` (lines 556-673)

Menambahkan validasi dan authorization untuk input pemasok_id:

```php
// Validasi dan authorization untuk pemasok_id
if (isset($detailData['pemasok_id'])) {
    if (!in_array($user->role_id, ['R04', 'R09'])) {
        return redirect()->back()->with('flash', [
            'message' => 'Hanya Staf/Manajer Pengadaan yang bisa mengalokasikan pemasok.',
            'type' => 'error'
        ]);
    }

    if ($pengadaan->status !== 'disetujui_gudang') {
        return redirect()->back()->with('flash', [
            'message' => 'Pemasok hanya bisa dialokasikan saat status "Disetujui Gudang".',
            'type' => 'error'
        ]);
    }

    // Validasi hanya untuk item bahan_baku
    $detail = $pengadaan->detail()->find($detailData['pengadaan_detail_id']);
    if ($detail->jenis_barang !== 'bahan_baku') {
        return redirect()->back()->with('flash', [
            'message' => 'Pemasok hanya bisa diinput untuk item bahan_baku, bukan produk internal.',
            'type' => 'error'
        ]);
    }
}
```

#### Perubahan di Method `edit()` (lines 488-553)

Menambahkan user/role data ke Props untuk frontend authorization:

```php
$user = Auth::user();

return Inertia::render('pengadaan/edit', [
    'pengadaan' => [...],
    'pemasoks' => $pemasok,
    'statusOptions' => $statusOptions,
    'auth' => [
        'user' => [
            'user_id' => $user->user_id,
            'nama_lengkap' => $user->nama_lengkap,
            'role_id' => $user->role_id,
        ]
    ]
]);
```

### 4. **resources/js/pages/pengadaan/edit.tsx**

#### Perubahan di Props Interface (line 44)

Menambahkan optional auth property:

```tsx
interface Props {
    pengadaan: Pengadaan;
    pemasoks: Pemasok[];
    statusOptions: StatusOption[];
    auth?: {
        user: {
            user_id: string;
            nama_lengkap: string;
            role_id: string;
        };
    };
}
```

#### Perubahan di Component Function (line 61)

Menambahkan helper functions untuk authorization check:

```tsx
export default function Edit({ pengadaan, pemasoks, statusOptions, auth }: Props) {
    // ... form setup ...

    // Authorization helper functions
    const canEditSupplier = (): boolean => {
        const userRole = auth?.user?.role_id;
        // Only R04 (Staf Pengadaan) and R09 (Manajer Pengadaan) can edit supplier
        const isPengadaanStaff = userRole === 'R04' || userRole === 'R09';
        // Only when status is "disetujui_gudang"
        const isCorrectStatus = pengadaan.status === 'disetujui_gudang';
        return isPengadaanStaff && isCorrectStatus;
    };

    const canEditPrice = (): boolean => {
        const userRole = auth?.user?.role_id;
        // Staf/Manajer Gudang (R02, R07), Staf/Manajer Pengadaan (R04, R09)
        const isAuthorizedRole = ['R02', 'R04', 'R07', 'R09'].includes(userRole || '');
        // Only when status is pending or disetujui_gudang
        const isEditableStatus = pengadaan.status === 'pending' || pengadaan.status === 'disetujui_gudang';
        return isAuthorizedRole && isEditableStatus;
    };

    const isPriceEditable = canEditPrice();
```

#### Perubahan di Pemasok Field Rendering (line 190-220)

Conditional rendering berdasarkan authorization:

```tsx
{item.jenis_barang === 'bahan_baku' ? (
    <>
        {canEditSupplier() ? (
            <>
                <Select
                    value={data.details[index].pemasok_id || ''}
                    onValueChange={(value) => handleDetailChange(index, 'pemasok_id', value)}
                >
                    <SelectTrigger className={cn('mt-1 bg-white', ...)}>
                        <SelectValue placeholder="Pilih Pemasok" />
                    </SelectTrigger>
                    <SelectContent>
                        {pemasoks.map((pemasok) => (
                            <SelectItem key={pemasok.pemasok_id} value={pemasok.pemasok_id}>
                                {pemasok.nama_pemasok}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {errors[`details.${index}.pemasok_id`] && (
                    <p className="mt-1 text-sm text-red-600">{...}</p>
                )}
            </>
        ) : (
            <div className={cn('mt-1 rounded border p-2 text-sm', ...)}>
                {data.details[index].pemasok_id
                    ? pemasoks.find(p => p.pemasok_id === data.details[index].pemasok_id)?.nama_pemasok
                    : '- (Menunggu alokasi)'}
            </div>
        )}
    </>
) : (
    <div className="mt-1 rounded border bg-gray-200 p-2 text-sm text-gray-600">
        - (Produk Internal)
    </div>
)}
```

**Penjelasan:**

- Jika user adalah Staf/Manajer Pengadaan dan status adalah "disetujui_gudang" → dropdown editable
- Jika sudah ada pemasok → tampilkan nama pemasok (read-only)
- Jika belum ada pemasok → tampilkan "- (Menunggu alokasi)" (read-only)
- Untuk produk → tampilkan "- (Produk Internal)" (tidak ada pemasok)

### 5. **app/Providers/AuthServiceProvider.php**

#### Menambahkan Import dan Registration

```php
use App\Models\Pengadaan;
use App\Policies\PengadaanPolicy;

protected $policies = [
    PenugasanProduksi::class => PenugasanProduksiPolicy::class,
    Pelanggan::class => PelangganPolicy::class,
    Pesanan::class => PesananPolicy::class,
    Pengadaan::class => PengadaanPolicy::class,  // <-- NEW
];
```

## Authorization Flow

### Backend (Laravel Controller)

1. User submit form edit pengadaan
2. `PengadaanController.update()` intercept request
3. Check: apakah ada `pemasok_id` di detail yang diupdate?
4. Jika ada:
    - ✓ Validasi: user role harus R04 atau R09
    - ✓ Validasi: status pengadaan harus "disetujui_gudang"
    - ✓ Validasi: item harus jenis_barang = 'bahan_baku'
    - ✓ Jika semua pass → update pemasok_id
    - ✗ Jika ada yang fail → return error message dengan flash

### Frontend (React Component)

1. Component loads dengan `auth` data (role_id)
2. Di render time, `canEditSupplier()` dievaluasi:
    - Check: user role adalah R04 atau R09?
    - Check: status pengadaan adalah 'disetujui_gudang'?
3. Conditional rendering:
    - ✓ Jika true → render select dropdown editable
    - ✗ Jika false → render read-only view dengan nilai saat ini

### Error Handling

```
Flash message ditampilkan jika:
- User tidak memiliki role R04/R09
  → "Hanya Staf/Manajer Pengadaan yang bisa mengalokasikan pemasok."

- Status bukan 'disetujui_gudang'
  → "Pemasok hanya bisa dialokasikan saat status 'Disetujui Gudang'."

- Item bukan bahan_baku (adalah produk internal)
  → "Pemasok hanya bisa diinput untuk item bahan_baku, bukan produk internal."
```

## Authorization Matrix

| Role                                         | R01 (Admin) | R02 (Staf Gudang) | R04 (Staf Pengadaan) | R06 (Staf Keuangan) | R07 (Manajer Gudang) | R09 (Manajer Pengadaan) | R10 (Manajer Keuangan) |
| -------------------------------------------- | :---------: | :---------------: | :------------------: | :-----------------: | :------------------: | :---------------------: | :--------------------: |
| Edit Pemasok (status=disetujui_gudang)       |      ✗      |         ✗         |          ✓           |          ✗          |          ✗           |            ✓            |           ✗            |
| Edit Harga (status=pending/disetujui_gudang) |      ✓      |         ✓         |          ✓           |          -          |          ✓           |            ✓            |           -            |

## Next Steps untuk Phase Selanjutnya

### 1. Implement Produk Routing Logic (TODO)

Update `PengadaanController.updateStatus()` untuk routing based on item type:

```php
// Ketika status -> disetujui_gudang
if ($pengadaan->hasProdukOnly()) {
    // Route ke RnD untuk penugasan produksi
} elseif ($pengadaan->hasBahanBakuOnly()) {
    // Route ke Pengadaan untuk alokasi pemasok
}
```

### 2. Implement Penugasan Produksi Integration (TODO)

Ketika produk pengadaan dikirim ke RnD, automatic create penugasan_produksi atau mark untuk assignment

### 3. Visibility Filter in Index (TODO)

Filter pengadaan di index berdasarkan jenis_barang untuk setiap divisi:

```
- Gudang: lihat semua pengadaan (bahan_baku & produk)
- Pengadaan: lihat hanya bahan_baku
- Keuangan: lihat hanya bahan_baku
- RnD: lihat hanya produk (untuk penugasan)
```

## Testing Checklist

- [ ] Test edit sebagai Admin (R01) - should see pemasok field read-only
- [ ] Test edit sebagai Staf Gudang (R02) - should see pemasok field read-only
- [ ] Test edit sebagai Staf Pengadaan (R04) dengan status disetujui_gudang - should see dropdown editable
- [ ] Test edit sebagai Staf Pengadaan (R04) dengan status pending - should see pemasok field read-only
- [ ] Test edit produk item sebagai Staf Pengadaan (R04) - should see "- (Produk Internal)"
- [ ] Test update pemasok_id via API dengan role tidak authorized - should return error 403
- [ ] Test update pemasok_id dengan status salah - should return error message
- [ ] Test update pemasok_id dengan item type salah - should return error message

## Kode Sumber yang Dimodifikasi

| File                                         | Lines              | Perubahan                                |
| -------------------------------------------- | ------------------ | ---------------------------------------- |
| app/Models/Pengadaan.php                     | +35                | Add 4 helper methods                     |
| app/Policies/PengadaanPolicy.php             | NEW                | Create new policy class                  |
| app/Http/Controllers/PengadaanController.php | 488-553, 640-673   | Add auth data, add pemasok validation    |
| resources/js/pages/pengadaan/edit.tsx        | 47, 61-82, 190-220 | Add Props, add helpers, update rendering |
| app/Providers/AuthServiceProvider.php        | 7, 25              | Register policy                          |

## Catatan Penting

1. **Status Enum**: Menggunakan status dari migration (pending, disetujui_gudang, disetujui_pengadaan, disetujui_keuangan, diproses, diterima, dibatalkan)

2. **Role IDs**: R04 = Staf Pengadaan, R09 = Manajer Pengadaan. Hanya roles ini yang bisa input pemasok.

3. **Item Type**: jenis_barang field di PengadaanDetail dapat 'bahan_baku' atau 'produk'. Pemasok hanya untuk bahan_baku.

4. **Frontend Authorization**: Authorization checks di frontend bersifat UX helper saja. Backend validation adalah yang sebenarnya mengatur keamanan.

5. **Policy Usage**: PengadaanPolicy dapat digunakan di controller untuk authorization gate jika diperlukan di masa depan.

## Deployment Notes

1. Migration sudah ada (tidak perlu perubahan database)
2. PengadaanPolicy.php adalah file baru - pastikan deployed
3. Frontend changes hanya file .tsx - rebuild frontend
4. No breaking changes ke existing API endpoints
