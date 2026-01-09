# Codebase Architect - DS AXIS System Map

> **Purpose**: This skill provides a comprehensive architectural map of the DS AXIS (Dragon Scale Axis) Laravel application to help agents deeply understand the system structure, data workflows, UI patterns, and established conventions when creating new tasks or modules.

---

## 1. System Overview

### 1.1 Project Identity

| Property | Value |
|----------|-------|
| **Project Name** | DS AXIS (Dragon Scale Axis) |
| **Company** | Dragon Scale Web Company |
| **Project Type** | Centralized Admin Dashboard / Parent System |
| **Framework** | Laravel 12 on PHP 8.2+ |
| **UI Template** | Skote Admin Template v4.3.0 (Bootstrap 5) |
| **Database** | MySQL (Remote Production / XAMPP Local) |
| **Build Tool** | Vite with laravel-vite-plugin |
| **Timezone** | Asia/Manila (Philippines) |

### 1.2 System Purpose & Architecture

DS AXIS is the **main centralized admin system** for Dragon Scale Web Company. It serves as the **parent system** that manages all sub-business ventures and partner systems under the Dragon Scale umbrella.

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         DS AXIS ARCHITECTURE                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│                    ┌─────────────────────────┐                         │
│                    │       DS AXIS           │                         │
│                    │  (This Admin System)    │                         │
│                    │                         │                         │
│                    │  • Centralized Control  │                         │
│                    │  • Multi-Venture Admin  │                         │
│                    │  • Unified Dashboard    │                         │
│                    └───────────┬─────────────┘                         │
│                                │                                        │
│           ┌────────────────────┼────────────────────┐                  │
│           │                    │                    │                  │
│           ▼                    ▼                    ▼                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐        │
│  │  Sub-Venture 1  │  │  Sub-Venture 2  │  │  Sub-Venture N  │        │
│  │  (E-commerce    │  │  (Courses/      │  │  (Future        │        │
│  │   Stores)       │  │   Memberships)  │  │   Ventures)     │        │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘        │
│           │                    │                    │                  │
│           ▼                    ▼                    ▼                  │
│  ┌─────────────────────────────────────────────────────────────┐      │
│  │              SEPARATE FRONTEND SYSTEMS                       │      │
│  │         (Customer-facing apps - NOT this system)            │      │
│  └─────────────────────────────────────────────────────────────┘      │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

**Key Understanding**: DS AXIS is the ADMIN BACKEND. Separate frontend systems will be built for customer-facing interfaces. This system handles:
- All administrative operations
- Business logic and data management
- Multi-venture/multi-partner management
- CRM and business tools
- Access control and memberships

### 1.3 Current Business Modules (Sub-Ventures)

| Module | Sub-Venture | Purpose | Table Prefix | Namespace |
|--------|-------------|---------|--------------|-----------|
| **Crypto Investment** | Dragon Scale Crypto | Monitor prices, thresholds, income logging | `task`, `income_logger`, `historical_*` | `App\Http\Controllers\Crypto*` |
| **E-commerce** | Dragon Scale Store(s) | Products, Orders, Shipping, Discounts | `ecom_*` | `App\Http\Controllers\Ecommerce\*` |
| **Ani-Senso Courses** | Ani-Senso Academy | Educational content, memberships | `as_*` | `App\Http\Controllers\aniSensoAdmin\*` |
| **Access & Memberships** | Cross-Venture | Access tags, triggers, client management | `axis_tags`, `clients_*` | Various |
| **Affiliates** | Cross-Venture | Affiliate tracking (planned expansion) | `ecom_affiliates` | `App\Http\Controllers\Ecommerce\*` |
| **User Management** | Core DS AXIS | Admin user accounts | `users` | `App\Http\Controllers\UsersController` |

### 1.4 Growth & Evolution Principles

DS AXIS is designed to **continuously grow and evolve**. When developing:

