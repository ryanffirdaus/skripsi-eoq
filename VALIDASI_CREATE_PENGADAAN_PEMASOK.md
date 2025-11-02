# Validasi Akses Pemasok di Halaman Create Pengadaan

## Overview

Menambahkan authorization checks di halaman create pengadaan sehingga hanya Staf Pengadaan (R04) dan Manajer Pengadaan (R09) yang dapat menginput pemasok (supplier) saat membuat pengadaan baru untuk item bahan_baku.

## Perubahan yang Dilakukan

### 1. **app/Http/Controllers/PengadaanController.php**

#### Method `create()` - Line 129-220

Menambahkan user Auth dan pass ke Props:

```php
public function create()
{
    // ... authorization check ...

    $user = Auth::user();  // <-- ADDED

    $pemasok = Pemasok::active()->...;

    // ... get pesanan, bahanBaku, produk ...

    return Inertia::render('pengadaan/create', [
        'pemasoks' => $pemasok,
        'pesanan' => $pesanan,
        'bahanBaku' => $bahanBaku,
        'produk' => $produk,
        'auth' => [                    // <-- ADDED
            'user' => [
                'user_id' => $user->user_id,
                'nama_lengkap' => $user->nama_lengkap,
                'role_id' => $user->role_id,
            ]
        ]
    ]);
}
```

#### Method `store()` - Line 342-420

Menambahkan custom validation untuk pemasok_id input. Hanya R04 dan R09 yang boleh input pemasok:

```php
$validator->after(function ($validator) use ($request, $user) {
    if ($request->has('items')) {
        foreach ($request->items as $index => $item) {
            // ... existing validations ...

            // Authorization: Hanya R04 (Staf Pengadaan) dan R09 (Manajer Pengadaan)
            // yang bisa input pemasok
            if (isset($item['pemasok_id']) && !empty($item['pemasok_id'])) {
                // Check role
                if (!in_array($user->role_id, ['R04', 'R09'])) {
                    $validator->errors()->add(
                        "items.{$index}.pemasok_id",
                        "Hanya Staf/Manajer Pengadaan yang dapat mengalokasikan pemasok."
                    );
                }

                // Check jenis_barang (pemasok hanya untuk bahan_baku)
                if (isset($item['jenis_barang']) && $item['jenis_barang'] !== 'bahan_baku') {
                    $validator->errors()->add(
                        "items.{$index}.pemasok_id",
                        "Pemasok hanya dapat diinput untuk item bahan_baku."
                    );
                }
            }
        }
    }
});
```

### 2. **resources/js/pages/pengadaan/create.tsx**

#### Props Interface - Line 97

Menambahkan optional auth property:

```tsx
interface Props {
    pemasoks: Pemasok[];
    pesanan: Pesanan[];
    bahanBaku: BahanBaku[];
    produk: Produk[];
    auth?: {
        // <-- ADDED
        user: {
            user_id: string;
            nama_lengkap: string;
            role_id: string;
        };
    };
}
```

#### Component Function - Line 116

Menambahkan parameter auth dan helper function canInputSupplier():

```tsx
export default function Create({ pemasoks, pesanan, bahanBaku, produk, auth }: Props) {
    // ... state setup ...

    const { data, setData, post, processing, errors, reset } = useForm({...});

    // Authorization helper function
    const canInputSupplier = (): boolean => {
        const userRole = auth?.user?.role_id;
        // Only R04 (Staf Pengadaan) and R09 (Manajer Pengadaan) can input supplier
        return userRole === 'R04' || userRole === 'R09';
    };

    useEffect(() => {
        setData('items', items);
    }, [items, setData]);
```

#### Pemasok Field Rendering - Line 485-510

Conditional rendering berdasarkan role. Jika user bukan R04/R09, tampilkan warning:

```tsx
<div className="md:col-span-2">
    <Label>Pemasok *</Label>
    {item.jenis_barang === 'bahan_baku' ? (
        <>
            {canInputSupplier() ? (
                <>
                    <Select
                        value={item.pemasok_id || ''}
                        onValueChange={(value) => updateItem(index, 'pemasok_id', value)}
                    >
                        <SelectTrigger className={cn('mt-1', ...)}>
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
                    {errors[`items.${index}.pemasok_id`] && (
                        <p className="mt-1 text-sm text-red-600">...</p>
                    )}
                </>
            ) : (
                <div className="mt-1 rounded-md border border-yellow-300 bg-yellow-50 px-3 py-2 text-sm text-yellow-700">
                    <p className="font-medium">⚠ Hanya Staf/Manajer Pengadaan yang dapat input pemasok</p>
                    <p className="mt-1 text-xs">Hubungi Staf Pengadaan untuk mengalokasikan pemasok setelah pengadaan dibuat.</p>
                </div>
            )}
        </>
    ) : (
        <div className="mt-1 rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500">
            - (Produk Internal)
        </div>
    )}
</div>
```

## Authorization Flow

### Frontend (Create Form)

1. Page loads dengan user auth data
2. `canInputSupplier()` dievaluasi:
    - Jika role = R04 atau R09 → dropdown editable
    - Jika role lain → warning message (read-only)

### Backend (API Store)

1. User submit form dengan items array
2. Validator custom check setiap item yang punya pemasok_id:
    - ✓ Check: user role harus R04 atau R09
    - ✓ Check: jenis_barang harus 'bahan_baku' (bukan 'produk')
    - ✓ Check: pemasok_id harus valid (exists di tabel pemasok)
3. Jika semua pass → create pengadaan
4. Jika ada yang fail → return validation errors dengan flash messages

## Pesan Error

### Saat Frontend (Create Page)

