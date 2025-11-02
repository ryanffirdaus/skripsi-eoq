# Soft Delete dengan Cascade untuk Detail Models

## Ringkasan Implementasi

Semua model di aplikasi ini sekarang menggunakan **Soft Delete** dengan fitur **Cascade Soft Delete** untuk detail models. Ini memastikan data consistency dan audit trail yang sempurna.

## Fitur Utama

### 1. Soft Delete di Semua Models

Semua models memiliki trait `SoftDeletes`:

- ✅ **Master Models**: Pesanan, Pengadaan, Pembelian, Pengiriman, PenugasanProduksi, dll
- ✅ **Detail Models**: PesananDetail, PengadaanDetail, PembelianDetail, PenerimaanBahanBakuDetail
- ✅ **Header Models**: PenerimaanBahanBaku

### 2. Cascade Soft Delete

Ketika master record dihapus, semua detail records otomatis di-soft-delete:

```
Pesanan.delete()
  → Semua PesananDetail di-soft-delete

Pengadaan.delete()
  → Semua PengadaanDetail di-soft-delete

Pembelian.delete()
  → Semua PembelianDetail di-soft-delete

PenerimaanBahanBaku.delete()
  → Semua PenerimaanBahanBakuDetail di-soft-delete
```

### 3. withTrashed() untuk ID Generation

Semua models menggunakan `withTrashed()` saat generate ID berikutnya:

```php
static::creating(function ($model) {
    if (!$model->pesanan_id) {
        // Include soft-deleted records untuk ID sequence
        $latest = static::withTrashed()->orderBy('pesanan_id', 'desc')->first();
        $nextId = $latest ? (int) substr($latest->pesanan_id, 2) + 1 : 1;
        $model->pesanan_id = 'PS' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }
});
```

**Keuntungan:**

- Tidak ada ID gaps
- ID tetap sequential meski ada deleted records
- Konsisten dengan business rule

## Struktur Data

### Master Models dengan Cascade

| Model               | Detail Model              | Relationship |
| ------------------- | ------------------------- | ------------ |
| Pesanan             | PesananDetail             | hasMany      |
| Pengadaan           | PengadaanDetail           | hasMany      |
| Pembelian           | PembelianDetail           | hasMany      |
| PenerimaanBahanBaku | PenerimaanBahanBakuDetail | hasMany      |

### ID Generation Format

| Model                     | Format         | Example        |
| ------------------------- | -------------- | -------------- |
| Pesanan                   | PS###          | PS001, PS002   |
| PesananDetail             | PSND#######    | PSND0000001    |
| Pengadaan                 | PGD#######     | PGD0000001     |
| PengadaanDetail           | PGDD#######    | PGDD0000001    |
| Pembelian                 | PO-YYYYMM-#### | PO-202511-0001 |
| PembelianDetail           | PBLD#######    | PBLD0000001    |
| PenerimaanBahanBaku       | RBM#######     | RBM0000001     |
| PenerimaanBahanBakuDetail | RBMD#######    | RBMD0000001    |

## Boot Methods Implementation

### Master Model (Pesanan)

```php
protected static function boot()
{
    parent::boot();

    static::deleting(function ($model) {
        if (Auth::id()) {
            $model->deleted_by = Auth::id();
        }

        // Cascade soft delete
        $model->detail()->each(function ($detail) {
            $detail->delete();
        });
    });
}
```

### Detail Model (PesananDetail)

```php
protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (!$model->pesanan_detail_id) {
            $latest = static::withTrashed()->orderBy('pesanan_detail_id', 'desc')->first();
            $nextNumber = $latest ? (int)substr($latest->pesanan_detail_id, 4) + 1 : 1;
            $model->pesanan_detail_id = 'PSND' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
        }
    });
}
```

## Database Migrations

### Migration File

`database/migrations/2025_11_03_add_soft_delete_to_detail_tables.php`

Menambahkan kolom `deleted_at` ke:

- pesanan_detail
- pengadaan_detail
- pembelian_detail
- penerimaan_bahan_baku
- penerimaan_bahan_baku_detail

## Workflow Soft Delete

### Scenario 1: Delete Pesanan

```
1. User klik Delete di Pesanan
2. Controller memanggil: $pesanan->delete()
3. Pesanan boot() deleting trigger:
   - Set deleted_by = logged in user
   - Iterate semua PesananDetail
   - Call $detail->delete() untuk setiap detail
4. Setiap PesananDetail di-soft-delete
5. Pesanan di-soft-delete (set deleted_at timestamp)
```

### Scenario 2: Restore Pesanan

```
1. User klik Restore di Pesanan (jika diimplementasi)
2. Controller memanggil: $pesanan->restore()
3. Pesanan dan semua detail yang di-delete bareng akan di-restore
4. PENTING: Detail yang di-delete secara terpisah tidak akan di-restore
   (hanya detail yang di-delete bersama Pesanan)
```

## Auditing dan Tracking

### Deleted Records Query

```php
// Lihat semua records termasuk yang di-soft-delete
$allPesanan = Pesanan::withTrashed()->get();

// Hanya deleted records
$deletedPesanan = Pesanan::onlyTrashed()->get();

// Exclude deleted records (default)
$activePesanan = Pesanan::get();
```

### Audit Information

Setiap master model track:

- `created_by`: User yang membuat
- `updated_by`: User yang terakhir update
- `deleted_by`: User yang delete

Contoh query audit:

```php
// Siapa yang delete Pesanan ini?
$pesanan = Pesanan::withTrashed()->find('PS001');
$deletedByUser = $pesanan->deletedBy; // Relasi ke User

// Kapan dihapus?
$pesanan->deleted_at; // Timestamp
```

## Best Practices

### 1. Selalu Gunakan withTrashed() untuk ID Generation