1. **Modular Design**: Each sub-venture should be self-contained with clear boundaries
2. **Shared Services**: Use common patterns for cross-venture features (auth, access control)
3. **Scalable Patterns**: Follow established conventions to ensure consistency as system grows
4. **Multi-Tenancy Ready**: Design with multiple partners/businesses in mind
5. **API-First Consideration**: Build with future frontend integrations in mind

---

## 2. Directory Structure

```
ds-axis/  (btc-check folder - legacy name)
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                    # Authentication (Laravel UI)
│   │   │   ├── aniSensoAdmin/           # Course management
│   │   │   ├── Ecommerce/               # E-commerce module
│   │   │   │   ├── ProductsController.php
│   │   │   │   ├── OrdersController.php
│   │   │   │   ├── ShippingController.php
│   │   │   │   ├── DiscountsController.php
│   │   │   │   └── TriggersController.php
│   │   │   ├── Api/                     # API endpoints
│   │   │   ├── Crypto*Controller.php    # Crypto-related features
│   │   │   └── [Core controllers]
│   │   └── Middleware/
│   └── Models/
│       ├── BaseModel.php                # IMPORTANT: Extend this for new models
│       ├── Task.php                     # Crypto trading tasks
│       ├── IncomeLogger.php             # Income logging
│       ├── Ecom*.php                    # E-commerce models
│       ├── As*.php                      # Ani-Senso models
│       └── User.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── master.blade.php         # PRIMARY layout (sidebar)
│       │   ├── master-layouts.blade.php # Horizontal layout
│       │   ├── sidebar.blade.php        # Navigation menu
│       │   ├── topbar.blade.php         # Top navigation bar
│       │   ├── vendor-scripts.blade.php # Core JS includes
│       │   └── head-css.blade.php       # Core CSS includes
│       ├── components/
│       │   └── breadcrumb.blade.php     # Reusable breadcrumb
│       ├── ecommerce/                   # E-commerce views
│       │   ├── products/
│       │   │   ├── index.blade.php
│       │   │   ├── create.blade.php
│       │   │   ├── edit.blade.php
│       │   │   └── variants/
│       │   ├── orders/
│       │   ├── discounts/
│       │   └── shipping.blade.php
│       ├── aniSensoAdmin/               # Course views
│       ├── crypto-*.blade.php           # Crypto feature views
│       └── [template demo pages]        # Skote template examples
├── routes/
│   ├── web.php                          # All web routes
│   └── api.php                          # API routes
├── public/
│   └── build/
│       ├── libs/                        # Third-party libraries
│       ├── js/app.js                    # Main app JS
│       ├── css/                         # Compiled CSS
│       └── images/                      # Static images
└── database/
    └── migrations/                      # Laravel migrations
```

---

## 3. Model Architecture

### 3.1 BaseModel Pattern (RECOMMENDED)

**File**: `app/Models/BaseModel.php`

All new models SHOULD extend `BaseModel` for consistent timezone handling:

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BaseModel extends Model
{
    // Timestamps in Asia/Manila timezone
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // Automatic timezone conversion
    public function getCreatedAtAttribute($value) {
        return $value ? Carbon::parse($value)->timezone('Asia/Manila') : $value;
    }

    public function freshTimestamp() {
        return Carbon::now('Asia/Manila');
    }
}
```

### 3.2 Standard Model Template

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class NewFeature extends BaseModel
{
    use HasFactory;

    protected $table = 'table_name';

    protected $fillable = [
        'usersId',           // User ownership
        'fieldName',         // Business fields (camelCase)
        'delete_status',     // Soft delete status
    ];

    protected $casts = [
        'decimalField' => 'decimal:2',
        'cryptoValue' => 'decimal:8',
        'booleanField' => 'boolean',
        'dateField' => 'datetime',
    ];

    // REQUIRED: Active scope for soft deletes
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    // REQUIRED: User scoping for multi-tenant data
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    // Relationship to owner
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }
}
```

### 3.3 Soft Delete Conventions

