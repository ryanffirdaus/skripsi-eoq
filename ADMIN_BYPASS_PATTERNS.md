# Admin Bypass Pattern - Quick Reference Guide

**Purpose**: Maintain Admin Absolute capability across the codebase  
**Status**: Active - Applies to all new features and modifications

---

## Standard Admin Bypass Patterns

### Pattern 1: Model-Level Status Check (Recommended)

Use when you have a status field that controls editability.

```php
namespace App\Models;

use Illuminate\Support\Facades\Auth;

class YourModel extends Model
{
    public function canBeEdited()
    {
        $user = Auth::user();

        // Admin (R01) dapat edit di SEMUA status tanpa exception
        if ($user && $user->role_id === 'R01') {
            return true;
        }

        // Other role restrictions
        return in_array($this->status, ['allowed_status_1', 'allowed_status_2']);
    }
}
```

**Usage in Controller**:

```php
public function update(Request $request, YourModel $model)
{
    if (!$model->canBeEdited()) {
        return back()->with('error', 'Cannot edit this record');
    }

    // Update logic...
}
```

---

### Pattern 2: Controller-Level Status Check

Use when you need immediate status-based blocking.

```php
namespace App\Http\Controllers;

use App\Http\Traits\RoleAccess;

class YourModelController extends Controller
{
    use RoleAccess;

    public function edit(YourModel $model)
    {
        if (!$this->isAdmin() && $model->status === 'final_state') {
            abort(403, 'Cannot edit finalized records');
        }

        // Load and return edit view...
    }

    public function update(Request $request, YourModel $model)
    {
        if (!$this->isAdmin() && in_array($model->status, ['status_1', 'status_2'])) {
            return back()->with('error', 'Cannot modify at this stage');
        }

        // Update logic...
    }

    public function destroy(YourModel $model)
    {
        if (!$this->isAdmin() && $model->status === 'protected_status') {
            return back()->with('error', 'Cannot delete protected records');
        }

        $model->delete();
        // Return response...
    }
}
```

---

### Pattern 3: Cancel/Delete Method with Admin Override

Use when you need to protect deletion/cancellation operations.

```php
namespace App\Models;

use Illuminate\Support\Facades\Auth;

class TransactionModel extends Model
{
    public function canBeCancelled()
    {
        // Admin (R01) dapat bypass
        if (Auth::check() && Auth::user()->role_id === 'R01') {
            return true;
        }

        return !in_array($this->status, ['final_status_1', 'final_status_2']);
    }
}
```

---

### Pattern 4: Policy-Based Authorization

Use for complex authorization logic with multiple roles.

```php
namespace App\Policies;

use App\Models\User;
use App\Models\YourModel;
use Illuminate\Auth\Access\Response;

class YourModelPolicy
{
    public function update(User $user, YourModel $model): bool
    {
        // Admin can always update
        if ($user->role_id === 'R01') {
            return true;
        }

        // Other role-specific checks...
        if ($user->role_id === 'SPECIFIC_ROLE') {
            return $model->status !== 'protected_status';
        }

        return false;
    }

    public function delete(User $user, YourModel $model): bool
    {
        // Admin can always delete
        if ($user->role_id === 'R01') {
            return true;
        }

        // Other role-specific checks...
        return $user->id === $model->created_by;
    }
}
```

---

## Key Implementation Rules

### ✅ DO's

1. **Always include Admin bypass** in status checks:

    ```php
    if (!$this->isAdmin() && $model->status === 'restricted') {
        // Prevent action
    }
    ```

2. **Use isAdmin() helper** from RoleAccess trait:

    ```php
    use App\Http\Traits\RoleAccess;

    class MyController extends Controller {
        use RoleAccess;

        public function someAction() {
            if ($this->isAdmin()) {
                // Admin-only logic
            }
        }
    }
    ```

3. **Check Auth user for R01** in models:

    ```php
    if (Auth::user()->role_id === 'R01') {
        return true;
    }
    ```

4. **Document Admin exceptions** in comments:

    ```php
    // Admin (R01) dapat bypass - dapat edit di SEMUA status
    if (!$this->isAdmin() && $model->status === 'final') {
    ```

5. **Test Admin access** for every status:
    - Create record
    - Transition to each status
    - Verify Admin can edit/update/delete at each status
    - Verify non-Admin gets blocked appropriately

---

### ❌ DON'Ts

