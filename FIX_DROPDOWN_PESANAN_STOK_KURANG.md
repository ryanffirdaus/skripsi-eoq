# Fix Dropdown Pesanan - Stok Kurang ✅

## Tanggal: 3 November 2025

**Issue:** Dropdown pesanan di create pengadaan tidak menampilkan pesanan yang stoknya kurang

---

## 🐛 Root Cause Analysis

### Problem #1: Status Enum Mismatch ❌

```php
// CODE LAMA (Line 150)
->whereIn('status', ['pending', 'confirmed', 'processing']) // ❌ ENGLISH

// Actual Database (after Indonesian conversion):
enum('status', ['menunggu', 'dikonfirmasi', 'diproses', ...])
```

**Impact:** Query tidak menemukan pesanan apapun karena mencari status English yang tidak ada!

---

### Problem #2: Query Join Syntax ❌

```php
// CODE LAMA (Line 152-154)
->whereHas('detail', function ($query) {
    $query->join('produk', 'pesanan_detail.produk_id', '=', 'produk.produk_id')
        ->whereColumn('pesanan_detail.jumlah_produk', '>', 'produk.stok_produk');
})
```

**Issues:**

1. Manual `join` could cause table name conflicts
2. Not utilizing existing Eloquent relationship
3. More complex and error-prone

---

## ✅ Solution Applied

### Fix #1: Update Status to Indonesian

```php
// BEFORE
->whereIn('status', ['pending', 'confirmed', 'processing'])

// AFTER
->whereIn('status', ['menunggu', 'dikonfirmasi', 'diproses'])
```

### Fix #2: Use Eloquent Relationship

```php
// BEFORE
->whereHas('detail', function ($query) {
    $query->join('produk', 'pesanan_detail.produk_id', '=', 'produk.produk_id')
        ->whereColumn('pesanan_detail.jumlah_produk', '>', 'produk.stok_produk');
})

// AFTER
->whereHas('detail', function ($query) {
    $query->whereHas('produk', function ($q) {
        $q->whereColumn('pesanan_detail.jumlah_produk', '>', 'produk.stok_produk');
    });
})
```

**Benefits:**

- ✅ Uses existing `produk()` relationship in `PesananDetail` model
- ✅ Cleaner and more maintainable
- ✅ Avoids table name conflicts
- ✅ Laravel handles the join automatically

---

## 📋 Complete Filter Logic

```php
$pesanan = Pesanan::with(['pelanggan:pelanggan_id,nama_pelanggan', 'detail.produk'])
    ->select('pesanan_id', 'pelanggan_id', 'tanggal_pemesanan', 'total_harga', 'status')

    // Filter 1: Only active orders (Indonesian status)
    ->whereIn('status', ['menunggu', 'dikonfirmasi', 'diproses'])

    // Filter 2: Orders without existing pengadaan
    ->whereDoesntHave('pengadaan')

    // Filter 3: Orders where ANY product has insufficient stock
    ->whereHas('detail', function ($query) {
        $query->whereHas('produk', function ($q) {
            $q->whereColumn('pesanan_detail.jumlah_produk', '>', 'produk.stok_produk');
        });
    })

    ->orderBy('tanggal_pemesanan', 'desc')
    ->get()
```

**Filter Explanation:**

1. **Status Filter**: Only shows active orders that need procurement
2. **Pengadaan Filter**: Excludes orders that already have procurement
3. **Stock Filter**: Only shows orders where stock is insufficient

---

## 🔍 Query Flow

### Example Scenario:

```
Pesanan PS001:
- Produk A: dipesan 100, stok 80 → KURANG ✅ (show)
- Produk B: dipesan 50, stok 60 → CUKUP

Pesanan PS002:
- Produk C: dipesan 30, stok 50 → CUKUP
- Produk D: dipesan 20, stok 25 → CUKUP
→ TIDAK DITAMPILKAN (semua stok cukup)

Pesanan PS003 (status: selesai):
- Produk A: dipesan 200, stok 80 → KURANG
→ TIDAK DITAMPILKAN (status bukan menunggu/dikonfirmasi/diproses)
```

---

## 🎯 Expected Behavior After Fix

### Dropdown Will Show:

✅ Pesanan with status: `menunggu`, `dikonfirmasi`, or `diproses`
✅ Pesanan without existing pengadaan record
✅ Pesanan where AT LEAST ONE product has `jumlah_produk > stok_produk`

### Dropdown Will NOT Show:

❌ Pesanan with status: `siap`, `dikirim`, `diterima`, `selesai`, `dibatalkan`
❌ Pesanan that already have pengadaan record
❌ Pesanan where ALL products have sufficient stock

---

## 🔧 Files Modified

| File                                           | Change                       | Line    |
| ---------------------------------------------- | ---------------------------- | ------- |
| `app/Http/Controllers/PengadaanController.php` | Status: English → Indonesian | 150     |
| `app/Http/Controllers/PengadaanController.php` | Query: join → whereHas       | 152-157 |

---

## 📝 Related Models

### Relationships Used:

```php
// Pesanan Model
public function detail() {
    return $this->hasMany(PesananDetail::class, 'pesanan_id', 'pesanan_id');
}

public function pengadaan() {
    return $this->hasMany(Pengadaan::class, 'pesanan_id', 'pesanan_id');
}

// PesananDetail Model
public function produk() {
    return $this->belongsTo(Produk::class, 'produk_id', 'produk_id');
}
```

---

## ✅ Testing Checklist

To verify the fix works:

1. **Create Test Data:**
    - Create pesanan with status `menunggu`
    - Add products where `jumlah_produk > stok_produk`

2. **Test Dropdown:**
    - Navigate to: `/pengadaan/create`
    - Verify dropdown shows the test pesanan
    - Verify it displays stock shortage information

3. **Test Filters:**
    - Create pesanan with sufficient stock → should NOT appear
    - Create pesanan with status `selesai` and stock shortage → should NOT appear
    - Create pengadaan for pesanan → pesanan should disappear from dropdown

---

## 🎓 Lessons Learned

### 1. Always Use Enum Values After Migration

When converting enums to another language, update ALL code that references the enum:

- ✅ Controllers
- ✅ Models
- ✅ Factories
- ✅ Seeders
- ✅ Frontend validation

### 2. Prefer Eloquent Relationships Over Manual Joins

```php
// ❌ BAD: Manual join
->whereHas('detail', function ($q) {
    $q->join('produk', ...)->whereColumn(...);
})

// ✅ GOOD: Use relationship
->whereHas('detail', function ($q) {
    $q->whereHas('produk', function ($p) {
        $p->whereColumn(...);
    });
})
```

### 3. Test Complex Queries

Always test queries with multiple conditions:

- Status filters
- Relationship filters
- Column comparisons

---

## 📊 Impact

| Aspect             | Before               | After                         |
| ------------------ | -------------------- | ----------------------------- |
| **Dropdown Items** | 0 (broken)           | Shows correct filtered orders |
| **Status Filter**  | Using English values | Using Indonesian values       |
| **Query Method**   | Manual join          | Eloquent relationship         |
| **Code Quality**   | Complex, error-prone | Clean, maintainable           |

---

**STATUS: FIXED ✅**

Dropdown pesanan sekarang akan menampilkan pesanan yang:

1. Status aktif (menunggu/dikonfirmasi/diproses)
2. Belum ada pengadaan
3. Minimal 1 produk stoknya kurang

Test di browser untuk memverifikasi dropdown sudah muncul!
