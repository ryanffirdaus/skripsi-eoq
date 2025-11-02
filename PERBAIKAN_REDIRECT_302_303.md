# Perbaikan Error 302 dan 303 Redirect

## Ringkasan Masalah

- **Error 302 (Found)**: Terjadi saat admin menekan tombol "Tambah" dan "Ubah" di halaman index pembelian, penerimaan bahan baku, dan transaksi pembayaran
- **Error 303 (See Other)**: Terjadi saat admin memilih "Ya" pada dialog hapus di semua modul
- **Root Cause**: Authorization checks di controller menggunakan `redirect()` dengan status implicit, bukan explicit `abort(403)`

## File yang Dimodifikasi

### 1. `app/Http/Controllers/PembelianController.php`

Perubahan pada 5 method:

#### a. Method `create()` - Line 107-116

**Sebelum:**

```php
if (!$this->isKeuanganRelated()) {
    return redirect()->route('pembelian.index')
        ->with('flash', [
            'message' => 'Anda tidak memiliki izin untuk membuat pembelian baru.',
            'type' => 'error'
        ]);
}
```

**Sesudah:**

```php
// Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa create
if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
    abort(403, 'Anda tidak memiliki izin untuk membuat pembelian baru.');
}
```

#### b. Method `store()` - Line 161-171

**Sebelum:**

```php
if (!$this->isKeuanganRelated()) {
    return redirect()->route('pembelian.index')
        ->with('flash', [
            'message' => 'Anda tidak memiliki izin untuk membuat pembelian baru.',
            'type' => 'error'
        ]);
}
```

**Sesudah:**

```php
// Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa store
if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
    abort(403, 'Anda tidak memiliki izin untuk membuat pembelian baru.');
}
```

#### c. Method `edit()` - Line 306-315

**Sebelum:**

```php
if (!$this->isKeuanganRelated()) {
    return redirect()->route('pembelian.index')
        ->with('flash', [
            'message' => 'Anda tidak memiliki izin untuk mengedit pembelian.',
            'type' => 'error'
        ]);
}
```

**Sesudah:**

```php
// Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa edit
if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
    abort(403, 'Anda tidak memiliki izin untuk mengedit pembelian.');
}
```

#### d. Method `update()` - Line 366-375

**Sebelum:**

```php
if (!$this->isKeuanganRelated()) {
    return redirect()->route('pembelian.index')
        ->with('flash', [
            'message' => 'Anda tidak memiliki izin untuk mengubah pembelian.',
            'type' => 'error'
        ]);
}
```

**Sesudah:**

```php
// Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa update
if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
    abort(403, 'Anda tidak memiliki izin untuk mengubah pembelian.');
}
```

#### e. Method `destroy()` - Line 447-455

**Sebelum:**

```php
if (!$this->isKeuanganRelated()) {
    return redirect()->route('pembelian.index')
        ->with('flash', [
            'message' => 'Anda tidak memiliki izin untuk menghapus pembelian.',
            'type' => 'error'
        ]);
}
```

**Sesudah:**

```php
// Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa destroy
if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
    abort(403, 'Anda tidak memiliki izin untuk menghapus pembelian.');
}
```

### 2. `app/Http/Controllers/PenerimaanBahanBakuController.php`

Perubahan pada 3 method:

#### a. Method `create()` - Line 65-70

**Sebelum:**

```php
if (!$this->isGudangRelated()) {
    return redirect()->route('penerimaan-bahan-baku.index')
        ->with('flash', [
            'message' => 'Anda tidak memiliki izin untuk membuat penerimaan bahan baku baru.',
            'type' => 'error'
        ]);
}
```

**Sesudah:**

```php
// Authorization: Admin (R01), Staf Gudang (R02) dan Manajer Gudang (R07) yang bisa create
if (!$this->isAdmin() && !$this->isGudangRelated()) {
    abort(403, 'Anda tidak memiliki izin untuk membuat penerimaan bahan baku baru.');
}
```

#### b. Method `store()` - Line 103-108

**Sebelum:**

```php
if (!$this->isGudangRelated()) {
    return redirect()->route('penerimaan-bahan-baku.index')
        ->with('flash', [
            'message' => 'Anda tidak memiliki izin untuk membuat penerimaan bahan baku baru.',
            'type' => 'error'
        ]);
}
```

**Sesudah:**

```php
// Authorization: Admin (R01), Staf Gudang (R02) dan Manajer Gudang (R07) yang bisa store
if (!$this->isAdmin() && !$this->isGudangRelated()) {
    abort(403, 'Anda tidak memiliki izin untuk membuat penerimaan bahan baku baru.');
}
```

#### c. Method `show()` - Line 162-191

Perbaikan data yang dikirim ke view agar tidak ada default values kosong:

**Sebelum:**

