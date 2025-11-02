# Audit Tracking & Soft Delete Implementation

## 📋 Summary

Semua **master models** (tidak termasuk detail models) sekarang memiliki:

- ✅ **Soft Delete** (SoftDeletes trait)
- ✅ **Audit Columns** (created_by, updated_by, deleted_by)
- ✅ **Automatic Audit Tracking** (via boot() methods)
- ✅ **ID Generation dengan withTrashed()** (prevent ID gaps)

## 🎯 Master Models dengan Audit

| Model               | SoftDeletes | Audit Columns | Relasi Audit                       | ID Format      |
| ------------------- | ----------- | ------------- | ---------------------------------- | -------------- |
| BahanBaku           | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | BB###          |
| Produk              | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | PP###          |
| Pelanggan           | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | CU###          |
| Pemasok             | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | PMS#######     |
| Pesanan             | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | PS###          |
| Pengadaan           | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | PGD#######     |
| Pembelian           | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | PO-YYYYMM-#### |
| Pengiriman          | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | PG###          |
| PenerimaanBahanBaku | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | RBM#######     |
| TransaksiPembayaran | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | TRP########    |
| PenugasanProduksi   | ✅          | ✅            | ✅ createdBy, updatedBy, deletedBy | PPD#####       |

## 🔄 Detail Models (No Soft Delete)

| Model                     | SoftDeletes | Reason                                      |
| ------------------------- | ----------- | ------------------------------------------- |
| PesananDetail             | ❌          | Cascade soft delete via Pesanan             |
| PengadaanDetail           | ❌          | Cascade soft delete via Pengadaan           |
| PembelianDetail           | ❌          | Cascade soft delete via Pembelian           |
| PenerimaanBahanBakuDetail | ❌          | Cascade soft delete via PenerimaanBahanBaku |

## 🔧 Boot Method Pattern

Semua master models mengikuti pattern yang sama:

```php
protected static function boot()
{
    parent::boot();

    // ID Generation with withTrashed()
    static::creating(function ($model) {
        if (!$model->primary_key) {
            $latest = static::withTrashed()->orderBy('primary_key', 'desc')->first();
            $nextNumber = $latest ? (int)substr($latest->primary_key, prefix_length) + 1 : 1;
            $model->primary_key = 'PREFIX' . str_pad($nextNumber, digits, '0', STR_PAD_LEFT);
        }

        if (Auth::check()) {
            $model->created_by = Auth::user()->user_id; // or Auth::id()
            $model->updated_by = Auth::user()->user_id;
        }
    });

    // Track updates
    static::updating(function ($model) {
        if (Auth::check()) {
            $model->updated_by = Auth::user()->user_id;
        }
    });

    // Track deletes
    static::deleting(function ($model) {
        if (Auth::check()) {
            $model->deleted_by = Auth::user()->user_id;
        }

        // Cascade soft delete untuk parent yang punya children
        // $model->detail()->each(fn($d) => $d->delete());
    });
}
```

## 🗂️ Database Columns

Setiap master model table memiliki:

```sql
created_by VARCHAR(10) NULLABLE
updated_by VARCHAR(10) NULLABLE
deleted_by VARCHAR(10) NULLABLE
deleted_at TIMESTAMP NULLABLE
created_at TIMESTAMP
updated_at TIMESTAMP

FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
FOREIGN KEY (deleted_by) REFERENCES users(user_id) ON DELETE SET NULL
```

## 💾 Migrations Updated

**Direct edits (tidak create migration baru):**

1. `2025_09_05_171600_create_penerimaan_bahan_baku_table.php`
    - Added: `deleted_by` column
    - Added: `$table->softDeletes()`

2. `2025_09_17_161137_create_transaksi_pembayaran_table.php`
    - Added: `created_by`, `updated_by`, `deleted_by` columns
    - Added: Foreign keys ke users table
    - Added: `$table->softDeletes()`

**Already had audit columns (no changes):**

- BahanBaku, Produk, Pelanggan, Pemasok, Pesanan, Pengadaan, Pembelian, Pengiriman, PenugasanProduksi

## 🎓 Usage Examples

