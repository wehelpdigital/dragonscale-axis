# CLAUDE.md - DS AXIS Development Guide

This file provides guidance to Claude Code when working with the DS AXIS codebase. It integrates the PACT (Project Architecture & Code Toolkit) agent system for harmonious, consistent development.

---

## 1. Project Identity

| Property | Value |
|----------|-------|
| **Project Name** | DS AXIS (Dragon Scale Axis) |
| **Company** | Dragon Scale Web Company |
| **Purpose** | Centralized Admin Dashboard / Parent System |
| **Framework** | Laravel 12 on PHP 8.2+ |
| **UI Template** | Skote Admin Template v4.3.0 (Bootstrap 5) |
| **Database** | MySQL (Remote: `onmartph_axis` / Local: XAMPP) |
| **Build Tool** | Vite with laravel-vite-plugin |
| **Timezone** | Asia/Manila (Philippines) |

### What is DS AXIS?

DS AXIS is the **main centralized admin system** for Dragon Scale Web Company. It serves as the **parent system** that manages all sub-business ventures under the Dragon Scale umbrella:

| Sub-Venture | Module | Purpose |
|-------------|--------|---------|
| Dragon Scale Crypto | Crypto Investment | Price tracking, thresholds, income logging |
| Dragon Scale Store(s) | E-commerce | Products, orders, shipping, discounts |
| Ani-Senso Academy | Courses | Educational content, memberships |
| Cross-Venture | CRM & Access | Client management, access tags, triggers |
| Cross-Venture | Affiliates | Affiliate tracking (expanding) |
| Future | New Ventures | System designed for continuous growth |

**Key Understanding**: DS AXIS is the **ADMIN BACKEND**. Separate frontend systems will be built for customer-facing interfaces.

---

## 2. PACT Agent System

DS AXIS uses the PACT (Project Architecture & Code Toolkit) system for development. All agents work together using shared skill documentation.

### 2.1 Architect Skills (Knowledge Base)

These skill files document the system architecture. **Always consult before development:**

| Skill File | Purpose | Path |
|------------|---------|------|
| **codebase-architect** | System structure, patterns, conventions | `.claude/skills/codebase-architect.md` |
| **database-architect** | Database schema, relationships, types | `.claude/skills/database-architect.md` |
| **logic-architect** | Business logic, data flows, APIs | `.claude/skills/logic-architect.md` |
| **anisenso-course-architect** | Ani-Senso frontend project architecture | `.claude/skills/anisenso-course-architect.md` |
| **ds-axis-agent-harmony** | Agent collaboration rules | `.claude/skills/ds-axis-agent-harmony.md` |
| **pact-skills-updater** | Skill synchronization guide | `.claude/skills/pact-skills-updater.md` |

### 2.2 PACT Agents

| Agent | When to Use |
|-------|-------------|
| **pact-preparation** | Research requirements, gather documentation before implementation |
| **pact-architect** | Design component structure, plan implementation strategy |
| **pact-database-engineer** | Create migrations, design database schema |
| **pact-backend-coder** | Implement controllers, models, services, API endpoints |
| **pact-frontend-coder** | Create Blade templates, JavaScript, CSS, DataTables |
| **pact-test-engineer** | Test, debug, and fix errors |
| **ui-ux-engineer** | Design and implement UI components |
| **performance-optimizer** | Optimize queries and code performance |
| **security-code-auditor** | Review code for security vulnerabilities |
| **code-consistency-analyzer** | Verify code follows established patterns |
| **library-scout** | Research libraries before implementation |

