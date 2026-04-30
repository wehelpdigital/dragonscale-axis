@extends('layouts.master')

@section('title') Client Subscriptions @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
    .client-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        color: #fff;
        flex-shrink: 0;
    }
    .search-box {
        position: relative;
        min-width: 250px;
    }
    .search-box .form-control {
        padding-left: 38px;
        height: 38px;
    }
    .search-box .bx-search {
        position: absolute;
        left: 13px;
        top: 0;
        height: 38px;
        display: flex;
        align-items: center;
        color: #74788d;
        font-size: 16px;
        z-index: 4;
    }
    .sub-row {
        transition: background-color 0.15s ease;
        vertical-align: top;
    }
    .sub-row:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.04);
    }
    .sub-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        margin: 2px 2px 2px 0;
        line-height: 1.3;
        max-width: 100%;
    }
    .sub-badge .badge-name {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
    }
    .sub-badge-active {
        background-color: rgba(52, 195, 143, 0.15);
        color: #0d7a4d;
        border: 1px solid rgba(52, 195, 143, 0.3);
    }
    .sub-badge-lifetime {
        background-color: rgba(85, 110, 230, 0.15);
        color: #3751b3;
        border: 1px solid rgba(85, 110, 230, 0.3);
    }
    .sub-badge-expiring {
        background-color: rgba(241, 180, 76, 0.2);
        color: #916a16;
        border: 1px solid rgba(241, 180, 76, 0.4);
    }
    .sub-badge-expired {
        background-color: rgba(244, 106, 106, 0.15);
        color: #a83232;
        border: 1px solid rgba(244, 106, 106, 0.3);
    }
    .sub-badge-inactive {
        background-color: rgba(116, 120, 141, 0.15);
        color: #4a4e5e;
        border: 1px solid rgba(116, 120, 141, 0.3);
    }
    .sub-badge-product {
        background-color: rgba(80, 165, 241, 0.15);
        color: #1f6ca3;
        border: 1px solid rgba(80, 165, 241, 0.3);
    }
    .sub-cell {
        min-width: 240px;
        max-width: 360px;
    }
    .empty-dash {
        color: #adb5bd;
    }
    .pagination-info {
        font-size: 13px;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
    .table-container {
        position: relative;
        min-height: 200px;
    }
    .filter-pill {
        font-size: 12px;
    }
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') E-commerce @endslot
        @slot('title') Client Subscriptions @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1">Subscriptions & Products per Client</h4>
                            <p class="text-secondary small mb-0">Course enrollments and products each client owns, with expiration details for active subscriptions.</p>
                        </div>
                        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                            <select class="form-select form-select-sm" id="filterSelect" style="width: auto;">
                                <option value="all">All clients</option>
                                <option value="has_any">Only with subscriptions/products</option>
                                <option value="has_active">Only with active subscription</option>
                                <option value="has_expiring">Only with expiring soon (&le; 7 days)</option>
                                <option value="has_expired">Only with expired subscription</option>
                            </select>

                            <select class="form-select form-select-sm" id="perPageSelect" style="width: auto;">
                                <option value="15">15 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>

                            <div class="search-box">
                                <i class="bx bx-search"></i>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search clients...">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3 d-flex flex-wrap gap-3 align-items-center">
                        <small class="text-secondary"><strong class="text-dark">Legend:</strong></small>
                        <span class="sub-badge sub-badge-active filter-pill"><i class="bx bx-check-circle"></i> Active</span>
                        <span class="sub-badge sub-badge-lifetime filter-pill"><i class="bx bx-infinite"></i> Lifetime</span>
                        <span class="sub-badge sub-badge-expiring filter-pill"><i class="bx bx-time-five"></i> Expiring soon</span>
                        <span class="sub-badge sub-badge-expired filter-pill"><i class="bx bx-x-circle"></i> Expired</span>
                        <span class="sub-badge sub-badge-inactive filter-pill"><i class="bx bx-minus-circle"></i> Inactive</span>
                        <span class="sub-badge sub-badge-product filter-pill"><i class="bx bx-package"></i> Product</span>
                    </div>

                    <div class="table-container">
                        <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="subscriptionsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;"></th>
                                        <th>Client</th>
                                        <th class="sub-cell">Courses Enrolled</th>
                                        <th class="sub-cell">Products Purchased</th>
                                        <th style="width: 140px;">Last Activity</th>
                                    </tr>
                                </thead>
                                <tbody id="subscriptionsTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                        <div class="pagination-info text-secondary" id="paginationInfo"></div>
                        <nav aria-label="Subscriptions pagination">
                            <ul class="pagination pagination-sm mb-0" id="paginationContainer"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    let currentPage = 1;
    let perPage = 15;
    let currentSearch = '';
    let currentFilter = 'all';
    let searchTimeout = null;

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    loadData();

    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const search = $(this).val();
        searchTimeout = setTimeout(function() {
            currentSearch = search;
            currentPage = 1;
            loadData();
        }, 300);
    });

    $('#perPageSelect').on('change', function() {
        perPage = parseInt($(this).val());
        currentPage = 1;
        loadData();
    });

    $('#filterSelect').on('change', function() {
        currentFilter = $(this).val();
        currentPage = 1;
        loadData();
    });

    function loadData() {
        showLoading(true);

        $.ajax({
            url: '{{ route("ecom-client-subscriptions.data") }}',
            type: 'GET',
            data: {
                search: currentSearch,
                page: currentPage,
                per_page: perPage
            },
            success: function(response) {
                if (response.success) {
                    const filtered = applyClientFilter(response.data);
                    renderRows(filtered);
                    renderPagination(response.pagination);
                    updatePaginationInfo(response.pagination);
                } else {
                    toastr.error(response.message || 'Failed to load data', 'Error!');
                }
            },
            error: function() {
                toastr.error('Failed to load client subscriptions', 'Error!');
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    function applyClientFilter(rows) {
        if (currentFilter === 'all') return rows;

        return rows.filter(function(r) {
            if (currentFilter === 'has_any') {
                return r.hasAny;
            }
            if (currentFilter === 'has_active') {
                return r.courses.some(c => c.status === 'active' || c.status === 'lifetime');
            }
            if (currentFilter === 'has_expiring') {
                return r.courses.some(c => c.status === 'expiring_soon');
            }
            if (currentFilter === 'has_expired') {
                return r.courses.some(c => c.status === 'expired');
            }
            return true;
        });
    }

    function renderRows(rows) {
        const $tbody = $('#subscriptionsTableBody');
        $tbody.empty();

        if (!rows.length) {
            $tbody.html(`
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="bx bx-user-x text-secondary" style="font-size: 2.5rem;"></i>
                        <p class="text-dark mt-2 mb-0">No clients match the current filter.</p>
                        <small class="text-secondary">Try a different search or filter.</small>
                    </td>
                </tr>
            `);
            return;
        }

        rows.forEach(function(row) {
            const coursesHtml = row.courses.length
                ? row.courses.map(renderCourseBadge).join('')
                : '<span class="empty-dash">—</span>';

            const productsHtml = row.products.length
                ? row.products.map(renderProductBadge).join('')
                : '<span class="empty-dash">—</span>';

            const lastActivity = row.lastActivity
                ? `<span class="text-dark">${escapeHtml(row.lastActivity)}</span>`
                : '<span class="empty-dash">—</span>';

            $tbody.append(`
                <tr class="sub-row">
                    <td>
                        <div class="client-avatar" style="background-color: ${row.avatarColor};">
                            ${escapeHtml(row.initials || '?')}
                        </div>
                    </td>
                    <td>
                        <strong class="text-dark d-block">${escapeHtml(row.fullName || 'Unknown')}</strong>
                        ${row.email ? `<small class="text-secondary d-block">${escapeHtml(row.email)}</small>` : ''}
                        ${row.phone ? `<small class="text-secondary d-block">${escapeHtml(row.phone)}</small>` : ''}
                    </td>
                    <td class="sub-cell">${coursesHtml}</td>
                    <td class="sub-cell">${productsHtml}</td>
                    <td>${lastActivity}</td>
                </tr>
            `);
        });
    }

    function renderCourseBadge(course) {
        const iconMap = {
            'active': 'bx-check-circle',
            'lifetime': 'bx-infinite',
            'expiring_soon': 'bx-time-five',
            'expired': 'bx-x-circle',
            'inactive': 'bx-minus-circle'
        };
        const classMap = {
            'active': 'sub-badge-active',
            'lifetime': 'sub-badge-lifetime',
            'expiring_soon': 'sub-badge-expiring',
            'expired': 'sub-badge-expired',
            'inactive': 'sub-badge-inactive'
        };
        const icon = iconMap[course.status] || 'bx-book-open';
        const cls = classMap[course.status] || 'sub-badge-inactive';
        const tooltip = escapeHtml(course.formattedExpiration || '');

        let expiryInline = '';
        if (course.status === 'lifetime') {
            expiryInline = ' · Lifetime';
        } else if (course.status === 'active' && course.daysRemaining !== null) {
            expiryInline = ` · ${course.daysRemaining}d left`;
        } else if (course.status === 'expiring_soon' && course.daysRemaining !== null) {
            expiryInline = course.daysRemaining === 1
                ? ' · 1 day left'
                : ` · ${course.daysRemaining}d left`;
        } else if (course.status === 'expired') {
            expiryInline = ' · Expired';
        } else if (course.status === 'inactive') {
            expiryInline = ' · Inactive';
        }

        return `<span class="sub-badge ${cls}" title="${tooltip}">
            <i class="bx ${icon}"></i>
            <span class="badge-name">${escapeHtml(course.name)}</span>
            <small class="text-nowrap">${expiryInline}</small>
        </span>`;
    }

    function renderProductBadge(product) {
        const qty = product.quantity > 1 ? ` ×${product.quantity}` : '';
        const tooltip = `Last purchased ${escapeHtml(product.lastPurchased)}`;
        return `<span class="sub-badge sub-badge-product" title="${tooltip}">
            <i class="bx bx-package"></i>
            <span class="badge-name">${escapeHtml(product.name)}</span>
            <small>${qty}</small>
        </span>`;
    }

    function renderPagination(pagination) {
        const $container = $('#paginationContainer');
        $container.empty();
        if (pagination.last_page <= 1) return;

        $container.append(`
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}"><i class="bx bx-chevron-left"></i></a>
            </li>
        `);

        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

        if (startPage > 1) {
            $container.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
            if (startPage > 2) {
                $container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            $container.append(`
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        if (endPage < pagination.last_page) {
            if (endPage < pagination.last_page - 1) {
                $container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
            $container.append(`<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`);
        }

        $container.append(`
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}"><i class="bx bx-chevron-right"></i></a>
            </li>
        `);
    }

    function updatePaginationInfo(pagination) {
        const $info = $('#paginationInfo');
        if (pagination.total === 0) {
            $info.html('No clients found');
        } else {
            $info.html(`Showing <strong class="text-dark">${pagination.from}</strong> to <strong class="text-dark">${pagination.to}</strong> of <strong class="text-dark">${pagination.total}</strong> client(s)`);
        }
    }

    $(document).on('click', '#paginationContainer .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        const $parent = $(this).parent();
        if (page && !$parent.hasClass('disabled') && !$parent.hasClass('active')) {
            currentPage = page;
            loadData();
        }
    });

    function showLoading(show) {
        $('#loadingOverlay').toggle(show);
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
});
</script>
@endsection
