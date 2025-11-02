# 🔓 Admin Exception for Pengadaan Module

## Overview

Update authorization untuk memungkinkan Admin (R01) melakukan CRUD operations pada pengadaan tanpa mempertimbangkan status apapun. Ini sesuai dengan pattern umum bahwa Admin dapat melakukan semua operasi di semua modul.

## Changes Made

### 1. Backend Authorization - PengadaanController.php

#### store() method - Create Page

**Before**: Only R04 (Staf Pengadaan) and R09 (Manajer Pengadaan) can input pemasok
**After**: R01 (Admin), R04, and R09 can input pemasok

```php
// AUTHORIZATION: Hanya R04 (Staf Pengadaan), R09 (Manajer Pengadaan), atau R01 (Admin) yang bisa input pemasok
if (isset($item['pemasok_id']) && !empty($item['pemasok_id'])) {
    // Check role - Admin (R01) bisa input pemasok kapanpun
    if ($user->role_id !== 'R01' && !in_array($user->role_id, ['R04', 'R09'])) {
        $validator->errors()->add("items.{$index}.pemasok_id",
            "Hanya Staf/Manajer Pengadaan atau Admin yang dapat mengalokasikan pemasok.");
    }

    // Check jenis_barang (pemasok hanya untuk bahan_baku)
    if (isset($item['jenis_barang']) && $item['jenis_barang'] !== 'bahan_baku') {
        $validator->errors()->add("items.{$index}.pemasok_id",
            "Pemasok hanya dapat diinput untuk item bahan_baku.");
    }
}
```

#### update() method - Edit Page

**Before**: Only R04/R09 during disetujui_gudang status
**After**: Admin can edit any status, R04/R09 only during disetujui_gudang

```php
// AUTHORIZATION: Pemasok input hanya boleh Staf/Manajer Pengadaan (R04, R09), atau Admin (R01)
// Admin bisa edit status apapun, tapi R04/R09 hanya saat status = 'disetujui_gudang'
if (isset($detailData['pemasok_id'])) {
    // Check role - Admin (R01) bisa selalu, R04/R09 restricted
    if ($user->role_id !== 'R01' && !in_array($user->role_id, ['R04', 'R09'])) {
        return redirect()->back()
            ->with('flash', [
                'message' => 'Hanya Staf/Manajer Pengadaan atau Admin yang bisa mengalokasikan pemasok.',
                'type' => 'error'
            ])->withInput();
    }

    // Check status - Admin can edit any status, tapi R04/R09 hanya saat disetujui_gudang
    if ($user->role_id !== 'R01' && $pengadaan->status !== 'disetujui_gudang') {
        return redirect()->back()
            ->with('flash', [
                'message' => 'Pemasok hanya bisa dialokasikan saat status "Disetujui Gudang". Hubungi Admin jika perlu exception.',
                'type' => 'error'
            ])->withInput();
    }

    // ... additional validations
}
```

#### update() method - Status Approval

**Before**: Only specific roles can change to specific statuses
**After**: Admin (R01) can change to any status, others follow the same rules

```php
// Status approval validation - Admin bisa change status ke manapun
if ($request->status === 'disetujui_gudang' && $user->role_id !== 'R01' && $user->role_id !== 'R07') {
    // Error
}

if ($request->status === 'disetujui_pengadaan' && $user->role_id !== 'R01' && $user->role_id !== 'R09') {
    // Error
}

if ($request->status === 'disetujui_keuangan' && $user->role_id !== 'R01' && $user->role_id !== 'R10') {
    // Error
}

if ($request->status === 'diproses' && $user->role_id !== 'R01' && $user->role_id !== 'R07') {
    // Error
}
```

### 2. Policy Layer - PengadaanPolicy.php

#### editSupplier() method

