# Perbaikan Transaksi Pembayaran - Final

## Masalah yang Diperbaiki

1. **Error 302 saat simpan** - Authorization checks menggunakan redirect implicit
2. **Dropdown tidak filter yang belum lunas** - Semua pembelian ditampilkan
3. **Label DP tidak tampil** - Hanya muncul jika jumlah_dp > 0
4. **Bukti pembayaran tidak jelas WAJIB** - Label dan pesan kurang jelas
5. **Metode pembayaran tidak lengkap** - Tidak ada Cek dan Giro

## File yang Dimodifikasi

### 1. `app/Http/Controllers/TransaksiPembayaranController.php`

#### a. Method `create()` - Filter dropdown hanya belum lunas

**Sebelum:**

```php
// Ambil pembelian yang sudah dikonfirmasi (bisa dibayar)
$pembelians = Pembelian::with('pemasok:pemasok_id,nama_pemasok')
    ->whereIn('status', ['confirmed', 'partially_received', 'fully_received'])
    ->select(...)
    ->orderBy('tanggal_pembelian', 'desc')
    ->get()
```

**Sesudah:**

```php
// Ambil pembelian yang sudah dikonfirmasi dan BELUM LUNAS
$pembelians = Pembelian::with('pemasok:pemasok_id,nama_pemasok')
    ->whereIn('status', ['confirmed', 'partially_received', 'fully_received'])
    ->where(function ($query) {
        // Filter: sisa pembayaran > 0 (belum lunas)
        $query->whereRaw('CAST(COALESCE(sisa_pembayaran, 0) AS DECIMAL(15,2)) > 0');
    })
    ->select(...)
    ->orderBy('tanggal_pembelian', 'desc')
    ->get()
```

**Impact**: Dropdown hanya menampilkan pembelian yang belum lunas (sisa_pembayaran > 0)

#### b. Method `store()` - Perbaikan authorization dan validasi

**Sebelum:**

```php
'metode_pembayaran'  => 'required|in:tunai,transfer',
'bukti_pembayaran'   => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
// ... message
'bukti_pembayaran.required' => 'Bukti pembayaran wajib diunggah',
```

**Sesudah:**

```php
// Authorization: Admin (R01), Staf Keuangan (R06) dan Manajer Keuangan (R10) yang bisa store
if (!$this->isAdmin() && !$this->isKeuanganRelated()) {
    abort(403, 'Anda tidak memiliki izin untuk membuat transaksi pembayaran baru.');
}

// ...
'metode_pembayaran'  => 'required|in:tunai,transfer,cek,giro',
'bukti_pembayaran'   => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
// ... message
'bukti_pembayaran.required' => 'Bukti pembayaran WAJIB diunggah',
```

**Impact**:

- Fix error 302 dengan abort(403)
- Tambah metode pembayaran Cek dan Giro
- Pesan error lebih jelas (WAJIB)

### 2. `resources/js/pages/transaksi-pembayaran/create.tsx`

#### a. Tampilkan label DP selalu (tidak hanya ketika > 0)

**Sebelum:**

```tsx
{
    selectedPembelian.jumlah_dp && selectedPembelian.jumlah_dp > 0 && (
        <div>
            <span className="text-gray-600">DP:</span>
            <p className="font-semibold text-purple-600">{formatCurrency(selectedPembelian.jumlah_dp)}</p>
        </div>
    );
}
```

**Sesudah:**

```tsx
<div>
    <span className="text-gray-600">DP:</span>
    <p className="font-semibold text-purple-600">
        {selectedPembelian.jumlah_dp && selectedPembelian.jumlah_dp > 0 ? formatCurrency(selectedPembelian.jumlah_dp) : '-'}
    </p>
</div>
```

**Impact**: Label "DP:" selalu tampil, dengan value "-" jika tidak ada DP

#### b. Perbaiki label dan pesan bukti pembayaran

**Sebelum:**

```tsx
<FormField id="bukti_pembayaran" label="Bukti Pembayaran" error={errors.bukti_pembayaran}>
    <Input ... />
    <p className="mt-1 text-sm text-gray-500">Upload bukti pembayaran ... - Opsional</p>
</FormField>
```

**Sesudah:**

```tsx
<FormField id="bukti_pembayaran" label="Bukti Pembayaran" error={errors.bukti_pembayaran} required>
    <Input ... />
    <p className="mt-1 text-sm text-gray-500">Upload bukti pembayaran ... - WAJIB</p>
</FormField>
```

**Impact**:

- Label form menjadi required (visual indicator)
- Pesan berubah dari "Opsional" menjadi "WAJIB"

#### c. Auto-fill jumlah pembayaran (sudah ada, dipastikan bekerja)

```tsx
React.useEffect(() => {
    if (selectedPembelian) {
        // Auto-suggest amount based on jenis_pembayaran
        if (data.jenis_pembayaran === 'dp' && selectedPembelian.jumlah_dp) {
            setData('jumlah_pembayaran', selectedPembelian.jumlah_dp.toString());
        } else if (data.jenis_pembayaran === 'pelunasan') {
            setData('jumlah_pembayaran', selectedPembelian.sisa_pembayaran.toString());
        } else {
            setData('jumlah_pembayaran', '');
        }
    }
}, [selectedPembelian, data.jenis_pembayaran]);
```

**Impact**:

- Ketika jenis_pembayaran = 'pelunasan', jumlah_pembayaran auto-fill dengan sisa_pembayaran
- Ketika jenis_pembayaran = 'dp', jumlah_pembayaran auto-fill dengan jumlah_dp

## Hasil yang Diharapkan

### Fungsionalitas

- ✅ Admin bisa simpan tanpa error 302 (auth fix dengan abort(403))
- ✅ Dropdown hanya tampil PO yang belum lunas
- ✅ Label DP selalu tampil (tidak hilang ketika tidak ada DP)
- ✅ Pesan bukti pembayaran jelas "WAJIB"
- ✅ Jumlah pembayaran auto-fill saat jenis = pelunasan
- ✅ Metode pembayaran lengkap (Transfer, Tunai, Cek, Giro)

### UI/UX

- Label "DP:" tetap muncul di detail PO (bukan disappear seperti bug sebelumnya)
- Bukti pembayaran dengan label required dan pesan WAJIB
- Dropdown yang lebih clean (hanya belum lunas)

## Testing Checklist

- [ ] Dropdown hanya tampil PO yang belum lunas (sisa > 0)
- [ ] Label DP selalu muncul di detail PO
- [ ] Jenis pembayaran = Pelunasan, jumlah auto-fill dengan sisa
- [ ] Jenis pembayaran = DP, jumlah auto-fill dengan DP
- [ ] Bukti pembayaran form menampilkan required dan "WAJIB"
- [ ] Submit form tanpa error 302 (auth fix)
- [ ] Metode pembayaran ada Cek dan Giro
- [ ] Error message untuk bukti pembayaran muncul dengan benar

## Summary

Total **1 controller + 1 view** yang dimodifikasi dengan fokus pada:

- Perbaikan authorization (error 302)
- Filtering dropdown yang lebih smart (hanya belum lunas)
- UX improvement (DP label selalu tampil, bukti jelas WAJIB)
- Auto-fill untuk pelunasan
- Metode pembayaran lengkap