**CRITICAL WARNING**: The codebase has INCONSISTENT soft delete patterns!

| Module | Column | Active Value | Deleted Value |
|--------|--------|--------------|---------------|
| **Crypto/Income** (Recommended) | `delete_status` | `'active'` | `'deleted'` |
| E-commerce | `deleteStatus` | `1` (true) | `0` (false) |
| Ani-Senso | `deleteStatus` | `true` | `false` |

**For NEW features**: Use the Crypto/Income pattern:
- Column: `delete_status` (enum)
- Values: `'active'` or `'deleted'`
- Scope: `->where('delete_status', 'active')` or `Model::active()`

---

## 4. Controller Patterns

### 4.1 Standard CRUD Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Feature;

class FeatureController extends Controller
{
    // LIST - Index with filters and pagination
    public function index(Request $request)
    {
        $userId = Auth::user()->id;
        $query = Feature::active()->forUser($userId);

        // Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        $data = $query->orderBy('created_at', 'desc')->paginate(100);
        return view('feature.index', compact('data'));
    }

    // CREATE - Show form
    public function create()
    {
        return view('feature.create');
    }

    // STORE - Save with validation
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fieldName' => 'required|string|max:255',
        ], [
            'fieldName.required' => 'Field name is required.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        Feature::create([
            'usersId' => Auth::user()->id,
            'fieldName' => $request->fieldName,
            'delete_status' => 'active',
        ]);

        return redirect()->route('feature.index')
            ->with('success', 'Created successfully!');
    }

    // DESTROY - Soft delete with ownership check
    public function destroy($id)
    {
        try {
            $userId = Auth::user()->id;
            $item = Feature::where('id', $id)
                ->where('usersId', $userId)
                ->where('delete_status', 'active')
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not found or unauthorized.'
                ], 404);
            }

            $item->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred.'
            ], 500);
        }
    }

    // AJAX DATA - For DataTables
    public function getData(Request $request)
    {
        $userId = Auth::user()->id;
        $data = Feature::active()
            ->forUser($userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
```

### 4.2 JSON Response Format

```php
// Success
return response()->json([
    'success' => true,
    'message' => 'Operation completed.',
    'data' => $result
]);

// Error
return response()->json([
    'success' => false,
    'message' => 'Error description.',
    'error' => $e->getMessage()
], 500);
```

---

## 5. View/Template Architecture

### 5.1 Master Layout Structure

**Primary Layout**: `resources/views/layouts/master.blade.php`

```blade
<!doctype html>
<html>
<head>
    @include('layouts.head-css')    <!-- Core CSS -->
    @yield('css')                    <!-- Page-specific CSS -->
</head>
<body data-sidebar="dark">
    <div id="layout-wrapper">
        @include('layouts.topbar')    <!-- Top navigation -->
        @include('layouts.sidebar')   <!-- Left sidebar menu -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')  <!-- Page content -->
                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
    @include('layouts.vendor-scripts') <!-- Core JS -->
    @yield('script')                   <!-- Page-specific JS -->
    @yield('script-bottom')            <!-- Bottom scripts -->
</body>
</html>
```

### 5.2 Standard Page Template

```blade
@extends('layouts.master')

@section('title') Page Title @endsection

@section('css')
<!-- DataTables CSS (if using) -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<!-- Toastr (if using) -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" />
@endsection

@section('content')

{{-- Breadcrumb --}}
@component('components.breadcrumb')
    @slot('li_1') Category @endslot
    @slot('title') Page Title @endslot
@endcomponent

{{-- Flash Messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Main Content Card --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Title</h4>
                    <a href="#" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New
                    </a>
                </div>

                <!-- Content here -->
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
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

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Page-specific JavaScript
});
</script>
@endsection
```

### 5.3 Form Patterns

```blade
<form method="POST" action="{{ route('feature.store') }}">
    @csrf

    <div class="mb-3">
        <label for="fieldName" class="form-label">
            Label <span class="text-danger">*</span>
        </label>
        <input type="text"
               class="form-control @error('fieldName') is-invalid @enderror"
               id="fieldName"
               name="fieldName"
               value="{{ old('fieldName') }}"
               required>
        <div class="invalid-feedback">
            @error('fieldName'){{ $message }}@enderror
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bx bx-save me-1"></i>Save
    </button>
</form>
```

---

## 6. JavaScript Patterns

### 6.1 Core Libraries Available

| Library | Path | Purpose |
|---------|------|---------|
| jQuery | `build/libs/jquery/jquery.min.js` | DOM manipulation, AJAX |
| Bootstrap 5 | `build/libs/bootstrap/js/bootstrap.bundle.min.js` | UI components |
| DataTables | `build/libs/datatables.net/js/jquery.dataTables.min.js` | Data tables |
| Select2 | `build/libs/select2/js/select2.min.js` | Enhanced selects |
| Toastr | `build/libs/toastr/build/toastr.min.js` | Notifications |
| SweetAlert2 | `build/libs/sweetalert2/sweetalert2.min.js` | Alert dialogs |
| TinyMCE | `build/libs/tinymce/tinymce.min.js` | Rich text editor |
| ApexCharts | `build/libs/apexcharts/apexcharts.min.js` | Charts |
| Moment.js | `build/libs/moment/moment.min.js` | Date handling |
| Dragula | `build/libs/dragula/dragula.min.js` | Drag & drop |
| Dropzone | `build/libs/dropzone/dropzone.min.js` | File uploads |

### 6.2 AJAX Pattern with CSRF

```javascript
$.ajax({
    url: '/api/endpoint/' + id,
    type: 'DELETE',  // Or POST, PUT, PATCH
    data: {
        _token: '{{ csrf_token() }}',
        field: value
    },
    success: function(response) {
        if (response.success) {
            toastr.success(response.message, 'Success!');
            location.reload();
        }
    },
    error: function(xhr) {
        let message = 'An error occurred.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        }
        toastr.error(message, 'Error!');
    }
});
```

### 6.3 Modal Delete Pattern

```javascript
let itemToDelete = null;

// Show modal
$('.delete-btn').on('click', function() {
    itemToDelete = {
        id: $(this).data('item-id'),
        name: $(this).data('item-name'),
        row: $(this).closest('tr')
    };
    $('#deleteItemName').text(itemToDelete.name);
    $('#deleteModal').modal('show');
});

// Confirm delete
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
            $btn.prop('disabled', false).html('<i class="bx bx-trash"></i> Delete');
            itemToDelete = null;
        }
    });
});
```

### 6.4 Toastr Configuration

```javascript
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
    extendedTimeOut: 1000,
    preventDuplicates: true
};
```

### 6.5 DataTables Initialization

```javascript
$('#dataTable').DataTable({
    processing: true,
    serverSide: false,  // Or true for server-side processing
    responsive: true,
    ajax: {
        url: "{{ route('feature.data') }}",
        dataSrc: 'data'
    },
    columns: [
        { data: 'fieldName', name: 'fieldName' },
        { data: 'createdAt', name: 'createdAt' },
        {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                return `
                    <button class="btn btn-sm btn-danger delete-btn"
                            data-item-id="${data}"
                            data-item-name="${row.fieldName}">
                        <i class="bx bx-trash"></i>
                    </button>
                `;
            }
        }
    ],
    order: [[1, 'desc']]
});
```

---

## 7. Route Conventions

### 7.1 Standard CRUD Routes

```php
// In routes/web.php

