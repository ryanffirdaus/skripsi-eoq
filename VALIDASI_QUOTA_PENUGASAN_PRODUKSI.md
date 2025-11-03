# Validasi Quota Penugasan Produksi

## Deskripsi

Implementasi validasi untuk memastikan total jumlah produksi yang ditugaskan kepada satu atau beberapa orang tidak melebihi jumlah produksi yang diminta/disetujui.

## Contoh Kasus

- Jika jumlah yang harus diproduksi: **40 unit**
- User A ditugaskan: **20 unit**
- User B mencoba ditugaskan: **30 unit** ❌ **DITOLAK**
- User B hanya bisa ditugaskan maksimal: **20 unit** ✅ **DITERIMA**

## Perubahan yang Dilakukan

### 1. Model PengadaanDetail (`app/Models/PengadaanDetail.php`)

#### Accessor Baru:

- **`total_ditugaskan`**: Menghitung total jumlah produksi yang sudah ditugaskan (tidak termasuk yang dibatalkan)
- **`sisa_quota_produksi`**: Menghitung sisa quota yang masih bisa ditugaskan

```php
public function getTotalDitugaskanAttribute()
{
    return $this->penugasan()
        ->where('status', '!=', 'dibatalkan')
        ->sum('jumlah_produksi');
}

public function getSisaQuotaProduksiAttribute()
{
    $maxQty = $this->qty_disetujui ?? $this->qty_diminta;
    $totalDitugaskan = $this->getTotalDitugaskanAttribute();
    return $maxQty - $totalDitugaskan;
}
```

### 2. Controller PenugasanProduksiController (`app/Http/Controllers/PenugasanProduksiController.php`)

#### Method `store()`:

- Menambahkan validasi untuk memeriksa sisa quota sebelum membuat penugasan baru
- Menampilkan pesan error yang informatif dengan breakdown:
    - Total yang harus diproduksi
    - Sudah ditugaskan berapa
    - Sisa yang tersedia

```php
// Validasi jumlah tidak melebihi sisa quota produksi
$maxQty = $pengadaanDetail->qty_disetujui ?? $pengadaanDetail->qty_diminta;
$sisaQty = $pengadaanDetail->sisa_quota_produksi;

if ($validated['jumlah_produksi'] > $sisaQty) {
    $totalDitugaskan = $pengadaanDetail->total_ditugaskan;
    return back()->withErrors([
        'jumlah_produksi' => "Jumlah produksi tidak boleh melebihi sisa kuota. Total yang harus diproduksi: $maxQty, Sudah ditugaskan: $totalDitugaskan, Sisa: $sisaQty"
    ])->withInput();
}
```

#### Method `update()`:

- Menambahkan validasi yang sama saat mengupdate penugasan
- Menghitung total ditugaskan **tanpa** menyertakan penugasan yang sedang diedit
- Memastikan perubahan jumlah produksi tidak melebihi sisa quota

```php
// Hitung total yang sudah ditugaskan (tidak termasuk yang dibatalkan dan penugasan saat ini)
$totalSudahDitugaskan = PenugasanProduksi::where('pengadaan_detail_id', $penugasan_produksi->pengadaan_detail_id)
    ->where('penugasan_id', '!=', $penugasan_produksi->penugasan_id)
    ->where('status', '!=', 'dibatalkan')
    ->sum('jumlah_produksi');

$sisaQty = $maxQty - $totalSudahDitugaskan;

if ($validated['jumlah_produksi'] > $sisaQty) {
    return back()->withErrors([
        'jumlah_produksi' => "Jumlah produksi tidak boleh melebihi sisa kuota. Total yang harus diproduksi: $maxQty, Sudah ditugaskan (lainnya): $totalSudahDitugaskan, Sisa yang tersedia: $sisaQty"
    ])->withInput();
}
```

#### Method `create()`:

- Menambahkan relasi `penugasan` untuk memuat data penugasan yang sudah ada
- Menyertakan accessor `sisa_quota_produksi` dan `total_ditugaskan` dalam response
- Frontend dapat menampilkan informasi sisa quota secara real-time

```php
$pengadaanDetails = PengadaanDetail::with(['pengadaan', 'produk', 'penugasan' => function($query) {
        $query->where('status', '!=', 'dibatalkan');
    }])
    ->where('jenis_barang', 'produk')
    ->whereHas('pengadaan', function ($query) {
        $query->whereIn('status', ['disetujui_keuangan', 'diproses']);
    })
    ->get();
```

## Logika Validasi

### Saat Create:

1. Load `PengadaanDetail` yang dipilih
2. Hitung total yang sudah ditugaskan (status != dibatalkan)
3. Hitung sisa = qty_disetujui - total_ditugaskan
4. Validasi: jumlah_produksi_baru <= sisa

### Saat Update:

1. Load `PengadaanDetail` dari penugasan yang diedit
2. Hitung total yang sudah ditugaskan **KECUALI** penugasan ini (status != dibatalkan)
3. Hitung sisa = qty_disetujui - total_ditugaskan_lainnya
4. Validasi: jumlah_produksi_baru <= sisa

### Status yang Dikecualikan:

- **Dibatalkan**: Penugasan dengan status "dibatalkan" tidak dihitung dalam total karena tidak akan dikerjakan

## Contoh Error Message

Ketika validasi gagal, user akan melihat pesan:

```
Jumlah produksi tidak boleh melebihi sisa kuota.
Total yang harus diproduksi: 40,
Sudah ditugaskan: 20,
Sisa: 20
```

## Testing

### Skenario Test:

1. ✅ Buat penugasan pertama dengan jumlah <= qty_disetujui
2. ✅ Buat penugasan kedua dengan total tidak melebihi quota
3. ❌ Buat penugasan dengan total melebihi quota
4. ✅ Update penugasan dengan jumlah yang valid
5. ❌ Update penugasan dengan jumlah yang melebihi sisa quota
6. ✅ Batalkan penugasan, sisa quota bertambah kembali

## Catatan

- Validasi dilakukan di server-side untuk keamanan
- Frontend dapat menampilkan sisa quota secara real-time menggunakan accessor yang tersedia
- Penugasan yang dibatalkan tidak dihitung dalam total
- Saat edit, penugasan yang sedang diedit tidak dihitung untuk menghindari double counting
