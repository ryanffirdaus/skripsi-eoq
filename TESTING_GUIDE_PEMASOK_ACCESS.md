# 🧪 Testing Guide - Pemasok Access Control Feature

## Pre-Test Setup

Pastikan sudah login dengan akun yang memiliki role yang berbeda-beda.

### Test User Roles

- **R01**: Admin
- **R02**: Staf Gudang
- **R04**: Staf Pengadaan
- **R06**: Staf Keuangan
- **R07**: Manajer Gudang
- **R09**: Manajer Pengadaan
- **R10**: Manajer Keuangan

## Test Scenario 1: Authorized Access (R04 - Staf Pengadaan)

### Setup

1. Login sebagai R04 (Staf Pengadaan)
2. Navigate ke Pengadaan → Ubah (edit) existing pengadaan
3. Pengadaan status harus: **"disetujui_gudang"**
4. Item harus: **"bahan_baku"** (not "produk")

### Expected Result ✅

- Pemasok field menampilkan **editable dropdown**
- Dapat memilih pemasok dari list
- Dapat save changes dengan pemasok_id terisi

### Test Steps

```
1. Open pengadaan edit page
2. Look for pemasok field for bahan_baku items
3. Click dropdown → should show pemasok list
4. Select a pemasok
5. Submit form
6. Verify pemasok saved successfully
```

**Screenshots Expected**:

- Dropdown dengan multiple pemasok options
- Form submit berhasil dengan success message

---

## Test Scenario 2: Unauthorized - Wrong Role (R02 - Staf Gudang)

### Setup

1. Login sebagai R02 (Staf Gudang)
2. Navigate ke Pengadaan → Ubah same pengadaan
3. Status: **"disetujui_gudang"**
4. Item: **"bahan_baku"**

### Expected Result ❌

- Pemasok field menampilkan **read-only view** (bukan dropdown)
- Menampilkan nama pemasok yang sudah dialokasikan atau "- (Menunggu alokasi)"
- Tidak bisa edit

### Test Steps

```
1. Open pengadaan edit page as R02
2. Look for pemasok field
3. Field should be read-only (no dropdown)
4. If pemasok already set, show name
5. If empty, show "- (Menunggu alokasi)"
```

**Screenshots Expected**:

- Static text, tidak ada dropdown
- Field disabled/read-only appearance

---

## Test Scenario 3: Unauthorized - Wrong Status (R04 with status != disetujui_gudang)

### Setup

1. Login sebagai R04 (Staf Pengadaan)
2. Navigate ke Pengadaan → Ubah another pengadaan
3. Status: **"pending"** (NOT "disetujui_gudang")
4. Item: **"bahan_baku"**

### Expected Result ❌

- Pemasok field menampilkan **read-only view**
- Status belum tepat untuk alokasi pemasok

### Test Steps

```
1. Open pengadaan edit page with status=pending
2. Look for pemasok field
3. Should show read-only (no dropdown)
4. Try to submit dengan pemasok_id → should fail
```

**Screenshots Expected**:

- Read-only field
- Error message: "Pemasok hanya bisa dialokasikan saat status 'Disetujui Gudang'."

---

## Test Scenario 4: Produk Item (No Pemasok)

### Setup

1. Login sebagai any role
2. Navigate ke Pengadaan → Ubah pengadaan dengan produk item
3. Item: **"produk"** (not "bahan_baku")

### Expected Result ✅

- Pemasok field untuk item produk menampilkan: **"- (Produk Internal)"**
- Tidak ada dropdown
- Read-only

### Test Steps

```
1. Open pengadaan with produk items
2. Scroll to produk item row
3. Look at pemasok column
4. Should show "- (Produk Internal)"
5. No dropdown regardless of role/status
```

**Screenshots Expected**:

- Static text "- (Produk Internal)"
- Different styling (gray background)

---

## Test Scenario 5: API Validation (Backend)

### Setup - Testing via cURL or Postman

#### Test 5A: Unauthorized Role

```bash
curl -X PUT http://localhost/pengadaan/123 \
  -H "Authorization: Bearer TOKEN_R02" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "disetujui_gudang",
    "details": [{
      "pengadaan_detail_id": "ABC123",
      "pemasok_id": "PEMASOK001"
    }]
  }'
```

**Expected Response**:

```php
// Redirect dengan flash message:
"Hanya Staf/Manajer Pengadaan yang bisa mengalokasikan pemasok."
```

#### Test 5B: Wrong Status

```bash
curl -X PUT http://localhost/pengadaan/123 \
  -H "Authorization: Bearer TOKEN_R04" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "pending",
    "details": [{
      "pengadaan_detail_id": "ABC123",
      "pemasok_id": "PEMASOK001"
    }]
  }'
```

**Expected Response**:

```php
// Redirect dengan flash message:
"Pemasok hanya bisa dialokasikan saat status 'Disetujui Gudang'."
```

#### Test 5C: Produk Item

```bash
curl -X PUT http://localhost/pengadaan/123 \
  -H "Authorization: Bearer TOKEN_R04" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "disetujui_gudang",
    "details": [{
      "pengadaan_detail_id": "PRODUK123",
      "pemasok_id": "PEMASOK001"
    }]
  }'
```

**Expected Response**:

```php
// Redirect dengan flash message:
"Pemasok hanya bisa diinput untuk item bahan_baku, bukan produk internal."
```

---