- **Non-Pengadaan Staff**: Menampilkan warning panel dengan ikon ⚠
    ```
    "⚠ Hanya Staf/Manajer Pengadaan yang dapat input pemasok
     Hubungi Staf Pengadaan untuk mengalokasikan pemasok setelah pengadaan dibuat."
    ```

### Saat Backend (Store Validation)

```
Jika role tidak authorized:
  "Hanya Staf/Manajer Pengadaan yang dapat mengalokasikan pemasok."

Jika item bukan bahan_baku:
  "Pemasok hanya dapat diinput untuk item bahan_baku."
```

## Testing Scenario

### Test 1: Create sebagai Admin (R01)

1. Buka halaman create pengadaan sebagai Admin
2. Tambah item bahan_baku
3. **Expected**: Pemasok field menampilkan warning, TIDAK bisa pilih dropdown
4. Submit form
5. **Expected**: Validation error "Hanya Staf/Manajer Pengadaan..."

### Test 2: Create sebagai Staf Gudang (R02)

1. Buka halaman create pengadaan sebagai Staf Gudang
2. Tambah item bahan_baku
3. **Expected**: Pemasok field menampilkan warning, TIDAK bisa pilih dropdown
4. Submit form tanpa pemasok (kosong)
5. **Expected**: Pengadaan berhasil dibuat dengan pemasok kosong

### Test 3: Create sebagai Staf Pengadaan (R04)

1. Buka halaman create pengadaan sebagai Staf Pengadaan
2. Tambah item bahan_baku
3. **Expected**: Pemasok field menampilkan dropdown editable, bisa pilih
4. Pilih pemasok dan submit
5. **Expected**: Pengadaan berhasil dibuat dengan pemasok terinput

### Test 4: Create dengan Item Campuran (R04)

1. Buka halaman create pengadaan sebagai Staf Pengadaan (R04)
2. Tambah item bahan_baku + item produk
3. Untuk bahan_baku → pilih pemasok
4. Untuk produk → dropdown tidak ada (disabled)
5. Submit form
6. **Expected**: Pengadaan berhasil dibuat, pemasok hanya di item bahan_baku

### Test 5: Create dengan Pemasok Invalid (R04)

1. Buka halaman create pengadaan sebagai Staf Pengadaan (R04)
2. Tambah item bahan_baku
3. Pilih pemasok yang valid
4. Submit form
5. **Expected**: Pengadaan berhasil dibuat
6. Buka DevTools, modify request untuk pemasok_id dengan ID invalid
7. Submit via API
8. **Expected**: Validation error "pemasok_id" harus exists

## Perbandingan Create vs Edit

| Aspek                  | Create Page                 | Edit Page                            |
| ---------------------- | --------------------------- | ------------------------------------ |
| **Status Restriction** | Tidak ada (semua item baru) | Hanya saat status disetujui_gudang   |
| **Role Check**         | R04, R09 only               | R04, R09 only                        |
| **Item Type Check**    | Semua jenis_barang          | Hanya bahan_baku                     |
| **UI Feedback**        | Warning panel               | Read-only text                       |
| **Validation Layer**   | Backend validator custom    | Backend authorization check + Policy |

## Authorization Matrix - Create Page

| Role                    |   Can Create Pengadaan?    | Can Input Pemasok for Bahan Baku? | Can Submit Bahan Baku without Pemasok? |
| ----------------------- | :------------------------: | :-------------------------------: | :------------------------------------: |
| R01 (Admin)             | ✓ (via canCreatePengadaan) |                 ✗                 |                   ✓                    |
| R02 (Staf Gudang)       | ✓ (via canCreatePengadaan) |                 ✗                 |                   ✓                    |
| R04 (Staf Pengadaan)    | ✓ (via canCreatePengadaan) |                 ✓                 |                   ✓                    |
| R06 (Staf Keuangan)     |             ✗              |                 ✗                 |                   ✗                    |
| R07 (Manajer Gudang)    | ✓ (via canCreatePengadaan) |                 ✗                 |                   ✓                    |
| R09 (Manajer Pengadaan) | ✓ (via canCreatePengadaan) |                 ✓                 |                   ✓                    |
| R10 (Manajer Keuangan)  |             ✗              |                 ✗                 |                   ✗                    |

## Notes

1. **Pemasok Optional di Create**: Unlike edit page dimana pemasok WAJIB di status tertentu, di create page pemasok bersifat OPTIONAL. User non-Pengadaan staff bisa membuat pengadaan tanpa pemasok, kemudian Staf Pengadaan akan mengalokasikan di halaman edit.

2. **Backend Validation**: Semua authorization checks juga di backend, jadi meskipun frontend di-bypass, backend tetap akan reject unauthorized requests.

3. **Consistency dengan Edit**: Flow sama dengan edit page:
    - Frontend: show/hide dropdown berdasarkan role
    - Backend: validate role untuk setiap item dengan pemasok_id
    - Error messages: jelas dan actionable

## Files Modified

| File                                         | Changes                                                                          | Lines                     |
| -------------------------------------------- | -------------------------------------------------------------------------------- | ------------------------- |
| app/Http/Controllers/PengadaanController.php | Add user auth, add auth to Props, add pemasok validation in store                | 129, 217-220, 375-395     |
| resources/js/pages/pengadaan/create.tsx      | Add auth Props, add canInputSupplier helper, update rendering, fix useEffect dep | 97, 129-132, 485-510, 137 |

## Deployment Checklist

- [x] Backend validation added
- [x] Frontend helpers added
- [x] Error messages consistent
- [x] No database migration needed
- [x] Backwards compatible (pemasok optional)
- [x] ESLint errors fixed
- [ ] Test all scenarios before deploy
