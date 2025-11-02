# Penugasan Produksi String ID Conversion

## Overview

Converted Penugasan Produksi (Production Assignment) module from auto-increment integer primary key to string-based custom ID with the format `PPD#####` (e.g., PPD00001, PPD00002, PPD00010). This change ensures consistency across all modules in the application, following the pattern established by Pesanan (PS###) and Pengadaan (PGD###).

## ID Format

- **Prefix**: PPD (Penugasan Produksi)
- **Format**: PPD + 5-digit zero-padded number
- **Examples**: PPD00001, PPD00002, PPD00010, PPD00100
- **Pattern**: Automatically generated in model boot() method based on the next available sequence number

## Files Modified

### 1. Database Migrations

#### Original Migration (2025_10_19_create_penugasan_produksi.php)

**Change**: Updated schema to use string primary key

```php
// Before
$table->id('penugasan_id');

// After
$table->string('penugasan_id', 50)->primary();
```

**Note**: This migration is for fresh installations. Existing databases use the data migration below.

#### Data Migration (2025_11_03_convert_penugasan_produksi_to_string_id.php) - NEW FILE

**Purpose**: Safely converts existing data from integer to string format

**Up Migration Process**:

1. Creates temporary table `penugasan_produksi_new` with string primary key
2. Copies all data from old table with ID conversion formula: `'PPD' . str_pad($id, 5, '0', STR_PAD_LEFT)`
3. Example conversion: `1` → `PPD00001`, `10` → `PPD00010`, `123` → `PPD00123`
4. Drops old table and renames new table
5. All timestamps and relationships are preserved

**Down Migration Process**:

1. Creates temporary table with original auto-increment structure
2. Extracts numeric portion: `substr($penugasan_id, 3)` converts `PPD00001` → `1`
3. Restores original integer format
4. Drops new table and renames back

**Key Features**:

- Uses `DB::table()` for direct database operations
- Preserves all data including soft deletes (`deleted_at`)
- Maintains all foreign key relationships
- Bidirectional reversibility for safety

### 2. Model (app/Models/PenugasanProduksi.php)

**Changes Made**:

a) **Primary Key Configuration**

```php
protected $primaryKey = 'penugasan_id';
protected $keyType = 'string';
public $incrementing = false;
```

b) **Fillable Array** - Added 'penugasan_id'

```php
protected $fillable = [
    'penugasan_id',  // NEW
    'pengadaan_detail_id',
    'user_id',
    'jumlah_produksi',
    'status',
    'deadline',
    'catatan',
    'created_by',
    'updated_by',
    'deleted_by',
];
```

c) **Boot Method** - Added auto-generation logic

```php
static::creating(function ($model) {
    // Auto-generate penugasan_id
    if (!$model->penugasan_id) {
        $latest = static::withTrashed()->orderBy('penugasan_id', 'desc')->first();
        $nextId = $latest ? (int) substr($latest->penugasan_id, 3) + 1 : 1;
        $model->penugasan_id = 'PPD' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    if (Auth::check()) {
        $model->created_by = Auth::user()->user_id;
    }
});
```

**Auto-Generation Logic**:

- `withTrashed()`: Includes soft-deleted records in sequence calculation to prevent ID gaps
- `substr($latest->penugasan_id, 3)`: Extracts numeric portion (positions 3-7)
- `+1`: Increments to get next ID
- `str_pad(..., 5, '0', STR_PAD_LEFT)`: Pads with zeros to maintain 5-digit format
- Prepends 'PPD' to create final ID

**Example Sequence**:

- First record: `NULL` → `1` → `PPD00001`
- Second record: `PPD00001` → `2` → `PPD00002`
- Tenth record: `PPD00009` → `10` → `PPD00010`
- Hundredth record: `PPD00099` → `100` → `PPD00100`

### 3. Factory (database/factories/PenugasanProduksiFactory.php)

**Status**: No changes needed

- Factory does NOT explicitly set `penugasan_id`
- Model auto-generation handles ID creation when records are created through factory
- Factory uses `state()` methods for status variations (assigned, in progress, completed, cancelled, overdue)
- Auto-generation applies automatically to all created instances

### 4. Seeder (database/seeders/PenugasanProduksiSeeder.php)

**Status**: No changes needed

- Seeder does NOT explicitly set `penugasan_id` in `create()` calls
- Model auto-generation applies automatically
- Seeder creates realistic test data with relationships to PengadaanDetail and Users

### 5. Frontend - TypeScript Types

#### resources/js/pages/penugasan-produksi/index.tsx

**Change**: Updated interface property type