```php
// ✅ BENAR
$latest = static::withTrashed()->orderBy('id', 'desc')->first();

// ❌ SALAH - akan skip deleted records
$latest = static::orderBy('id', 'desc')->first();
```

### 2. Cascade Delete Detail Saat Master Delete

```php
// ✅ BENAR
static::deleting(function ($model) {
    $model->detail()->each(function ($detail) {
        $detail->delete();
    });
});

// ❌ SALAH - detail tidak dihapus
// Tidak ada cascade logic
```

### 3. Query yang Sensitive terhadap Deleted Records

```php
// ✅ BENAR - hanya active records
$active = Pesanan::where('status', 'pending')->get();

// Jika perlu lihat deleted juga
$all = Pesanan::withTrashed()
    ->where('status', 'pending')
    ->get();
```

### 4. Relasi ke Deleted Records

```php
// ✅ BENAR - include deleted detail
$pesanan = Pesanan::with('detail')  // akan tetap include soft-deleted detail
    ->withTrashed()
    ->find('PS001');

// Jika ingin exclude
$pesanan = Pesanan::find('PS001'); // otomatis exclude deleted detail
```

## Controller Implementation

### Soft Delete

```php
public function destroy($pesanan)
{
    // Check authorization first
    $this->authorize('delete', $pesanan);

    // Delete (soft delete automatically)
    $pesanan->delete();

    return redirect()
        ->route('pesanan.index')
        ->with('message', 'Pesanan berhasil dihapus');
}
```

### Restore (Optional)

```php
public function restore($pesanan)
{
    // Check authorization
    $this->authorize('restore', $pesanan);

    // Restore
    $pesanan->restore();

    return redirect()
        ->route('pesanan.index')
        ->with('message', 'Pesanan berhasil dipulihkan');
}
```

## Testing

### Unit Test

```php
public function test_deleting_pesanan_soft_deletes_details()
{
    $pesanan = Pesanan::factory()->create();
    $detail = PesananDetail::factory()->create([
        'pesanan_id' => $pesanan->pesanan_id
    ]);

    // Delete pesanan
    $pesanan->delete();

    // Check pesanan soft-deleted
    $this->assertNotNull($pesanan->fresh()->deleted_at);

    // Check detail soft-deleted
    $this->assertNotNull($detail->fresh()->deleted_at);
}
```

### Feature Test

```php
public function test_user_can_delete_pesanan()
{
    $user = User::factory()->supervisor()->create();
    $pesanan = Pesanan::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('pesanan.destroy', $pesanan));

    $response->assertRedirect(route('pesanan.index'));
    $this->assertNotNull($pesanan->fresh()->deleted_at);
    $this->assertEquals(1, $pesanan->detail()->onlyTrashed()->count());
}
```

## Performance Considerations

### Indexes pada Soft Delete

Kolom `deleted_at` otomatis di-index saat menggunakan `$table->softDeletes()`.

Query yang optimal:

```php
// ✅ OPTIMAL - menggunakan index pada deleted_at
$active = Pesanan::where('status', 'pending')->get();

// ✅ OPTIMAL - filtered query
$active = Pesanan::where('pesanan_id', 'PS001')->get();

// ⚠️ BERAT - scans many records, gunakan dengan pagination
$trashed = Pesanan::onlyTrashed()->get();

// ✅ OPTIMAL
$trashed = Pesanan::onlyTrashed()->paginate(15);
```

### Query Tips

```php
// Gunakan select() untuk mengurangi columns
$pesanan = Pesanan::select('pesanan_id', 'pelanggan_id', 'total_harga')
    ->get();

// Eager load relations
$pesanan = Pesanan::with(['detail', 'pelanggan'])
    ->get();

// Gunakan chunks untuk large datasets
Pesanan::withTrashed()->chunk(1000, function ($pesanans) {
    // Process $pesanans
});
```

## Migration Status

✅ **Completed Tasks:**

1. Created migration file: `2025_11_03_add_soft_delete_to_detail_tables.php`
2. Added `SoftDeletes` trait to all detail models:
    - PesananDetail ✅
    - PengadaanDetail ✅
    - PembelianDetail ✅
    - PenerimaanBahanBaku ✅
    - PenerimaanBahanBakuDetail ✅
3. Updated boot() methods to cascade soft delete:
    - Pesanan ✅
    - Pengadaan ✅
    - Pembelian ✅
    - PenerimaanBahanBaku ✅
4. All models using `withTrashed()` for ID generation ✅

## Commands to Run

```bash
# Run the migration
php artisan migrate

# Verify soft delete works
php artisan tinker
> $p = Pesanan::first();
> $p->delete();
> $p->fresh()->deleted_at; // should show timestamp
> $p->detail()->count(); // should show 0 (soft-deleted excluded by default)
> $p->detail()->onlyTrashed()->count(); // should show deleted details
```

## Troubleshooting

### Q: Detail tidak auto-deleted saat master deleted?

**A:** Pastikan boot() method di master model memanggil `$model->detail()->each(function ($detail) { $detail->delete(); });`

### Q: ID generation melompat?

**A:** Pastikan using `withTrashed()` saat query latest record untuk ID generation.

### Q: Deleted records masih tampil di query?

**A:** Gunakan `->where('deleted_at', null)` atau Laravel default exclude. Gunakan `withTrashed()` jika perlu lihat deleted.

### Q: Bagaimana restore detail yang sudah dihapus?

**A:** Gunakan `->restore()` pada detail model. Perhatikan business logic saat restore.

## References

- [Laravel Soft Deletes Documentation](https://laravel.com/docs/eloquent#soft-deletes)
- [Laravel Eloquent Relationships](https://laravel.com/docs/eloquent-relationships)
- [Laravel Model Events](https://laravel.com/docs/eloquent#events)