### 2.3 Development Workflow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    DS AXIS PACT DEVELOPMENT FLOW                        │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  1. UNDERSTAND REQUEST                                                 │
│     • Read relevant skill files (codebase, database, logic)           │
│     • Identify which module/venture this affects                       │
│                                                                         │
│  2. PREPARE (if complex)                                               │
│     • Use pact-preparation for research                                │
│     • Use library-scout if external libraries needed                   │
│                                                                         │
│  3. PLAN (if non-trivial)                                              │
│     • Use pact-architect to design solution                            │
│     • Follow existing patterns from codebase-architect                 │
│                                                                         │
│  4. IMPLEMENT                                                          │
│     • pact-database-engineer → Migrations (if needed)                  │
│     • pact-backend-coder → Controllers, Models, Logic                  │
│     • pact-frontend-coder → Views, JavaScript, CSS                     │
│     • ui-ux-engineer → Complex UI components                           │
│                                                                         │
│  5. VALIDATE                                                           │
│     • code-consistency-analyzer → Verify patterns                      │
│     • security-code-auditor → Check vulnerabilities                    │
│     • pact-test-engineer → Test and debug                              │
│     • performance-optimizer → Optimize if needed                       │
│                                                                         │
│  6. UPDATE DOCUMENTATION                                               │
│     • Invoke pact-skills-updater after significant changes             │
│     • Keep architect skills synchronized with codebase                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Development Commands

```bash
# Install dependencies
composer install
npm install

# Build frontend assets
npm run build           # Production build (Vite)
npm run dev             # Development with HMR

# Run tests
php artisan test        # All tests (PHPUnit + Pest)
./vendor/bin/phpunit tests/Feature/ExampleTest.php  # Single test

# Database
php artisan migrate
php artisan tinker

# Development server (if not using XAMPP)
php artisan serve
```

---

## 4. Architecture Patterns

> **Reference**: For complete patterns, see `.claude/skills/codebase-architect.md`

### 4.1 Models

**All models MUST extend `App\Models\BaseModel`** which provides:
- Asia/Manila timezone for all timestamps
- Consistent datetime serialization (`Y-m-d H:i:s`)
- Fresh timestamps in Philippines timezone

```php
class ModelName extends BaseModel {
    protected $table = 'table_name';
    protected $fillable = ['usersId', 'fieldName', 'delete_status'];
    protected $casts = [
        'decimalField' => 'decimal:2',
        'cryptoValue' => 'decimal:8',
    ];

    // REQUIRED: Active scope for soft deletes
    public function scopeActive($query) {
        return $query->where('delete_status', 'active');
    }

    // REQUIRED: User scoping for multi-tenant data
    public function scopeForUser($query, $userId) {
        return $query->where('usersId', $userId);
    }
}
```