```php
public function editSupplier(User $user, Pengadaan $pengadaan): bool
{
    // Admin (R01) bisa edit di status apapun
    if ($user->role_id === 'R01') {
        return true;
    }

    // Check role untuk R04/R09
    $allowedRoles = ['R04', 'R09'];
    if (!in_array($user->role_id, $allowedRoles)) {
        return false;
    }

    // Check status - hanya saat disetujui_gudang
    if ($pengadaan->status !== 'disetujui_gudang') {
        return false;
    }

    // Check jenis_barang
    return $pengadaan->detail()
        ->where('jenis_barang', 'bahan_baku')
        ->exists();
}
```

#### editPrice() method

```php
public function editPrice(User $user, Pengadaan $pengadaan): bool
{
    // Admin (R01) bisa edit di status apapun
    if ($user->role_id === 'R01') {
        return true;
    }

    // Check role untuk others
    $allowedRoles = ['R04', 'R07', 'R09'];
    if (!in_array($user->role_id, $allowedRoles)) {
        return false;
    }

    // Check status
    $editableStatuses = ['pending', 'disetujui_gudang'];
    return in_array($pengadaan->status, $editableStatuses);
}
```

#### approve() method

```php
public function approve(User $user, Pengadaan $pengadaan, string $targetStatus): bool
{
    // Admin (R01) bisa approve ke status apapun
    if ($user->role_id === 'R01') {
        return true;
    }

    // ... role-specific approval logic
}
```

### 3. Frontend Authorization - create.tsx

```tsx
const canInputSupplier = (): boolean => {
    const userRole = auth?.user?.role_id;
    // R01 (Admin), R04 (Staf Pengadaan) and R09 (Manajer Pengadaan) can input supplier
    return userRole === 'R01' || userRole === 'R04' || userRole === 'R09';
};
```

### 4. Frontend Authorization - edit.tsx

```tsx
const canEditSupplier = (): boolean => {
    const userRole = auth?.user?.role_id;
    // R01 (Admin) dapat edit status apapun
    // R04 (Staf Pengadaan) and R09 (Manajer Pengadaan) hanya saat disetujui_gudang
    if (userRole === 'R01') {
        return true; // Admin bisa edit di status apapun
    }
    const isPengadaanStaff = userRole === 'R04' || userRole === 'R09';
    const isCorrectStatus = pengadaan.status === 'disetujui_gudang';
    return isPengadaanStaff && isCorrectStatus;
};

const canEditPrice = (): boolean => {
    const userRole = auth?.user?.role_id;
    // R01 (Admin) dapat edit status apapun
    if (userRole === 'R01') {
        return true; // Admin bisa edit di status apapun
    }
    // Staf/Manajer Gudang (R02, R07), Staf/Manajer Pengadaan (R04, R09)
    const isAuthorizedRole = ['R02', 'R04', 'R07', 'R09'].includes(userRole || '');
    // Only when status is pending or disetujui_gudang
    const isEditableStatus = pengadaan.status === 'pending' || pengadaan.status === 'disetujui_gudang';
    return isAuthorizedRole && isEditableStatus;
};
```

## Authorization Matrix - Updated

| Role                    |  Create Pemasok   |       Edit Pemasok       |         Edit Harga          |       Change Status       |         Approval          |
| ----------------------- | :---------------: | :----------------------: | :-------------------------: | :-----------------------: | :-----------------------: |
| R01 (Admin)             |   ✅ Any status   |      ✅ Any status       |        ✅ Any status        |       ✅ Any status       |       ✅ Any status       |
| R02 (Staf Gudang)       |        ❌         |            ❌            | ✅ pending/disetujui_gudang |             -             |             -             |
| R04 (Staf Pengadaan)    | ✅ No restriction | ✅ disetujui_gudang only | ✅ pending/disetujui_gudang |             -             |             -             |
| R06 (Staf Keuangan)     |        ❌         |            ❌            |             ❌              |             -             |             -             |
| R07 (Manajer Gudang)    |        ❌         |            ❌            | ✅ pending/disetujui_gudang |  ✅ to disetujui_gudang   |  ✅ to disetujui_gudang   |
| R09 (Manajer Pengadaan) | ✅ No restriction | ✅ disetujui_gudang only | ✅ pending/disetujui_gudang | ✅ to disetujui_pengadaan | ✅ to disetujui_pengadaan |
| R10 (Manajer Keuangan)  |        ❌         |            ❌            |             ❌              | ✅ to disetujui_keuangan  | ✅ to disetujui_keuangan  |