// List
Route::get('/features', [FeaturesController::class, 'index'])
    ->name('features')
    ->middleware('auth');

// Create form
Route::get('/features-add', [FeaturesController::class, 'create'])
    ->name('features.create')
    ->middleware('auth');

// Store
Route::post('/features-add', [FeaturesController::class, 'store'])
    ->name('features.store')
    ->middleware('auth');

// Edit form
Route::get('/features-edit', [FeaturesController::class, 'edit'])
    ->name('features.edit')
    ->middleware('auth');

// Update
Route::put('/features/{id}', [FeaturesController::class, 'update'])
    ->name('features.update')
    ->middleware('auth');

// Delete
Route::delete('/features/{id}', [FeaturesController::class, 'destroy'])
    ->name('features.destroy')
    ->middleware('auth');

// AJAX data endpoint
Route::get('/features/data', [FeaturesController::class, 'getData'])
    ->name('features.data')
    ->middleware('auth');
```

### 7.2 Naming Conventions

- List page: `features` (plural, no suffix)
- Create: `features.create` or `features-add`
- Store: `features.store`
- Edit: `features.edit` or `features-edit`
- Update: `features.update`
- Delete: `features.destroy`
- AJAX Data: `features.data`

---

## 8. Database Conventions

### 8.1 Naming Rules

| Element | Convention | Example |
|---------|------------|---------|
| Table names | snake_case, plural | `income_logger`, `ecom_products` |
| Column names | camelCase | `usersId`, `taskCoin`, `originalPhpValue` |
| Primary key | `id` | bigint unsigned, auto-increment |
| Foreign keys | `{model}Id` | `usersId`, `taskId`, `productId` |
| Soft delete | `delete_status` | enum('active', 'deleted') |
| Status flags | `isActive` | boolean or tinyint |
| Ordering | `{item}Order` | `chapterOrder`, `topicsOrder` |

### 8.2 Data Type Conventions

| Purpose | MySQL Type | Laravel Cast |
|---------|------------|--------------|
| Currency (PHP) | decimal(15,2) | `'decimal:2'` |
| Crypto values | decimal(20,8) | `'decimal:8'` |
| Dates | datetime | `'datetime'` |
| Booleans | tinyint(1) | `'boolean'` |
| Large text | text/longtext | (no cast needed) |

### 8.3 Standard Migration

```php
Schema::create('feature_name', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('usersId');
    $table->string('fieldName', 255);
    $table->decimal('priceValue', 15, 2)->default(0);
    $table->enum('delete_status', ['active', 'deleted'])->default('active');
    $table->timestamps();

    $table->foreign('usersId')->references('id')->on('users');
    $table->index(['usersId', 'delete_status']);
});
```

---

## 9. Legacy Areas and Patterns to AVOID

### 9.1 Inconsistent Patterns (Existing but NOT recommended for new code)

1. **E-commerce Models**: Extend `Model` instead of `BaseModel`
   - Missing timezone handling
   - Uses boolean `deleteStatus` instead of enum `delete_status`

2. **Ani-Senso Module**: Mixed patterns
   - Uses `deleteStatus = true/false`
   - No user scoping in some controllers
   - Some controllers lack proper ownership validation

3. **Test Routes in Production**: Exposed debugging routes
   - `/test/users`, `/test/password/{email}` (security risk)

### 9.2 Patterns to Always Use for New Code

1. **Always extend `BaseModel`** for timezone consistency
2. **Always use `delete_status` with enum values** (`'active'`, `'deleted'`)
3. **Always scope queries by `usersId`** for user-specific data
4. **Always check ownership** before update/delete operations
5. **Always use `Validator::make()`** with custom messages
6. **Always return JSON responses** for AJAX endpoints with `success` boolean

### 9.3 Template Pages (Skote Demo)

The following views are Skote template demos and should NOT be modified:
- `charts-*.blade.php`
- `form-*.blade.php`
- `icons-*.blade.php`
- `ui-*.blade.php`
- `tables-*.blade.php`
- Most auth pages except custom implementations

---

## 10. Module-Specific Reference

### 10.1 Crypto Module

**Tables**: `task`, `income_logger`, `historical_prices`, `historical_ladder`, `difference_history`, `notification_history`, `threshold_task`

**Key Models**:
- `Task`: Trading tasks with buy/sell thresholds
- `IncomeLogger`: Transaction income records
- `HistoricalPrice`: Price snapshots
- `DifferenceHistory`: Price difference analysis

**User Scoping**: All crypto data is scoped by `usersId`

### 10.2 E-commerce Module

**Tables**: `ecom_products`, `ecom_products_variants`, `ecom_orders`, `ecom_products_shipping`, `ecom_products_shipping_options`, `ecom_products_discounts`

**Key Models**:
- `EcomProduct`: Main products
- `EcomProductVariant`: Product variations with pricing
- `EcomOrder`: Customer orders
- `EcomProductsShipping`: Shipping methods

**Note**: E-commerce is multi-store, filtered by `productStore` column

### 10.3 Ani-Senso Module

**Tables**: `as_courses`, `as_course_chapters`, `as_topics`, `as_topic_resources`, `as_image_library`

**Key Models**:
- `AsCourse`: Course definitions
- `AsCourseChapter`: Course chapters with ordering
- `AsTopic`: Topics within chapters
- `AsTopicResource`: Resources attached to topics

**Ordering**: Uses `chapterOrder` and `topicsOrder` for drag-and-drop sorting

---

## 11. Quick Reference Checklist

When creating a new feature, verify:

- [ ] Model extends `BaseModel`
- [ ] Model has `delete_status` in `$fillable` with default `'active'`
- [ ] Model has `scopeActive()` and `scopeForUser()` methods
- [ ] Controller checks user ownership before modify/delete
- [ ] Controller returns JSON for AJAX with `success` boolean
- [ ] View extends `layouts.master`
- [ ] View includes breadcrumb component
- [ ] View uses Toastr for notifications
- [ ] JavaScript uses CSRF token in AJAX calls
- [ ] Routes use `->middleware('auth')`
- [ ] Migration uses camelCase columns, snake_case table name
- [ ] Soft delete uses `delete_status` enum column

---

## 12. Common CSS Classes (Skote/Bootstrap 5)

```css
/* Cards */
.card, .card-body, .card-title, .card-title-desc