**Soft Delete Convention** - Uses `delete_status` column (NOT Laravel's SoftDeletes):
- Values: `'active'` or `'deleted'`
- Always filter: `->where('delete_status', 'active')` or `Model::active()`

**User Scoping** - All user-specific data uses `usersId` foreign key:
```php
$data = Model::active()->forUser(Auth::id())->get();
```

### 4.2 Controllers

**Standard CRUD Pattern:**
```php
// Index - List with filters
public function index(Request $request) {
    $query = Model::active()->forUser(Auth::id());

    if ($request->filled('start_date')) {
        $query->whereDate('created_at', '>=', $request->start_date);
    }

    $data = $query->orderBy('created_at', 'desc')->paginate(100);
    return view('page', compact('data'));
}

// Store - Create with validation
public function store(Request $request) {
    $validator = Validator::make($request->all(), [
        'field' => 'required|string|max:255',
    ], [
        'field.required' => 'Custom error message',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    Model::create([
        'usersId' => Auth::id(),
        'field' => $request->field,
        'delete_status' => 'active',
    ]);

    return redirect()->route('index')->with('success', 'Created successfully');
}

// Destroy - Soft delete with ownership check
public function destroy($id) {
    $item = Model::where('id', $id)
        ->where('usersId', Auth::id())
        ->where('delete_status', 'active')
        ->first();

    if (!$item) {
        return response()->json(['success' => false, 'message' => 'Not found'], 404);
    }

    $item->update(['delete_status' => 'deleted']);
    return response()->json(['success' => true, 'message' => 'Deleted successfully']);
}
```

**JSON Response Format:**
```php
// Success
return response()->json(['success' => true, 'message' => 'Done', 'data' => $result]);

// Error
return response()->json(['success' => false, 'message' => 'Error', 'error' => $e->getMessage()], 500);
```

---

## 5. View/Template Patterns

> **Reference**: For complete patterns, see `.claude/skills/codebase-architect.md`

### 5.1 Layout Structure

**Master Layout:** `layouts/master.blade.php` (sidebar)

```blade
@extends('layouts.master')

@section('title') Page Title @endsection

@section('css')
    <!-- Page-specific CSS -->
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Category @endslot
        @slot('title') Page Title @endslot
    @endcomponent

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Page content in cards -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Title</h4>
            <!-- Content -->
        </div>
    </div>
@endsection

@section('script')
    <!-- Page-specific JavaScript -->
@endsection
```

### 5.2 Form Pattern

```blade
<form method="POST" action="{{ route('store') }}">
    @csrf
    <div class="mb-3">
        <label for="field" class="form-label">Label <span class="text-danger">*</span></label>
        <input type="text"
               class="form-control @error('field') is-invalid @enderror"
               id="field" name="field" value="{{ old('field') }}">
        <div class="invalid-feedback">@error('field'){{ $message }}@enderror</div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Save</button>
</form>
```

### 5.3 Delete Modal Pattern

```blade
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
```

---

## 6. JavaScript Patterns

### 6.1 AJAX with CSRF Token

```javascript
$.ajax({
    url: '/api/endpoint/' + id,
    type: 'DELETE',
    data: { _token: '{{ csrf_token() }}' },
    success: function(response) {
        if (response.success) {
            toastr.success(response.message, 'Success!');
            location.reload();
        }
    },
    error: function(xhr) {
        toastr.error(xhr.responseJSON?.message || 'Error occurred', 'Error!');
    }
});
```

### 6.2 Delete Modal Handler

```javascript
let itemToDelete = null;

$('.delete-btn').on('click', function() {
    itemToDelete = {
        id: $(this).data('item-id'),
        name: $(this).data('item-name'),
        row: $(this).closest('tr')
    };
    $('#deleteItemName').text(itemToDelete.name);
    $('#deleteModal').modal('show');
});

$('#confirmDelete').on('click', function() {
    if (!itemToDelete) return;

    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Deleting...');

    $.ajax({
        url: '/api/delete/' + itemToDelete.id,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                $('#deleteModal').modal('hide');
                toastr.success('Deleted successfully!');
                itemToDelete.row.fadeOut(400, function() { $(this).remove(); });
            }
        },
        complete: function() {
            $btn.prop('disabled', false).html('Delete');
            itemToDelete = null;
        }
    });
});
```

### 6.3 Toastr Configuration

```javascript
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000
};
```

---

## 7. Database Conventions

> **Reference**: For complete schema, see `.claude/skills/database-architect.md`

| Convention | Format | Example |
|------------|--------|---------|
| Table names | snake_case with prefix | `ecom_products`, `as_courses` |
| Column names | camelCase | `usersId`, `taskCoin`, `originalPhpValue` |
| Primary key | `id` | bigint unsigned, auto-increment |
| Foreign keys | `{model}Id` | `usersId`, `taskId`, `productId` |
| Soft delete | `delete_status` | enum('active', 'deleted') |
| Ordering | `{item}Order` | `chapterOrder`, `topicsOrder` |
| Currency (PHP) | decimal(15,2) | `originalPhpValue` |
| Crypto values | decimal(20,8) | `currentCoinValue` |

### Module Table Prefixes

| Module | Prefix | Example |
|--------|--------|---------|
| Core | (none) | `users`, `task` |
| Crypto | `historical_`, `income_` | `historical_price`, `income_logger` |
| E-commerce | `ecom_` | `ecom_products`, `ecom_orders` |
| Ani-Senso | `as_` | `as_courses`, `as_topics` |
| Access/Tags | `axis_`, `clients_` | `axis_tags`, `clients_all_database` |

---

## 8. Route Patterns

**Standard CRUD Routes:**
```php
Route::get('/resource', [Controller::class, 'index'])->name('resource')->middleware('auth');
Route::get('/resource-add', [Controller::class, 'create'])->name('resource.create')->middleware('auth');
Route::post('/resource-add', [Controller::class, 'store'])->name('resource.store')->middleware('auth');
Route::get('/resource-edit', [Controller::class, 'edit'])->name('resource.edit')->middleware('auth');
Route::put('/resource/{id}', [Controller::class, 'update'])->name('resource.update')->middleware('auth');
Route::delete('/resource/{id}', [Controller::class, 'destroy'])->name('resource.destroy')->middleware('auth');
```

**DataTables Data Endpoint:**
```php
Route::get('/resource/data', [Controller::class, 'getData'])->name('resource.data')->middleware('auth');
```

---

## 9. File Structure

```
ds-axis/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/                    # Authentication
│   │   ├── Api/                     # API endpoints
│   │   ├── Crypto*Controller.php    # Crypto module
│   │   ├── Ecommerce/               # E-commerce module
│   │   └── aniSensoAdmin/           # Courses module
│   └── Models/
│       ├── BaseModel.php            # EXTEND THIS for all models
│       └── [Models by module]
├── resources/views/
│   ├── layouts/
│   │   ├── master.blade.php         # Primary sidebar layout
│   │   └── sidebar.blade.php        # Navigation menu
│   ├── components/
│   │   └── breadcrumb.blade.php     # Reusable breadcrumb
│   ├── ecommerce/                   # E-commerce views
│   ├── aniSensoAdmin/               # Course views
│   └── crypto-*.blade.php           # Crypto views
├── routes/
│   ├── web.php                      # Web routes
│   └── api.php                      # API routes
└── .claude/skills/                  # PACT skill files
    ├── codebase-architect.md
    ├── database-architect.md
    ├── logic-architect.md
    ├── ds-axis-agent-harmony.md
    └── pact-skills-updater.md
```

---

## 10. Key Libraries

- **Backend:** Laravel 12, Laravel Sanctum, Yajra DataTables
- **Frontend:** Bootstrap 5 (Skote v4.3.0), jQuery, DataTables, Select2, Toastr, SweetAlert2, TinyMCE, Dragula
- **Build:** Vite with laravel-vite-plugin

---

## 11. Quick Reference Checklist

### Before Creating a New Feature:
- [ ] Read relevant skill files (codebase, database, logic)
- [ ] Identify which module/venture this belongs to
- [ ] Check existing patterns for similar features

### When Creating Models:
- [ ] Extend `BaseModel`
- [ ] Include `usersId` for user-scoped data
- [ ] Include `delete_status` with default `'active'`
- [ ] Add `scopeActive()` and `scopeForUser()` methods

### When Creating Controllers:
- [ ] Check user ownership before modify/delete
- [ ] Return JSON for AJAX with `success` boolean
- [ ] Use `Validator::make()` with custom messages

### When Creating Views:
- [ ] Extend `layouts.master`
- [ ] Include breadcrumb component
- [ ] Use Toastr for notifications
- [ ] Include CSRF token in AJAX calls

### When Creating Migrations:
- [ ] Use appropriate table prefix
- [ ] Use camelCase for column names
- [ ] Include `delete_status` enum column
- [ ] Include proper indexes

### After Significant Changes:
- [ ] Invoke pact-skills-updater to sync documentation
- [ ] Run code-consistency-analyzer to verify patterns
- [ ] Consider security-code-auditor for sensitive features

### UI/UX Text Visibility Checklist:
- [ ] Use `text-dark` for primary text on light backgrounds
- [ ] Use `text-secondary` for secondary/muted text (NOT `text-muted`)
- [ ] Use `text-body-secondary` for Bootstrap 5 helper text
- [ ] Ensure badge text is visible: `bg-info text-white`, `bg-warning text-dark`
- [ ] Verify table cell text uses adequate contrast
- [ ] Check dynamically generated HTML for proper text classes

---

## 12. UI/UX Visibility Guidelines

All PACT agents (especially `ui-ux-engineer`, `pact-frontend-coder`, `code-consistency-analyzer`) MUST verify text visibility and contrast when creating or reviewing UI components.

### 12.1 Text Color Classes (Bootstrap 5 + Skote)

| Context | Recommended Class | Avoid |
|---------|------------------|-------|
| Primary body text | `text-dark` | `text-muted`, unclassed text |
| Secondary text | `text-secondary` | `text-muted` (too light) |
| Helper/hint text | `text-body-secondary` | `text-muted` |
| Links | `text-primary` | Default browser blue |
| Success text | `text-success` | - |
| Warning text | `text-warning` + dark bg | `text-warning` alone |
| Error text | `text-danger` | - |
| On dark backgrounds | `text-white` | - |

### 12.2 Badge Visibility Rules

```html
<!-- CORRECT - Ensure text is visible -->
<span class="badge bg-info text-white">Info</span>
<span class="badge bg-warning text-dark">Warning</span>
<span class="badge bg-light text-dark">Light</span>
<span class="badge bg-success">Success</span>
<span class="badge bg-danger">Danger</span>
<span class="badge bg-primary">Primary</span>

<!-- INCORRECT - May have visibility issues -->
<span class="badge bg-info">Info</span>  <!-- Add text-white -->
<span class="badge bg-warning">Warning</span>  <!-- Add text-dark -->
```

### 12.3 Table Text Visibility

When rendering table content dynamically (JavaScript):
```javascript
// CORRECT - Explicit text color
html += `<td class="text-dark">${data.name}</td>`;
html += `<td class="text-secondary">${data.description}</td>`;

// INCORRECT - May inherit wrong color
html += `<td>${data.name}</td>`;
```

### 12.4 Dynamic Content Patterns

```javascript
// For dynamically generated content, always specify text colors:
const html = `
    <tr>
        <td><strong class="text-dark">${escapeHtml(item.name)}</strong></td>
        <td class="text-dark">${escapeHtml(item.value)}</td>
        <td><small class="text-secondary">${escapeHtml(item.note)}</small></td>
    </tr>
`;
```

### 12.5 Card Content

```html
<!-- Card with proper text visibility -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0">Title</h6>
    </div>
    <div class="card-body">
        <p class="text-dark mb-2">Main content here.</p>
        <small class="text-secondary">Helper text here.</small>
    </div>
</div>
```

### 12.6 Empty State Messages

```html
<!-- CORRECT -->
<div class="text-center py-4">
    <i class="mdi mdi-folder-open text-secondary" style="font-size: 2.5rem;"></i>
    <p class="text-dark mt-2 mb-1">No items found.</p>
    <small class="text-secondary">Add items to see them here.</small>
</div>

<!-- INCORRECT - Low visibility -->
<div class="text-center py-4">
    <i class="mdi mdi-folder-open text-muted" style="font-size: 2.5rem;"></i>
    <p class="text-muted mt-2 mb-0">No items found.</p>
</div>
```

### 12.7 Agent Verification Steps

When `ui-ux-engineer`, `pact-frontend-coder`, or `code-consistency-analyzer` reviews UI:

1. **Check static HTML** for proper text color classes
2. **Check JavaScript** that generates HTML for `text-dark`, `text-secondary` usage
3. **Verify badges** have appropriate text contrast
4. **Test empty states** for visibility
5. **Review tables** for cell text visibility
6. **Check modals** for content visibility

---

## 13. Common Commands for Agents

```
# Research before implementation
"Use pact-preparation to research [feature requirements]"

# Design a feature
"Use pact-architect to plan [feature implementation]"

# Database work
"Use pact-database-engineer to create migration for [table]"

# Backend implementation
"Use pact-backend-coder to create [controller/model/service]"

# Frontend implementation
"Use pact-frontend-coder to create [view/JavaScript]"

# UI/UX work
"Use ui-ux-engineer to design [component/page]"

# Verify patterns
"Use code-consistency-analyzer to verify [feature]"

# Security review
"Use security-code-auditor to review [feature]"

# UI visibility check
"Use code-consistency-analyzer to verify text visibility in [component]"

# Update documentation
"Use pact-skills-updater to sync architect skills"
```

---

## 14. Legacy Warnings

### Soft Delete Inconsistencies (Existing Code)
| Module | Pattern | New Code Should Use |
|--------|---------|---------------------|
| Crypto/Users | `delete_status = 'active'/'deleted'` | **This one (preferred)** |
| E-commerce | `deleteStatus = 1/0` | Match existing for this module |
| Ani-Senso | Mixed boolean/string | Match existing for this module |

### Tables to Avoid Modifying
- `access-token` (hyphenated name - legacy)
- `main-account` (hyphenated name - legacy)
- `customers` (Skote demo table)

---

*For detailed patterns and flows, consult the skill files in `.claude/skills/`. For multi-agent collaboration, see `ds-axis-agent-harmony.md`.*