## Error Messages - Updated

When Admin tries to do something invalid:

- ❌ **Item type mismatch**: "Pemasok hanya dapat diinput untuk item bahan_baku." (same for all roles)

When Non-Admin tries to exceed their permissions:

- ❌ **Wrong role**: "Hanya Staf/Manajer Pengadaan atau Admin yang bisa mengalokasikan pemasok."
- ❌ **Wrong status (R04/R09 only)**: "Pemasok hanya bisa dialokasikan saat status 'Disetujui Gudang'. Hubungi Admin jika perlu exception."
- ❌ **Wrong approval role**: "Hanya [Role Name] atau Admin yang bisa menyetujui pengadaan di tahap ini."

## Philosophy

✅ **Admin as Super User**: R01 (Admin) dapat melakukan semua operasi di semua modul tanpa restriction pada status
✅ **Status-Based Gates for Others**: R04/R09/R07 dll hanya dapat edit pada status yang sesuai dengan workflow mereka
✅ **Consistent Pattern**: Same pattern applied across create, update, dan status approval
✅ **Security**: Item type validation tetap berlaku untuk semua roles (pemasok hanya untuk bahan_baku)

## Files Modified

| File                                           | Changes                                                    |
| ---------------------------------------------- | ---------------------------------------------------------- |
| `app/Http/Controllers/PengadaanController.php` | +Admin exception in store() and update() methods           |
| `app/Policies/PengadaanPolicy.php`             | +Admin exception in editSupplier(), editPrice(), approve() |
| `resources/js/pages/pengadaan/create.tsx`      | Update canInputSupplier() to include R01                   |
| `resources/js/pages/pengadaan/edit.tsx`        | Update canEditSupplier() and canEditPrice() to include R01 |

## Testing Scenarios

### Scenario 1: Admin Creates Pengadaan with Pemasok

- Login as R01 (Admin)
- Go to Create Pengadaan
- Add items with pemasok allocation
- **Expected**: Dropdown shows for admin, can allocate pemasok, can save ✅

### Scenario 2: Admin Edits Pemasok at Any Status

- Login as R01 (Admin)
- Open pengadaan at status "pending" (or any status)
- Edit pemasok field
- **Expected**: Dropdown editable regardless of status ✅

### Scenario 3: Admin Changes Status to Any Value

- Login as R01 (Admin)
- Edit pengadaan
- Change status from "pending" to "disetujui_keuangan" (or any other)
- **Expected**: Status change allowed ✅

### Scenario 4: Non-Admin at Wrong Status

- Login as R04 (Staf Pengadaan)
- Open pengadaan at status "pending"
- Try to edit pemasok field
- **Expected**: Field is read-only (no dropdown) ✅

### Scenario 5: Non-Admin at Correct Status

- Login as R04 (Staf Pengadaan)
- Open pengadaan at status "disetujui_gudang"
- Edit pemasok field
- **Expected**: Dropdown editable ✅

## Deployment Notes

1. No database changes required
2. No new files added
3. Backward compatible with existing pengadaan records
4. All changes are in authorization logic only
5. No breaking changes to API endpoints

## Summary

Admin (R01) sekarang memiliki penuh kontrol pada modul Pengadaan:

- ✅ Dapat menginput pemasok di status manapun
- ✅ Dapat mengubah harga di status manapun
- ✅ Dapat mengubah status ke manapun
- ✅ Dapat menyetujui di tahap apapun
- ✅ Tidak ada restriction berdasarkan status atau workflow

Ini sesuai dengan prinsip umum bahwa Admin adalah super user dengan akses penuh.