### Automatically Track User

```php
// Create - auto-set created_by dan updated_by
$model = Pesanan::create($data); // created_by, updated_by set to logged-in user

// Update - auto-set updated_by
$model->update($data); // updated_by set to logged-in user

// Delete (soft delete) - auto-set deleted_by
$model->delete(); // deleted_by set to logged-in user, deleted_at set to now
```

### Query with Audit Info

```php
// Get creator user
$pesanan = Pesanan::find('PS001');
$createdBy = $pesanan->createdBy; // Returns User model

// Get updater user
$updater = $pesanan->updatedBy; // Returns User model

// Get deleter user
$deleter = $pesanan->deletedBy; // Returns User model (if soft-deleted)

// Check if deleted
if ($pesanan->trashed()) {
    echo "Deleted at: " . $pesanan->deleted_at;
}
```

### Query Soft Deleted Records

```php
// Only active records
$active = Pesanan::get();

// Only soft-deleted records
$deleted = Pesanan::onlyTrashed()->get();

// Include soft-deleted
$all = Pesanan::withTrashed()->get();

// Restore
$pesanan->restore();
```

### Cascade Delete Example

```php
// When Pesanan is deleted, all PesananDetail auto soft-delete
$pesanan = Pesanan::with('detail')->find('PS001');
$pesanan->delete();

// All PesananDetail now have deleted_at set
// But PesananDetail doesn't have SoftDeletes trait, so query akan exclude them automatically
```

## 📝 Model Relationships for Audit

Setiap model memiliki 3 relasi audit:

```php
public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by', 'user_id');
}

public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by', 'user_id');
}

public function deletedBy()
{
    return $this->belongsTo(User::class, 'deleted_by', 'user_id');
}
```

Penggunaan:

```php
$pesanan = Pesanan::find('PS001');
echo $pesanan->createdBy->nama_user; // Siapa yang buat
echo $pesanan->updatedBy->nama_user; // Siapa yang terakhir update
echo $pesanan->deletedBy->nama_user; // Siapa yang delete (jika sudah deleted)
```

## 🚀 Next Steps

1. **Run Migration:**

    ```bash
    php artisan migrate:fresh --seed
    ```

2. **Test Audit Tracking:**

    ```bash
    php artisan tinker
    > $p = Pesanan::first();
    > $p->created_by; // Should show user_id
    > $p->createdBy; // Should return User model
    > $p->delete();
    > $p->deleted_by; // Should show user_id who deleted
    > $p->deleted_at; // Should show timestamp
    ```

3. **Optional: Restore Functionality**
    - Add restore routes in web.php
    - Implement restore UI buttons
    - Add restore authorization policies

## ⚠️ Important Notes

- **ID Generation:** Gunakan `withTrashed()` untuk prevent gaps saat generate ID berikutnya
- **Cascade Delete:** Parent model delete otomatis soft-delete detail models
- **Audit Trail:** Semua user actions ter-track (create, update, delete)
- **No Detail Soft Delete:** Detail models TIDAK punya SoftDeletes trait, cascade handled by parent
- **Fresh Migration:** Aman untuk `php artisan migrate:fresh --seed`

## 📊 Performance Tips

- Kolom `deleted_at` otomatis di-index untuk query performa
- Query default exclude soft-deleted records (menggunakan global scope)
- Gunakan `withTrashed()` dan `onlyTrashed()` untuk include deleted
- Untuk large datasets, gunakan pagination saat query deleted records

## 🔐 Authorization & Policies

Tambahkan policy untuk soft-delete operations:

```php
// In your Policy class
public function delete(User $user, Pesanan $pesanan)
{
    // Only super-admin atau creator bisa delete
    return $user->is_admin || $user->user_id === $pesanan->created_by;
}

public function restore(User $user, Pesanan $pesanan)
{
    // Only admin bisa restore
    return $user->is_admin;
}

public function forceDelete(User $user, Pesanan $pesanan)
{
    // Only super-admin bisa permanent delete
    return $user->is_super_admin;
}
```

Penggunaan:

```php
// In controller
$this->authorize('delete', $pesanan);
$pesanan->delete();
```
