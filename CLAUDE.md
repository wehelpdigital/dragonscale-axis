# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BTC-Check is a Laravel 12 cryptocurrency tracking and e-commerce admin dashboard built on the **Skote Admin Template** (Bootstrap 5). It runs on XAMPP with MySQL and manages:
- Crypto trading tasks with buy/sell thresholds
- Income logging and transaction history
- Price history and difference analysis
- E-commerce products, orders, shipping, discounts
- Course management (Ani-Senso module)

## Development Commands

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

## Architecture Patterns

### Models

**All models extend `App\Models\BaseModel`** which provides:
- Asia/Manila timezone for all timestamps
- Consistent datetime serialization (`Y-m-d H:i:s`)
- Fresh timestamps in Philippines timezone

**Standard Model Structure:**
```php
class ModelName extends BaseModel {
    protected $table = 'table_name';
    protected $fillable = ['field1', 'field2'];
    protected $casts = [
        'decimalField' => 'decimal:2',
        'cryptoValue' => 'decimal:8',
        'boolField' => 'boolean',
    ];
}
```

**Soft Delete Convention** - Uses `delete_status` column (NOT Laravel's SoftDeletes):
- Values: `'active'` or `'deleted'`
- Always filter: `->where('delete_status', 'active')`
- Use scope: `Model::active()->get()`

**User Scoping** - All user-specific data uses `usersId` foreign key:
```php
$data = Model::where('usersId', Auth::user()->id)->get();
```

**Common Scopes:**
```php
public function scopeActive($query) {
    return $query->where('delete_status', 'active');
}
public function scopeForUser($query, $userId) {
    return $query->where('usersId', $userId);
}
```

**Relationships Pattern:**
```php
// Owner relationship
public function user() {
    return $this->belongsTo(User::class, 'usersId');
}
// Child relationship
public function items() {
    return $this->hasMany(Item::class, 'parentId', 'id');
}
```

### Controllers

**CRUD Pattern:**
```php
// Index - List with filters
public function index(Request $request) {
    $query = Model::active()->where('usersId', Auth::user()->id);

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
        return response()->json(['errors' => $validator->errors()], 422);
    }

    Model::create([...]);
    return redirect()->route('index')->with('success', 'Created successfully');
}

// Destroy - Soft delete with ownership check
public function destroy($id) {
    $item = Model::where('id', $id)
        ->where('usersId', Auth::user()->id)
        ->first();

    if (!$item) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $item->update(['delete_status' => 'deleted']);
    return response()->json(['success' => true]);
}
```

**JSON Response Format:**
```php
// Success
return response()->json(['success' => true, 'message' => 'Done', 'data' => $result]);

// Error
return response()->json(['success' => false, 'message' => 'Error', 'error' => $e->getMessage()], 500);
```

**DataTables AJAX Endpoint:**
```php
public function getData(Request $request) {
    $data = Model::active()->where('usersId', Auth::user()->id)->get();

    return response()->json([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $paginated->currentPage(),
            'total' => $paginated->total(),
        ]
    ]);
}
```

---

## View/Template Patterns

### Layout Structure

**Master Layout:** `layouts/master.blade.php` (sidebar) or `layouts/master-layouts.blade.php` (horizontal)

**View Extension Pattern:**
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

    <!-- Page content -->
@endsection

@section('script')
    <!-- Page-specific JavaScript -->
@endsection
```

### Common UI Patterns

**Card Container:**
```blade
<div class="card">
    <div class="card-body">
        <h4 class="card-title">Title</h4>
        <p class="card-title-desc">Description</p>
        <!-- Content -->
    </div>
</div>
```

**Session Flash Messages:**
```blade
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
```

**Form Pattern:**
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
    <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save</button>
</form>
```

**Delete Confirmation Modal:**
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

### DataTables Implementation

**CSS Includes:**
```blade
@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection
```

**HTML Table:**
```blade
<table class="table align-middle table-nowrap dt-responsive w-100" id="data-table">
    <thead class="table-light">
        <tr>
            <th>Column</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
```

**JavaScript Initialization:**
```javascript
@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('#data-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('data.endpoint') }}",
        columns: [
            { data: 'column', name: 'column' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });
});
</script>
@endsection
```

---

## JavaScript Patterns

### AJAX with CSRF Token
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

### Modal Handling
```javascript
let itemToDelete = null;

$('.delete-btn').on('click', function() {
    itemToDelete = {
        id: $(this).data('item-id'),
        name: $(this).data('item-name')
    };
    $('#deleteItemName').text(itemToDelete.name);
    $('#deleteModal').modal('show');
});

$('#confirmDelete').on('click', function() {
    $.ajax({
        url: '/api/delete/' + itemToDelete.id,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
            $('#deleteModal').modal('hide');
            toastr.success('Deleted successfully');
            location.reload();
        }
    });
});
```

### Form Validation
```javascript
$('#myForm').on('submit', function(e) {
    e.preventDefault();
    $('.is-invalid').removeClass('is-invalid');

    let isValid = true;

    if (!$('#fieldName').val().trim()) {
        $('#fieldName').addClass('is-invalid');
        isValid = false;
    }

    if (isValid) {
        this.submit();
    }
});
```

### Toastr Notifications
```javascript
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000
};

toastr.success('Message', 'Title');
toastr.error('Message', 'Title');
```

---

## Database Conventions

| Convention | Format | Example |
|------------|--------|---------|
| Table names | snake_case | `income_logger`, `ecom_products` |
| Column names | camelCase | `usersId`, `taskCoin`, `originalPhpValue` |
| Primary key | `id` | bigint unsigned |
| Foreign keys | `{model}Id` | `usersId`, `taskId`, `productId` |
| Soft delete | `delete_status` | enum('active', 'deleted') |
| Ordering | `{item}Order` | `chapterOrder`, `topicsOrder` |
| Currency (PHP) | decimal(15,2) | `originalPhpValue` |
| Crypto values | decimal(20,8) | `currentCoinValue` |

---

## Route Patterns

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

## File Structure

```
app/
├── Http/Controllers/
│   ├── Crypto*Controller.php      # Crypto tracking features
│   ├── Ecommerce/                  # E-commerce module
│   │   ├── ProductsController.php
│   │   ├── OrdersController.php
│   │   └── ShippingController.php
│   └── aniSensoAdmin/              # Course management
├── Models/
│   ├── BaseModel.php               # All models extend this
│   ├── Task.php                    # Crypto trading tasks
│   ├── IncomeLogger.php            # Income records
│   └── Ecom*.php                   # E-commerce models
resources/views/
├── layouts/
│   ├── master.blade.php            # Sidebar layout
│   ├── master-layouts.blade.php    # Horizontal layout
│   └── vendor-scripts.blade.php    # JS includes
├── components/
│   └── breadcrumb.blade.php        # Reusable breadcrumb
├── crypto-*.blade.php              # Crypto feature views
└── ecommerce/                      # E-commerce views
```

---

## Key Libraries

- **Backend:** Laravel 12, Laravel Sanctum, Yajra DataTables
- **Frontend:** Bootstrap 5 (Skote theme), jQuery, DataTables, Select2, Toastr, SweetAlert2
- **Build:** Vite with laravel-vite-plugin

## Testing

- PHPUnit and Pest configured
- Test suites: `Unit` and `Feature` in `tests/`
- Uses array drivers for cache, mail, queue, session in testing