/* Buttons */
.btn-primary, .btn-success, .btn-danger, .btn-secondary
.btn-outline-* variants
.btn-sm for small buttons

/* Tables */
.table, .table-bordered, .table-striped, .table-responsive
.table-light (for header)
.align-middle, .table-nowrap

/* Badges */
.badge.bg-success, .badge.bg-danger, .badge.bg-info, .badge.bg-warning

/* Modals */
.modal.fade, .modal-dialog.modal-dialog-centered, .modal-content
.modal-header, .modal-body, .modal-footer

/* Alerts */
.alert.alert-success.alert-dismissible.fade.show
.alert.alert-danger

/* Forms */
.form-control, .form-select, .form-label, .form-check
.is-invalid, .invalid-feedback (validation)

/* Icons (BoxIcons) */
.bx.bx-* (e.g., bx-plus, bx-edit, bx-trash, bx-check-circle)
```

---

## 13. Related Skills & Agent Harmony

### 13.1 Skill Ecosystem

This skill is part of the DS AXIS architect skill system:

| Skill | Purpose | When to Reference |
|-------|---------|-------------------|
| **codebase-architect** (this) | System structure, patterns | WHERE to create files, HOW to structure |
| **database-architect** | Database schema, relationships | WHAT data exists, data types |
| **logic-architect** | Business logic, data flows | HOW logic executes, API integrations |
| **ds-axis-agent-harmony** | Agent collaboration rules | Multi-agent development |
| **pact-skills-updater** | Keep skills synchronized | After changes |

### 13.2 Cross-References

- For database schemas → See `database-architect.md`
- For business logic → See `logic-architect.md`
- For agent workflows → See `ds-axis-agent-harmony.md`

---

*This document should be consulted before implementing any new feature to ensure consistency with the established DS AXIS codebase architecture. For multi-agent development, also reference `ds-axis-agent-harmony.md`.*
