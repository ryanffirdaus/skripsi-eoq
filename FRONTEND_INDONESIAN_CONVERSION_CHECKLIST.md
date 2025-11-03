# 🎨 FRONTEND INDONESIAN CONVERSION CHECKLIST

**Status Backend**: ✅ COMPLETED (Database + Models)  
**Status Frontend**: ⚠️ NEEDS UPDATE  
**Date**: 3 November 2025

---

## 📋 OVERVIEW

Setelah konversi backend (database + models) selesai, sekarang perlu update **SEMUA** file frontend untuk:

1. **Field Names**: `created_by` → `dibuat_oleh`, `updated_by` → `diupdate_oleh`, dll
2. **Status Values**: English → Indonesian (pending → menunggu, dll)
3. **Status Labels**: Update badge labels dan filter options

---

## 🔍 MODULES YANG PERLU DIUPDATE

### ✅ **1. PENGADAAN MODULE** - SUDAH SELESAI

- [x] `resources/js/pages/pengadaan/index.tsx` ✅
- [x] `resources/js/pages/pengadaan/create.tsx` ✅
- [x] `resources/js/pages/pengadaan/edit.tsx` ✅
- [x] `resources/js/pages/pengadaan/show.tsx` ✅

**Status**: Sudah menggunakan bahasa Indonesia lengkap

---

### 🔴 **2. PESANAN MODULE** - PERLU UPDATE

#### **Status Database Saat Ini:**

```
Actual Values: menunggu, dikonfirmasi, diproses, dikirim, selesai, dibatalkan
```

#### **Files to Update:**

##### **A. Index Page** - `resources/js/pages/pesanan/index.tsx`

**Current Issues:**

```tsx
// Line 15: Type definition masih English
status: 'pending' | 'diproses' | 'dikirim' | 'selesai' | 'dibatalkan';

// Line 68: Status colors - MASIH ADA 'pending'
const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',  // ❌ Should be 'menunggu'
    diproses: 'bg-blue-100 text-blue-800',      // ✅
    dikirim: 'bg-purple-100 text-purple-800',   // ✅
    selesai: 'bg-green-100 text-green-800',     // ✅
    dibatalkan: 'bg-red-100 text-red-800',      // ✅
};

// Line 74: Status labels - MASIH ADA 'pending'
const statusLabels = {
    pending: 'Pending',      // ❌ Should be 'menunggu'
    diproses: 'Diproses',    // ✅
    dikirim: 'Dikirim',      // ✅
    diterima: 'Diterima',    // ⚠️ Not in DB - remove or map to 'selesai'
    selesai: 'Selesai',      // ✅
    dibatalkan: 'Dibatalkan', // ✅
};

// Line 161: Filter options - MASIH ADA 'pending'
options: [
    { value: 'pending', label: 'Pending' },      // ❌ Should be 'menunggu'
    { value: 'diproses', label: 'Diproses' },    // ✅
    { value: 'dikirim', label: 'Dikirim' },      // ✅
    { value: 'selesai', label: 'Selesai' },      // ✅
    { value: 'dibatalkan', label: 'Dibatalkan' }, // ✅
],
```

**Changes Needed:**

- [ ] Update type definition: `'pending'` → `'menunggu'`
- [ ] Update statusColors: add `menunggu`, remove `pending`
- [ ] Update statusLabels: add `menunggu`, remove `pending` and `diterima`
- [ ] Update filter options: change `pending` to `menunggu`

##### **B. Show Page** - `resources/js/pages/pesanan/show.tsx`

**Current Issues:**