```typescript
// Before
interface Penugasan extends Record<string, unknown> {
    penugasan_id: number;

// After
interface Penugasan extends Record<string, unknown> {
    penugasan_id: string;
```

#### resources/js/pages/penugasan-produksi/edit.tsx

**Change**: Updated interface property type

```typescript
// Before
interface Penugasan {
    penugasan_id: number;

// After
interface Penugasan {
    penugasan_id: string;
```

**Impact**:

- Routes like `/penugasan-produksi/PPD00001/edit` now work with string IDs
- No changes needed to route handling - Laravel's route model binding works with string PKs
- URL generation remains automatic through Inertia.js

### 6. Controller (app/Http/Controllers/PenugasanProduksiController.php)

**Status**: No changes needed

- Controller does NOT explicitly set `penugasan_id` in create/store operations
- Model auto-generation handles ID creation
- Route model binding automatically resolves string IDs (Laravel 11 feature)
- All existing methods work without modification

### 7. Tests (tests/Feature/PenugasanProduksiTest.php)

**Status**: No changes needed

- Tests use factory for data creation
- Factory auto-generation applies to all test instances
- Test assertions don't depend on specific numeric ID values
- All existing tests remain valid

## Migration Instructions

### For Fresh Installation

1. Run migrations in order:
    ```bash
    php artisan migrate
    ```
    The original migration (2025_10_19_create_penugasan_produksi.php) already creates the table with string primary key.

### For Existing Database

1. Run the data migration:

    ```bash
    php artisan migrate
    ```

    The conversion migration (2025_11_03_convert_penugasan_produksi_to_string_id.php) will:
    - Preserve all existing data
    - Convert IDs to new format
    - Maintain all relationships

2. Verify migration success:
    ```bash
    php artisan tinker
    >>> App\Models\PenugasanProduksi::first()->penugasan_id
    // Should return string like "PPD00001"
    ```

### Rolling Back

If needed, the data migration can be rolled back:

```bash
php artisan migrate:rollback
```

This will restore the original auto-increment structure.

## Testing

### Manual Testing

1. Create a new Penugasan Produksi record through the UI
2. Verify ID is formatted as `PPD#####`
3. Check database table:
    ```sql
    SELECT penugasan_id, pengadaan_detail_id, user_id FROM penugasan_produksi LIMIT 5;
    ```

### Automated Tests

Run the test suite:

```bash
php artisan test tests/Feature/PenugasanProduksiTest.php
```

All existing tests should pass without modification because:

- Factory auto-generates IDs
- Tests don't assert on specific numeric values
- String IDs are handled transparently by Eloquent

## Key Design Decisions

### 1. Why `withTrashed()`?

Using `withTrashed()` when calculating the next ID ensures that:

- Soft-deleted records are included in the sequence
- No ID gaps are created when records are deleted
- The sequence remains predictable and sequential

### 2. Why Temporary Table for Migration?

The temp table approach provides:

- Data safety (old table preserved until swap is successful)
- Atomic operation (table is either old or new, never inconsistent)
- Foreign key integrity (relationships maintained throughout)

### 3. Why String Primary Key?

String PKs provide:

- Business meaning (PPD prefix indicates production assignment)
- Human readability in logs and debugging
- Consistency with other modules (Pesanan, Pengadaan, etc.)
- Easier tracking and auditing

## Consistency Across Modules

All main modules now use consistent string ID format:

| Module                 | Prefix  | Format       | Example      |
| ---------------------- | ------- | ------------ | ------------ |
| Pesanan                | PS      | PS#####      | PS00001      |
| Pengadaan              | PGD     | PGD#####     | PGD00001     |
| Pengiriman             | PG      | PG#####      | PG00001      |
| Pembelian              | PB      | PB#####      | PB00001      |
| **Penugasan Produksi** | **PPD** | **PPD#####** | **PPD00001** |

## Performance Considerations

- String primary keys have minimal performance impact in MySQL
- Index on string PKs is automatically created
- Foreign key lookups remain fast
- No change to query performance compared to integer PKs

## Rollback Plan

If issues arise:

1. All migrations are reversible
2. Run `php artisan migrate:rollback` to restore auto-increment
3. Data integrity is preserved in both directions

## Notes for Developers

1. **Never manually set penugasan_id**: The model boot() method handles auto-generation
2. **Use factories in tests**: Don't hard-code test IDs
3. **String IDs in routes**: Laravel handles routing with string PKs automatically
4. **Database queries**: Can use string IDs directly in where() clauses

## Related Documentation

- PENGADAAN_OTOMATIS_EOQ.md - EOQ procurement implementation
- TEMPLATE_GUIDE.md - Template and form styling
- ROLE_ACCESS_DOCUMENTATION.md - RBAC implementation