## Test Scenario 6: Permission Denied - Admin Not Allowed

### Setup

1. Login sebagai R01 (Admin)
2. Navigate ke Pengadaan → Ubah pengadaan dengan bahan_baku
3. Status: **"disetujui_gudang"**

### Expected Result ❌

- Pemasok field menampilkan **read-only view** (tidak ada dropdown)
- Admin tidak bisa edit pemasok (hanya R04/R09)

### Note

Admin bisa edit semua hal lain (status, catatan, dll) tapi khusus pemasok, hanya R04/R09.

---

## Test Scenario 7: Workflow End-to-End

### Setup

1. Create new pengadaan dengan bahan_baku items
2. Pengadaan created dengan status **"pending"**

### Step 1: Staf Gudang (R02) Approve

```
1. Login as R02 (Staf Gudang)
2. Go to pengadaan
3. Change status to "disetujui_gudang"
4. Submit
✓ Expected: Status updated, now ready for pemasok allocation
```

### Step 2: Staf Pengadaan (R04) Allocate Supplier

```
1. Login as R04 (Staf Pengadaan)
2. Go to same pengadaan
3. Pemasok dropdown should now be EDITABLE (was read-only before)
4. Select pemasok for each bahan_baku item
5. Submit
✓ Expected: Pemasok allocated successfully
```

### Step 3: Verify Read-Only After Allocation

```
1. Login as R02 (Staf Gudang) again
2. Go to same pengadaan
3. Pemasok field should show allocated supplier (read-only)
4. Cannot change it
✓ Expected: Pemasok visible but cannot edit
```

---

## Error Message Verification

### Error Message 1: Wrong Role

```
Hanya Staf/Manajer Pengadaan yang bisa mengalokasikan pemasok.
```

- Should appear when: Non-R04/R09 user tries to update pemasok_id
- Should appear on: Flash message at top of page

### Error Message 2: Wrong Status

```
Pemasok hanya bisa dialokasikan saat status "Disetujui Gudang".
```

- Should appear when: Status is not 'disetujui_gudang'
- Should appear on: Flash message at top of page

### Error Message 3: Wrong Item Type

```
Pemasok hanya bisa diinput untuk item bahan_baku, bukan produk internal.
```

- Should appear when: Trying to set pemasok for produk item
- Should appear on: Flash message at top of page

---

## Database Verification

### Check Pemasok ID Update

```sql
-- Verify pemasok_id was saved correctly
SELECT pengadaan_detail_id, pemasok_id, jenis_barang
FROM pengadaan_detail
WHERE pengadaan_id = 'TEST123';

-- Should show pemasok_id filled for bahan_baku items
```

### Check Activity Log (if exists)

```sql
-- Verify who allocated which pemasok
SELECT * FROM audit_tracking
WHERE model_type = 'PengadaanDetail'
AND model_id LIKE '%TEST123%'
ORDER BY created_at DESC;
```

---

## Frontend CSS/Visual Verification

### Editable Dropdown State

```
Visual indicator:
- White background
- Border color: primary
- Cursor: pointer
- Shows dropdown arrow
```

### Read-Only State

```
Visual indicator:
- Gray background (or white)
- Border color: gray
- Cursor: default (not pointer)
- No dropdown arrow
- Static text display
```

### Produk Internal State

```
Visual indicator:
- Gray background
- Text color: gray
- Italic text
- Static: "- (Produk Internal)"
```

---

## Regression Testing

### Existing Features Should Still Work

- [ ] Create pengadaan → should still work
- [ ] Edit status → should still work
- [ ] Edit catatan → should still work
- [ ] Edit harga satuan → should still work
- [ ] Delete pengadaan → should still work
- [ ] View pengadaan list → should still work

### Existing Modules Should Not Break

- [ ] Pembelian module → should still work
- [ ] PenerimaanBahanBaku module → should still work
- [ ] TransaksiPembayaran module → should still work
- [ ] Pesanan module → should still work

---

## Performance Testing

### Load Testing

```
- Edit large pengadaan (50+ items) as R04
- UI should remain responsive
- Dropdown should load quickly
- No lag when selecting pemasok
```

### Browser Compatibility

```
- Chrome: Test
- Firefox: Test
- Edge: Test
- Safari (if applicable): Test
```

---

## Success Criteria ✅

All scenarios must pass:

- [x] R04/R09 can edit pemasok when status=disetujui_gudang
- [x] Other roles see read-only when status=disetujui_gudang
- [x] All roles see read-only when status≠disetujui_gudang
- [x] All items show "- (Produk Internal)" not pemasok field
- [x] Proper error messages shown
- [x] Backend validates properly
- [x] No regression in existing features
- [x] UI responsive and clear
- [x] Database correctly updated

---

## Reporting Issues

If any test fails, report with:

1. **Test Scenario Number**: (e.g., "Scenario 1")
2. **Expected vs Actual**: What should happen vs what happened
3. **Screenshots**: If possible
4. **Steps to Reproduce**: Exact steps to replicate
5. **User Role**: Which role was logged in
6. **Browser/Device**: Chrome, Firefox, etc.
7. **Error Messages**: Any error text from console
8. **Timestamp**: When the issue occurred

---

**Testing Duration**: ~30-45 minutes for all scenarios
**Testers Needed**: 1-2 people  
**Environment**: Staging (not production)
**Date Tested**: [fill in]
**Tester Name**: [fill in]
**Status**: [PASS/FAIL]