```tsx
// Line 40: Type definition - SEMUA ENGLISH!
status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled';

// Line 46: Field name masih English
created_by?: UserRef;   // ❌ Should be dibuat_oleh
updated_by?: UserRef;   // ❌ Should be diupdate_oleh

// Line 60-67: Status colors - SEMUA ENGLISH!
const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',      // ❌
    confirmed: 'bg-blue-100 text-blue-800 border-blue-200',          // ❌
    processing: 'bg-purple-100 text-purple-800 border-purple-200',   // ❌
    shipped: 'bg-indigo-100 text-indigo-800 border-indigo-200',      // ❌
    delivered: 'bg-green-100 text-green-800 border-green-200',       // ❌
    cancelled: 'bg-red-100 text-red-800 border-red-200',             // ❌
};

// Line 69-76: Status labels - MIXED!
const statusLabels = {
    pending: 'Pending',         // ❌
    confirmed: 'Dikonfirmasi',  // ⚠️ Label OK but key wrong
    processing: 'Diproses',     // ⚠️ Label OK but key wrong
    shipped: 'Dikirim',         // ⚠️ Label OK but key wrong
    delivered: 'Diterima',      // ⚠️ Label OK but key wrong
    cancelled: 'Dibatalkan',    // ⚠️ Label OK but key wrong
};

// Line 246: Field name masih English
createdBy={pesanan.created_by?.nama_lengkap}  // ❌ Should be dibuat_oleh
```

**Changes Needed:**

- [ ] Update type definition: ALL to Indonesian
    - `'pending'` → `'menunggu'`
    - `'confirmed'` → `'dikonfirmasi'`
    - `'processing'` → `'diproses'`
    - `'shipped'` → `'dikirim'`
    - `'delivered'` → remove (tidak ada di DB, gunakan 'selesai')
    - `'cancelled'` → `'dibatalkan'`
- [ ] Update field names: `created_by` → `dibuat_oleh`, `updated_by` → `diupdate_oleh`
- [ ] Update statusColors: ALL keys to Indonesian
- [ ] Update statusLabels: ALL keys to Indonesian
- [ ] Update component references to use `dibuat_oleh`

##### **C. Create Page** - `resources/js/pages/pesanan/create.tsx`

**Needs Check:**

- [ ] Check if there are status-related form fields
- [ ] Check field name references

##### **D. Edit Page** - `resources/js/pages/pesanan/edit.tsx`

**Needs Check:**

- [ ] Check status validation
- [ ] Check field name references
- [ ] Check status dropdown options

---

### 🔴 **3. PENGIRIMAN MODULE** - PERLU UPDATE

#### **Status Database Saat Ini:**

```
Actual Values: menunggu, dalam_perjalanan, diterima, dikirim, selesai, dibatalkan
```

#### **Files to Update:**

##### **A. Index Page** - `resources/js/pages/pengiriman/index.tsx`

**Current Issues:**

```tsx
// Line 75: Status colors - MIXED!
const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',      // ❌ Should be 'menunggu'
    dikirim: 'bg-blue-100 text-blue-800',          // ✅
    selesai: 'bg-green-100 text-green-800',        // ✅
    dibatalkan: 'bg-red-100 text-red-800',         // ✅
    // MISSING: menunggu, dalam_perjalanan, diterima
};

// Line 80: Status labels - MIXED!
const statusLabels = {
    pending: 'Pending',        // ❌ Should be 'menunggu'
    dikirim: 'Dikirim',        // ✅
    selesai: 'Diterima',       // ⚠️ Label says "Diterima" but key is "selesai"
    dibatalkan: 'Dibatalkan',  // ✅
    // MISSING: menunggu, dalam_perjalanan, diterima
};

// Line 244: Filter options - MIXED!
options: [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Pending' },        // ❌
    { value: 'dikirim', label: 'Dikirim' },        // ✅
    { value: 'selesai', label: 'Diterima' },       // ⚠️
    { value: 'dibatalkan', label: 'Dibatalkan' },  // ✅
],
```

**Changes Needed:**

- [ ] Add missing status colors: `menunggu`, `dalam_perjalanan`, `diterima`
- [ ] Remove `pending`
- [ ] Update statusLabels to match DB values
- [ ] Update filter options
- [ ] Verify mapping: apakah `selesai` = "Selesai" atau "Diterima"?

##### **B. Show Page** - `resources/js/pages/pengiriman/show.tsx`

**Needs Check:**

- [ ] Check status type definition
- [ ] Check status colors
- [ ] Check field name references (created_by, updated_by)

##### **C. Create Page** - `resources/js/pages/pengiriman/create.tsx`

**Needs Check:**

- [ ] Check status dropdown options
- [ ] Check initial status value

##### **D. Edit Page** - `resources/js/pages/pengiriman/edit.tsx`

**Needs Check:**