```php
return Inertia::render('penerimaan-bahan-baku/show', [
    'penerimaan' => [
        'penerimaan_id' => $penerimaanBahanBaku->penerimaan_id,
        'pembelian_detail_id' => $penerimaanBahanBaku->pembelian_detail_id,
        'qty_diterima' => $penerimaanBahanBaku->qty_diterima,
        'pembelian' => [
            'pembelian_id' => $penerimaanBahanBaku->pembelianDetail->pembelian->pembelian_id ?? 'N/A',
            'tanggal_pembelian' => $penerimaanBahanBaku->pembelianDetail->pembelian->tanggal_pembelian,
            'pemasok' => $penerimaanBahanBaku->pembelianDetail->pembelian->pemasok,
        ],
        'item' => [
            'nama_item' => $penerimaanBahanBaku->pembelianDetail->pengadaanDetail->nama_item ?? 'N/A',
            'satuan' => $penerimaanBahanBaku->pembelianDetail->pengadaanDetail->satuan ?? '-',
            'qty_dipesan' => $penerimaanBahanBaku->pembelianDetail->pengadaanDetail->qty_diminta ?? 0,
        ],
        'created_at' => $penerimaanBahanBaku->created_at?->format('Y-m-d H:i:s'),
    ]
]);
```

**Sesudah:**

```php
$pembelianDetail = $penerimaanBahanBaku->pembelianDetail;
$pembelian = $pembelianDetail->pembelian;
$pengadaanDetail = $pembelianDetail->pengadaanDetail;

return Inertia::render('penerimaan-bahan-baku/show', [
    'penerimaan' => [
        'penerimaan_id' => $penerimaanBahanBaku->penerimaan_id,
        'pembelian_detail_id' => $penerimaanBahanBaku->pembelian_detail_id,
        'qty_diterima' => $penerimaanBahanBaku->qty_diterima,
        'pembelian' => [
            'pembelian_id' => $pembelian?->pembelian_id ?? 'N/A',
            'tanggal_pembelian' => $pembelian ? \Carbon\Carbon::parse($pembelian->tanggal_pembelian)->format('Y-m-d') : '-',
            'pemasok_nama' => $pembelian?->pemasok?->nama_pemasok ?? '-',
        ],
        'item' => [
            'nama_item' => $pengadaanDetail?->nama_item ?? '-',
            'satuan' => $pengadaanDetail?->satuan ?? '-',
            'qty_dipesan' => $pengadaanDetail?->qty_diminta ?? 0,
            'harga_satuan' => $pengadaanDetail?->harga_satuan ?? 0,
            'total_harga' => $pengadaanDetail?->total_harga ?? 0,
        ],
        'created_at' => $penerimaanBahanBaku->created_at?->format('Y-m-d H:i:s'),
        'updated_at' => $penerimaanBahanBaku->updated_at?->format('Y-m-d H:i:s'),
    ]
]);
```

### 3. `resources/js/pages/penerimaan-bahan-baku/show.tsx`

Dibuat ulang dengan template yang lebih sederhana dan sesuai dengan data dari controller:

**Perubahan Utama:**

- Menghapus interface yang kompleks yang tidak sesuai dengan data controller
- Membuat structure data baru yang match dengan controller
- Menggunakan `ShowPageTemplate` dari komponen yang sudah ada
- Menambahkan card-based layout untuk setiap informasi
- Menampilkan data detail item (harga satuan, total harga)
- Menampilkan timestamp (created_at, updated_at)
- Menghilangkan section yang memerlukan data yang tidak ada (detail items table, catatan, dll)

## Penjelasan Teknis

### Mengapa `abort(403)` lebih baik dari `redirect()`?

1. **Status HTTP yang jelas**: `abort(403)` langsung mengembalikan HTTP 403 Forbidden, bukan 302 redirect
2. **Konsistensi**: Menggunakan middleware dan policy Laravel yang standard
3. **Error handling**: Exception akan ditangani oleh Laravel error handler dan menampilkan error page yang tepat
4. **Security**: Lebih jelas bahwa akses ditolak (forbidden), bukan redirect ke halaman lain

### Mengapa tambahkan `isAdmin()`?

Sesuai dengan file `CheckRoleBasedAccess.php`:

- Admin (R01) memiliki akses ke semua route (line dalam array dikosongkan)
- Controller harus menghormati access control middleware ini
- Jika hanya mengecek `isKeuanganRelated()`, Admin akan mendapat error 403 padahal seharusnya bisa akses

## Testing Checklist

- [ ] Admin bisa klik tombol "Tambah" di halaman index Pembelian (tidak 302)
- [ ] Admin bisa klik tombol "Ubah" di halaman index Pembelian (tidak 302)
- [ ] Admin bisa klik "Ya" pada dialog hapus Pembelian (tidak 303)
- [ ] Admin bisa klik tombol "Tambah" di halaman index Penerimaan Bahan Baku (tidak 302)
- [ ] Halaman detail Penerimaan menampilkan data dengan benar (bukan 0 dan -)
- [ ] Staf Gudang dan Manajer Gudang masih bisa akses Penerimaan Bahan Baku
- [ ] Staf Keuangan dan Manajer Keuangan masih bisa akses Pembelian

## Notes

- Semua perubahan menggunakan `abort(403)` untuk authorization failures
- Template view penerimaan disederhanakan agar match dengan data dari controller
- Role authorization sudah mencakup Admin, Staf, dan Manajer sesuai kebutuhan