1. **Don't block Admin entirely**:

    ```php
    // WRONG - blocks even Admin
    if ($model->status === 'final') {
        return back()->with('error', 'Blocked');
    }
    ```

2. **Don't use hard-coded role checks without Admin bypass**:

    ```php
    // WRONG
    if ($user->role_id !== 'SPECIFIC_ROLE') {
        abort(403);
    }

    // RIGHT
    if (!$this->isAdmin() && $user->role_id !== 'SPECIFIC_ROLE') {
        abort(403);
    }
    ```

3. **Don't forget auth() vs Auth::user()**:

    ```php
    // In Models: Use Auth::user()
    if (Auth::check() && Auth::user()->role_id === 'R01')

    // In Controllers: Use $this->isAdmin()
    if (!$this->isAdmin() && condition)
    ```

4. **Don't add status restrictions without considering Admin**:
   Every new status field that blocks operations needs Admin bypass

5. **Don't use inconsistent patterns**:
    - Use the same Admin check across all controllers
    - Apply same patterns to similar features

---

## Admin Role Identifier

**Role ID**: `R01`  
**Role Name**: Admin  
**Permission Level**: Absolute CRUD at all statuses

### Usage Examples:

```php
// Direct check
if ($user->role_id === 'R01') { /* Admin */ }

// Via trait
if ($this->isAdmin()) { /* Admin */ }

// Via policy
if ($user->role_id === 'R01') { /* Admin */ }
```

---

## Available Helper Methods

### From RoleAccess Trait

```php
// Check if user is Admin
$this->isAdmin()

// Check if user has specific role
$this->hasRole('ROLE_ID')

// Check if user is warehouse-related
$this->isGudangRelated()

// Check if user is finance-related
$this->isKeuanganRelated()
```

---

## New Feature Checklist

When adding a new feature with workflow statuses:

- [ ] Define all possible statuses in model
- [ ] Add `canBeEdited()` method to model with Admin bypass
- [ ] Add `canBeCancelled()` or `canBeDeleted()` method if applicable
- [ ] Add controller authorization checks with Admin bypass
- [ ] Test create/read/update/delete for Admin at each status
- [ ] Test that non-Admin users get blocked appropriately
- [ ] Document in model/controller which statuses block operations
- [ ] Add comments explaining Admin bypass: `// Admin (R01) dapat bypass`

---

## Common Status Flows

### Pengadaan Status Flow

```
pending
    ↓
pending_approval_gudang (Manajer Gudang approval)
    ↓
pending_supplier_allocation (Allocate supplier)
    ↓
pending_approval_pengadaan (Manajer Pengadaan approval)
    ↓
pending_approval_keuangan (Manajer Keuangan approval)
    ↓
diproses (Processing)
    ↓
diterima (Received)

Alt:
rejected (At any stage by authorized user)
```

**Admin Access**: Can edit at ANY of these statuses

---

### Pembelian Status Flow

```
draft
    ↓
sent
    ↓
confirmed
    ↓
partially_received
    ↓
fully_received

Alt:
cancelled (From any status except fully_received)
```

**Admin Access**: Can edit/cancel at ANY of these statuses

---

### PenugasanProduksi Status Flow

```
ditugaskan (Assigned)
    ↓
proses (Processing)
    ↓
selesai (Completed)

Alt:
dibatalkan (Cancelled from any stage)
```

**Admin Access**: Can edit/delete at ANY of these statuses

---

## Migration from Old Code

If you find status checks without Admin bypass:

**Before** (Blocking Everyone):

```php
if ($model->status === 'final_state') {
    return back()->with('error', 'Blocked');
}
```

**After** (Allowing Admin):

```php
if (!$this->isAdmin() && $model->status === 'final_state') {
    return back()->with('error', 'Blocked for non-admin users');
}
```

---

## Documentation References

- See `ADMIN_ABSOLUTE_VERIFICATION.md` for complete implementation details
- See `ADMIN_ABSOLUTE_SESSION_SUMMARY.md` for latest changes
- See model files for canBeEdited() implementations
- See controller files for authorization patterns

---

## Support & Questions

For questions about admin bypass implementation:

1. Check this guide first
2. Reference existing implementations (Pengadaan, Pembelian, PenugasanProduksi)
3. Review RoleAccess trait in `app/Http/Traits/RoleAccess.php`
4. Check policies in `app/Policies/` for policy pattern examples