- [ ] Check status dropdown options
- [ ] Check status validation

---

### 🔴 **4. PENUGASAN PRODUKSI MODULE** - PERLU UPDATE

#### **Status Database Saat Ini:**

```
Actual Values: menunggu, ditugaskan, sedang_dikerjakan, selesai, dibatalkan
```

#### **Files to Update:**

##### **A. Index Page** - `resources/js/pages/penugasan-produksi/index.tsx`

**Current Issues:**

```tsx
// Line 39, 41: Field names masih English
created_by?: string | User;     // ❌ Should be dibuat_oleh
created_by_user?: User;         // ❌ Should be dibuat_oleh_user

// Line 84: Status options - MIXED!
const statusOptions = [
    { value: 'ditugaskan', label: 'Ditugaskan' },           // ✅
    { value: 'proses', label: 'Sedang Dikerjakan' },        // ⚠️ DB has 'sedang_dikerjakan'
    { value: 'selesai', label: 'Selesai' },                 // ✅
    { value: 'dibatalkan', label: 'Dibatalkan' },           // ✅
    // MISSING: menunggu
];

// Line 153: Field key masih English
key: 'created_by',  // ❌ Should be 'dibuat_oleh'

// Line 160: Field references masih English
const createdByUser = item.createdBy || item.created_by_user ||
    (typeof item.created_by === 'object' ? item.created_by : null);
// ❌ All should be dibuat_oleh

// Line 178-184: Status map - MIXED!
const statusMap: Record<string, string> = {
    ditugaskan: 'Ditugaskan',           // ✅
    proses: 'Sedang Dikerjakan',        // ⚠️ DB uses 'sedang_dikerjakan'
    selesai: 'Selesai',                 // ✅
    dibatalkan: 'Dibatalkan',           // ✅
    // MISSING: menunggu, sedang_dikerjakan
};
```

**Changes Needed:**

- [ ] Update field names: `created_by` → `dibuat_oleh` (ALL occurrences)
- [ ] Update status options: `proses` → `sedang_dikerjakan`, add `menunggu`
- [ ] Update status map: add `menunggu`, `sedang_dikerjakan`
- [ ] Update type interfaces: `created_by` → `dibuat_oleh`
- [ ] Update column key: `created_by` → `dibuat_oleh`

##### **B. Show Page** - `resources/js/pages/penugasan-produksi/show.tsx`

**Needs Check:**

- [ ] Check status type definition
- [ ] Check status colors
- [ ] Check field name references

##### **C. Create Page** - `resources/js/pages/penugasan-produksi/create.tsx`

**Needs Check:**

- [ ] Check status dropdown
- [ ] Check initial status value

##### **D. Edit Page** - `resources/js/pages/penugasan-produksi/edit.tsx`

**Needs Check:**

- [ ] Check status dropdown options
- [ ] Check status validation

---

### 🔴 **5. PEMBELIAN MODULE** - PERLU UPDATE

#### **Status Database Saat Ini:**

```
Actual Values: draft, menunggu, dipesan, dikirim, dikonfirmasi, diterima, dibatalkan
```

#### **Files to Update:**

##### **A. Index Page** - `resources/js/pages/pembelian/index.tsx`

**Current Issues:**

```tsx
// Line 20: Field name masih English
dibuat_oleh: string; // ⚠️ Good! But need to verify usage

// Line 78-89: Status colors - MIXED!
const statusColors = {
    draft: 'outline',
    sent: 'secondary', // ❌ DB has 'dikirim'
    confirmed: 'default', // ❌ DB has 'dikonfirmasi'
    partially_received: 'default', // ❌ Not in DB
    fully_received: 'secondary', // ❌ Not in DB (use 'diterima')
    cancelled: 'destructive', // ❌ DB has 'dibatalkan'
};

// Line 91-97: Badge labels - MIXED!
{
    status === 'draft' && 'Draft';
} // ✅
{
    status === 'sent' && 'Terkirim';
} // ❌ DB uses 'dikirim'
{
    status === 'confirmed' && 'Dikonfirmasi';
} // ❌ DB uses 'dikonfirmasi'
{
    status === 'partially_received' && 'Diterima Sebagian';
} // ❌ Not in DB
{
    status === 'fully_received' && 'Diterima Lengkap';
} // ❌ Not in DB
{
    status === 'cancelled' && 'Dibatalkan';
} // ❌ DB uses 'dibatalkan'
```

**Changes Needed:**

- [ ] Update statusColors keys to match DB:
    - `sent` → `dikirim`
    - `confirmed` → `dikonfirmasi`
    - `partially_received` → remove or map
    - `fully_received` → `diterima`
    - `cancelled` → `dibatalkan`
    - Add: `menunggu`, `dipesan`
- [ ] Update badge render logic
- [ ] Update filter options (if exists)
- [ ] Check field name usage

##### **B. Show Page** - `resources/js/pages/pembelian/show.tsx`

**Needs Check:**

- [ ] Check status type definition
- [ ] Check status colors
- [ ] Check field name references

##### **C. Create Page** - `resources/js/pages/pembelian/create.tsx`

**Needs Check:**

- [ ] Check status dropdown
- [ ] Check initial status value

##### **D. Edit Page** - `resources/js/pages/pembelian/edit.tsx`

**Needs Check:**

- [ ] Check status dropdown options
- [ ] Check status validation

---

### 🟡 **6. PENERIMAAN BAHAN BAKU MODULE** - FIELD NAMES ONLY

#### **Database Info:**

```
❌ NO STATUS COLUMN - Only field names need update
```

#### **Files to Update:**

##### **A. Index Page** - `resources/js/pages/penerimaan-bahan-baku/index.tsx`

**Check:**

- [ ] Field names: `created_by` → `dibuat_oleh`
- [ ] Field names: `updated_by` → `diupdate_oleh`

##### **B. Show Page** - `resources/js/pages/penerimaan-bahan-baku/show.tsx`

**Check:**

- [ ] Field name references in timestamp section

##### **C. Create Page** - `resources/js/pages/penerimaan-bahan-baku/create.tsx`

**Check:**

- [ ] Form field references

##### **D. Edit Page** - `resources/js/pages/penerimaan-bahan-baku/edit.tsx`

**Current Issues:**

```tsx
// Line 50, 75: Has status types BUT NOT IN DB!
status: 'pending' | 'partial' | 'complete' | 'returned';
status_quality: 'pending' | 'passed' | 'failed' | 'partial';
```

**Check:**

- [ ] Verify if these status fields actually exist in DB
- [ ] If NOT, these are likely frontend-only or should be removed
- [ ] Update field names

---

### 🟡 **7. TRANSAKSI PEMBAYARAN MODULE** - FIELD NAMES ONLY

#### **Database Info:**

```
❌ NO STATUS COLUMN - Only field names need update
```

#### **Files to Update:**

##### **A. Index Page** - `resources/js/pages/transaksi-pembayaran/index.tsx`

**Check:**

- [ ] Field names: `created_by` → `dibuat_oleh`
- [ ] Field names: `updated_by` → `diupdate_oleh`

##### **B. Show Page** - `resources/js/pages/transaksi-pembayaran/show.tsx`

**Check:**

- [ ] Field name references in timestamp section

##### **C. Create Page** - `resources/js/pages/transaksi-pembayaran/create.tsx`

**Check:**

- [ ] Form field references

##### **D. Edit Page** - `resources/js/pages/transaksi-pembayaran/edit.tsx`

**Check:**

- [ ] Form field references

---

### 🟡 **8. USERS MODULE** - FIELD NAMES ONLY

#### **Files to Update:**

##### **A. Show Page** - `resources/js/pages/users/show.tsx`

**Current Issues:**

```tsx
// Line 23-26: Field names masih English
created_by_id?: string;      // ❌ Should be dibuat_oleh_id
updated_by_id?: string;      // ❌ Should be diupdate_oleh_id
created_by?: UserRef | null; // ❌ Should be dibuat_oleh
updated_by?: UserRef | null; // ❌ Should be diupdate_oleh

// Line 120-121: Field references
createdBy={user.created_by?.nama_lengkap}  // ❌ Should be dibuat_oleh
updatedBy={user.updated_by?.nama_lengkap}  // ❌ Should be diupdate_oleh
```

**Changes Needed:**

- [ ] Update interface field names
- [ ] Update component references

##### **B. Index Page** - `resources/js/pages/users/index.tsx`

**Check:**

- [ ] Field name references in columns

##### **C. Create Page** - `resources/js/pages/users/create.tsx`

**Check:**

- [ ] No changes needed (tidak ada created_by di create form)

##### **D. Edit Page** - `resources/js/pages/users/edit.tsx`

**Check:**

- [ ] Field name references

---

### 🟡 **9. PRODUK MODULE** - FIELD NAMES ONLY

#### **Files to Update:**

##### **A. Show Page** - `resources/js/pages/produk/show.tsx`

**Current Issues:**

```tsx
// Line 33-36: Field names masih English
created_by_id?: string;      // ❌ Should be dibuat_oleh_id
updated_by_id?: string;      // ❌ Should be diupdate_oleh_id
created_by?: UserRef | null; // ❌ Should be dibuat_oleh
updated_by?: UserRef | null; // ❌ Should be diupdate_oleh

// Line 222-223: Field references
createdBy={produk.created_by?.nama_lengkap}  // ❌ Should be dibuat_oleh
updatedBy={produk.updated_by?.nama_lengkap}  // ❌ Should be diupdate_oleh
```

**Changes Needed:**

- [ ] Update interface field names
- [ ] Update component references

##### **B. Index Page** - `resources/js/pages/produk/index.tsx`

**Check:**

- [ ] Field name references in columns

---

### 🟡 **10. PELANGGAN MODULE** - FIELD NAMES ONLY

**Check ALL files for field name references**

---

### 🟡 **11. PEMASOK MODULE** - FIELD NAMES ONLY

**Check ALL files for field name references**

---

### 🟡 **12. BAHAN BAKU MODULE** - FIELD NAMES ONLY

**Check ALL files for field name references**

---

## 📊 SUMMARY BY PRIORITY

### 🔴 **HIGH PRIORITY** (Status Changes Required)

1. **Pesanan** - 2 files confirmed (index, show)
2. **Pengiriman** - 1 file confirmed (index)
3. **Penugasan Produksi** - 1 file confirmed (index)
4. **Pembelian** - 1 file confirmed (index)

### 🟡 **MEDIUM PRIORITY** (Field Names Only)

5. **Penerimaan Bahan Baku** - Need to verify status columns
6. **Transaksi Pembayaran**
7. **Users** - 1 file confirmed (show)
8. **Produk** - 1 file confirmed (show)
9. **Pelanggan**
10. **Pemasok**
11. **Bahan Baku**

---

## 🎯 ACTION PLAN

### **Phase 1: Status Changes** 🔴

1. Update `Pesanan` module (index.tsx, show.tsx)
2. Update `Pengiriman` module (index.tsx, show.tsx)
3. Update `Penugasan Produksi` module (index.tsx, show.tsx)
4. Update `Pembelian` module (index.tsx, show.tsx)

### **Phase 2: Field Names** 🟡

1. Update `Users/show.tsx`
2. Update `Produk/show.tsx`
3. Scan and update remaining modules

### **Phase 3: Verification** ✅

1. Test each module after changes
2. Verify no TypeScript errors
3. Test filters and badges work correctly
4. Verify timestamps show correctly

---

## 📝 NOTES

### **Common Patterns to Find:**

```bash
# Search for old field names
created_by
updated_by
deleted_by
rejected_by

# Search for old status values
pending|confirmed|processing|shipped|delivered|cancelled
in_progress|in_transit|completed

# Search for status type definitions
status: '...'
```

### **Common Changes:**

```tsx
// FIELD NAMES
created_by   → dibuat_oleh
updated_by   → diupdate_oleh
deleted_by   → dihapus_oleh
rejected_by  → ditolak_oleh

// STATUS VALUES (varies by module - see individual sections)
pending      → menunggu
confirmed    → dikonfirmasi
processing   → diproses
shipped      → dikirim
delivered    → diterima / selesai
cancelled    → dibatalkan
in_progress  → sedang_dikerjakan
in_transit   → dalam_perjalanan
completed    → selesai
```

---

**Last Updated**: 2025-11-03  
**Status**: Documentation Complete - Ready for Implementation
