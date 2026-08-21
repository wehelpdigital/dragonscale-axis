@extends('layouts.master')

@section('title') Leads @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.badge-style {
    border-radius: 20px !important;
    padding: 4px 12px !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    border-width: 1px !important;
    transition: all 0.2s ease !important;
    line-height: 1.2 !important;
}

.badge-style:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

/* Loading overlay, same treatment as the other server-side tables in this app. */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.9);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
}

.loading-spinner {
    text-align: center;
    background: #fff;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.loading-text {
    color: #495057;
    font-weight: 500;
    font-size: 0.9rem;
}

/* The contact column stacks three short lines; keep them tight and readable. */
.contact-cell {
    line-height: 1.5;
    white-space: normal;
    min-width: 220px;
}

.contact-cell i {
    width: 16px;
}

.detail-table th {
    width: 38%;
    background-color: #f8f9fa;
    font-weight: 600;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Lead Finder @endslot
@slot('title') Leads @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1">Leads</h4>
                        <p class="text-secondary mb-0">
                            <span class="text-dark fw-semibold">{{ number_format($totalLeads) }}</span>
                            business{{ $totalLeads == 1 ? '' : 'es' }} collected so far.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" id="enrichPendingBtn">
                            <i class="bx bx-search-alt me-1"></i>Enrich pending
                        </button>
                        <button type="button" class="btn btn-success" id="exportCsvBtn">
                            <i class="bx bx-download me-1"></i>Export CSV
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row g-2 mb-3">
                    <div class="col-lg-3 col-md-6">
                        <label for="filterSearch" class="form-label text-dark">Search</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="filterSearch" autocomplete="off"
                                   placeholder="Name, email, phone, address...">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="filterOutreachStatus" class="form-label text-dark">Outreach</label>
                        <select class="form-select" id="filterOutreachStatus">
                            <option value="">All statuses</option>
                            @foreach($outreachStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="filterEnrichmentStatus" class="form-label text-dark">Enrichment</label>
                        <select class="form-select" id="filterEnrichmentStatus">
                            <option value="">All statuses</option>
                            @foreach($enrichmentStatuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="filterCategory" class="form-label text-dark">Category</label>
                        <select class="form-select" id="filterCategory">
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="filterCity" class="form-label text-dark">City</label>
                        <select class="form-select" id="filterCity">
                            <option value="">All cities</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}">{{ $city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="filterProvince" class="form-label text-dark">Province</label>
                        <select class="form-select" id="filterProvince">
                            <option value="">All provinces</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province }}">{{ $province }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-1 col-md-6">
                        <label for="filterHasEmail" class="form-label text-dark">Email</label>
                        <select class="form-select" id="filterHasEmail">
                            <option value="">Any</option>
                            <option value="1">Found</option>
                            <option value="0">Missing</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="filterBatch" class="form-label text-dark">Scrape batch</label>
                        <select class="form-select" id="filterBatch">
                            <option value="">All batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch['batchId'] }}">{{ $batch['label'] }} &middot; {{ $batch['startedAt'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="filterDateFrom" class="form-label text-dark">Found from</label>
                        <input type="date" class="form-control" id="filterDateFrom">
                    </div>
                    <div class="col-lg-2 col-md-6">
                        <label for="filterDateTo" class="form-label text-dark">Found to</label>
                        <input type="date" class="form-control" id="filterDateTo">
                    </div>
                    <div class="col-lg-2 col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" id="resetFiltersBtn">
                            <i class="bx bx-eraser me-1"></i>Reset filters
                        </button>
                    </div>
                </div>

                <!-- Leads Table -->
                <div class="table-responsive position-relative">
                    <div id="table-loading" class="loading-overlay" style="display: none;">
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="loading-text mt-2">Loading leads...</div>
                        </div>
                    </div>

                    <table class="table align-middle nowrap w-100 table-bordered table-striped" id="leads-table">
                        <thead class="table-light">
                            <tr>
                                <th>Business</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Contact</th>
                                <th class="text-center">Enrichment</th>
                                <th class="text-center">Outreach</th>
                                <th>Found</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Rows arrive from the server-side feed -->
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Lead Details Modal -->
<div class="modal fade" id="leadDetailsModal" tabindex="-1" aria-labelledby="leadDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadDetailsModalLabel">
                    <i class="bx bx-info-circle text-info me-2"></i>Lead Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="leadDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-secondary mb-0">Loading details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Lead Modal -->
<div class="modal fade" id="leadEditModal" tabindex="-1" aria-labelledby="leadEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="leadEditModalLabel">
                    <i class="bx bx-edit text-success me-2"></i>Edit Lead
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="leadEditLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-secondary mb-0">Loading lead...</p>
                </div>

                <form id="leadEditForm" style="display: none;">
                    <input type="hidden" id="editLeadId">
                    {{-- update() rewrites these columns from the request on every save, so they ride
                         along untouched instead of being nulled out by an edit that never showed them. --}}
                    <input type="hidden" id="editCategory">
                    <input type="hidden" id="editAddress">
                    <input type="hidden" id="editFacebookUrl">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="editEmail" class="form-label text-dark">
                                Email address
                                <small class="text-secondary">- saving one here marks the lead enriched</small>
                            </label>
                            <input type="email" class="form-control" id="editEmail" maxlength="255"
                                   placeholder="owner@business.com">
                            <div class="form-text text-body-secondary">Leave blank if you have not found one yet.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="editBusinessName" class="form-label text-dark">
                                Business name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="editBusinessName" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="editPhone" class="form-label text-dark">Phone</label>
                            <input type="text" class="form-control" id="editPhone" maxlength="60">
                        </div>
                        <div class="col-md-6">
                            <label for="editWebsite" class="form-label text-dark">Website</label>
                            <input type="text" class="form-control" id="editWebsite" maxlength="500">
                        </div>
                        <div class="col-md-3">
                            <label for="editCity" class="form-label text-dark">City</label>
                            <input type="text" class="form-control" id="editCity" maxlength="190">
                        </div>
                        <div class="col-md-3">
                            <label for="editProvince" class="form-label text-dark">Province</label>
                            <input type="text" class="form-control" id="editProvince" maxlength="190">
                        </div>
                        <div class="col-md-6">
                            <label for="editOutreachStatus" class="form-label text-dark">Outreach status</label>
                            <select class="form-select" id="editOutreachStatus">
                                @foreach($outreachStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editEnrichmentStatus" class="form-label text-dark">Enrichment status</label>
                            <select class="form-select" id="editEnrichmentStatus">
                                @foreach($enrichmentStatuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="editNotes" class="form-label text-dark">Notes</label>
                            <textarea class="form-control" id="editNotes" rows="3" maxlength="5000"
                                      placeholder="Anything worth remembering before you write to them."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" id="saveLeadBtn">
                    <i class="bx bx-save me-1"></i>Save changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enrichment Results Modal -->
<div class="modal fade" id="enrichResultsModal" tabindex="-1" aria-labelledby="enrichResultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enrichResultsModalLabel">
                    <i class="bx bx-search-alt text-primary me-2"></i>Enrichment Results
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="enrichResultsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete <strong id="deleteItemName" class="text-dark"></strong>?</p>
                <p class="text-secondary mb-0">
                    The business stays out of future scrapes, so this cannot be undone from here.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Required datatable js -->
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    var CSRF_TOKEN = '{{ csrf_token() }}';
    var LEAD_SHOW_URL = '{{ route('outreach.leads.show') }}';
    var LEAD_UPDATE_URL = '{{ route('outreach.leads.update') }}';
    var LEAD_DELETE_URL = '{{ route('outreach.leads.destroy') }}';
    var LEAD_ENRICH_URL = '{{ route('outreach.leads.enrich') }}';

    /** Escape anything that came back from the server before it goes into HTML we build here. */
    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    /** The filter row as the controller expects to read it - one shape for table, export and enrich. */
    function collectFilters() {
        return {
            search_term: $('#filterSearch').val(),
            outreachStatus: $('#filterOutreachStatus').val(),
            enrichmentStatus: $('#filterEnrichmentStatus').val(),
            aiCategory: $('#filterCategory').val(),
            city: $('#filterCity').val(),
            province: $('#filterProvince').val(),
            hasEmail: $('#filterHasEmail').val(),
            batchId: $('#filterBatch').val(),
            dateFrom: $('#filterDateFrom').val(),
            dateTo: $('#filterDateTo').val()
        };
    }

    /** Drop the empty keys so an export URL carries only the filters actually in use. */
    function activeFilters() {
        var filters = collectFilters();
        var cleaned = {};

        $.each(filters, function(key, value) {
            if (value !== null && value !== undefined && String(value) !== '') {
                cleaned[key] = value;
            }
        });

        return cleaned;
    }

    /**
     * Phone / email / website in one cell. Each piece already arrives as markup carrying
     * its own text class, so this only supplies the icons and the stacking.
     */
    function contactCell(data, type, row) {
        if (type !== 'display') {
            return row.email || '';
        }

        var missing = '<span class="text-secondary">-</span>';

        return '<div class="contact-cell">' +
            '<div><i class="bx bx-envelope text-secondary me-1"></i>' + (row.email || missing) + '</div>' +
            '<div><i class="bx bx-phone text-secondary me-1"></i>' + (row.phone || missing) + '</div>' +
            '<div><i class="bx bx-globe text-secondary me-1"></i>' + (row.website || missing) + '</div>' +
            '</div>';
    }

    $('#table-loading').show();

    var table = $('#leads-table').DataTable({
        processing: false,
        serverSide: true,
        responsive: false,
        order: [[5, 'desc']],
        ajax: {
            url: "{{ route('outreach.leads.data') }}",
            type: 'GET',
            data: function(d) {
                $.extend(d, collectFilters());
            },
            beforeSend: function() {
                $('#table-loading').show();
            },
            complete: function() {
                $('#table-loading').hide();
            },
            error: function() {
                $('#table-loading').hide();
                toastr.error('Error loading leads. Please refresh the page.', 'Error!', { timeOut: 5000 });
            }
        },
        columns: [
            { data: 'businessName', name: 'businessName' },
            { data: 'displayCategory', name: 'aiCategory' },
            { data: 'location', name: 'location' },
            { data: 'email', name: 'email', render: contactCell, className: 'contact-cell' },
            { data: 'enrichmentStatus', name: 'enrichmentStatus', className: 'text-center' },
            { data: 'outreachStatus', name: 'outreachStatus', className: 'text-center' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            emptyTable: "No leads yet - run a scrape from Find Leads to fill this list.",
            zeroRecords: "No leads match these filters",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ leads",
            infoEmpty: "Showing 0 to 0 of 0 leads",
            infoFiltered: "(filtered from _MAX_ total leads)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        initComplete: function() {
            $('#table-loading').hide();
        }
    });

    // ==================== FILTERS ====================

    var searchTimer = null;

    $('#filterSearch').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            table.ajax.reload();
        }, 400);
    });

    $('#filterOutreachStatus, #filterEnrichmentStatus, #filterCategory, #filterCity, #filterProvince, #filterHasEmail, #filterBatch, #filterDateFrom, #filterDateTo')
        .on('change', function() {
            table.ajax.reload();
        });

    $('#resetFiltersBtn').on('click', function() {
        $('#filterSearch').val('');
        $('#filterOutreachStatus, #filterEnrichmentStatus, #filterCategory, #filterCity, #filterProvince, #filterHasEmail, #filterBatch').val('');
        $('#filterDateFrom, #filterDateTo').val('');
        table.ajax.reload();
    });

    // ==================== EXPORT ====================

    $('#exportCsvBtn').on('click', function() {
        var query = $.param(activeFilters());
        var url = "{{ route('outreach.leads.export') }}";

        // A plain navigation, not AJAX: the response is a streamed file download.
        window.location.href = query === '' ? url : url + '?' + query;
    });

    // ==================== BATCH ENRICHMENT ====================

    $('#enrichPendingBtn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Finding emails...');

        var payload = collectFilters();
        payload._token = CSRF_TOKEN;
        payload.limit = 10;

        $.ajax({
            url: "{{ route('outreach.leads.enrichBatch') }}",
            type: 'POST',
            data: payload,
            success: function(response) {
                if (!response.success) {
                    toastr.error(response.message || 'Enrichment could not be started.', 'Error!', { timeOut: 5000 });
                    return;
                }

                var data = response.data || {};

                if (!data.processed) {
                    toastr.info(response.message, 'Nothing to do');
                    return;
                }

                toastr.success(response.message, 'Done!');
                renderEnrichResults(data);
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while enriching leads.';
                toastr.error(message, 'Error!', { timeOut: 5000 });
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    /** Per-lead outcome of a batch run, so a failed lookup is visible and not just a counter. */
    function renderEnrichResults(data) {
        var results = data.results || [];

        var html = '<div class="row g-2 mb-3">' +
            '<div class="col-4"><div class="border rounded p-2 text-center">' +
            '<div class="h4 mb-0 text-dark">' + escapeHtml(data.processed || 0) + '</div>' +
            '<small class="text-secondary">Processed</small></div></div>' +
            '<div class="col-4"><div class="border rounded p-2 text-center">' +
            '<div class="h4 mb-0 text-success">' + escapeHtml(data.enriched || 0) + '</div>' +
            '<small class="text-secondary">Emails found</small></div></div>' +
            '<div class="col-4"><div class="border rounded p-2 text-center">' +
            '<div class="h4 mb-0 text-danger">' + escapeHtml(data.failed || 0) + '</div>' +
            '<small class="text-secondary">Nothing found</small></div></div>' +
            '</div>';

        if (!results.length) {
            html += '<div class="text-center py-3">' +
                '<i class="mdi mdi-folder-open text-secondary" style="font-size: 2.5rem;"></i>' +
                '<p class="text-dark mt-2 mb-1">No leads were processed.</p>' +
                '<small class="text-secondary">Every lead in this filter already has an email.</small>' +
                '</div>';
        } else {
            html += '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">' +
                '<thead class="table-light"><tr>' +
                '<th class="text-dark">Business</th><th class="text-dark">Result</th>' +
                '</tr></thead><tbody>';

            $.each(results, function(index, row) {
                var outcome = row.email
                    ? '<span class="text-dark">' + escapeHtml(row.email) + '</span>' +
                      ' <small class="text-secondary">via ' + escapeHtml(row.source || 'unknown') + '</small>'
                    : '<span class="text-secondary">' + escapeHtml(row.error || 'No email found') + '</span>';

                html += '<tr>' +
                    '<td class="text-dark">' + escapeHtml(row.businessName) + '</td>' +
                    '<td>' + outcome + '</td>' +
                    '</tr>';
            });

            html += '</tbody></table></div>';
        }

        if (data.remaining) {
            html += '<p class="text-secondary mt-3 mb-0">' +
                '<i class="bx bx-time me-1"></i>' + escapeHtml(data.remaining) +
                ' lead(s) were left for the next run - the inline budget ran out.</p>';
        }

        $('#enrichResultsContent').html(html);
        $('#enrichResultsModal').modal('show');
    }

    // ==================== DETAILS ====================

    $('#leads-table').on('click', '.lead-view-btn', function() {
        var leadId = $(this).data('lead-id');

        $('#leadDetailsModal').modal('show');
        $('#leadDetailsContent').html(
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' +
            '<p class="mt-2 text-secondary mb-0">Loading details...</p>' +
            '</div>'
        );

        $.ajax({
            url: LEAD_SHOW_URL + '?id=' + encodeURIComponent(leadId),
            type: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    $('#leadDetailsContent').html(buildDetailsHtml(response.data));
                    return;
                }

                $('#leadDetailsContent').html(
                    '<div class="alert alert-warning text-center mb-0">' +
                    '<i class="bx bx-error-circle me-2"></i>Unable to load this lead.</div>'
                );
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while loading the lead.';

                $('#leadDetailsContent').html(
                    '<div class="alert alert-danger text-center mb-0">' +
                    '<i class="bx bx-error-circle me-2"></i>' + escapeHtml(message) + '</div>'
                );
            }
        });
    });

    function detailRow(label, value) {
        return '<tr>' +
            '<th class="text-dark">' + escapeHtml(label) + '</th>' +
            '<td class="text-dark">' + (value === '' || value === null || value === undefined
                ? '<span class="text-secondary">-</span>'
                : value) + '</td>' +
            '</tr>';
    }

    function buildDetailsHtml(data) {
        var lead = data.lead || {};
        var logs = data.emailLogs || [];

        var website = lead.website
            ? '<a class="text-primary" href="' + escapeHtml(lead.website) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(lead.website) + '</a>'
            : '';

        var facebook = lead.facebookUrl
            ? '<a class="text-primary" href="' + escapeHtml(lead.facebookUrl) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(lead.facebookUrl) + '</a>'
            : '';

        var email = lead.email
            ? '<a class="text-primary" href="mailto:' + escapeHtml(lead.email) + '">' + escapeHtml(lead.email) + '</a>' +
              (lead.emailSource ? ' <small class="text-secondary">via ' + escapeHtml(lead.emailSource) + '</small>' : '')
            : '';

        var coordinates = (lead.latitude !== null && lead.longitude !== null)
            ? escapeHtml(lead.latitude + ', ' + lead.longitude)
            : '';

        var html = '<h5 class="text-dark mb-1">' + escapeHtml(lead.businessName) + '</h5>' +
            '<p class="mb-3">' + (lead.enrichmentStatusBadge || '') + ' ' + (lead.outreachStatusBadge || '') + '</p>' +
            '<div class="table-responsive"><table class="table table-sm table-bordered detail-table mb-0">';

        html += detailRow('Category', escapeHtml(lead.category || ''));
        html += detailRow('Email', email);
        html += detailRow('Phone', escapeHtml(lead.phone || ''));
        html += detailRow('Website', website);
        html += detailRow('Facebook', facebook);
        html += detailRow('Address', escapeHtml(lead.address || ''));
        html += detailRow('Location', escapeHtml(lead.location || ''));
        html += detailRow('Coordinates', coordinates);
        html += detailRow('Rating', escapeHtml(lead.ratingLabel || ''));
        html += detailRow('Enrichment attempts', escapeHtml(lead.enrichmentAttempts));
        html += detailRow('Last enrichment error', escapeHtml(lead.enrichmentError || ''));
        html += detailRow('Enriched at', escapeHtml(lead.enrichedAt || ''));
        html += detailRow('Contact attempts', escapeHtml(lead.contactAttempts));
        html += detailRow('Last contacted', escapeHtml(lead.lastContactedAt || ''));
        html += detailRow('Replied at', escapeHtml(lead.repliedAt || ''));
        html += detailRow('Inbox messages', escapeHtml(data.messageCount || 0));
        html += detailRow('Scrape batch', escapeHtml(lead.batchId || ''));
        html += detailRow('Found on', escapeHtml(lead.createdAt || ''));
        html += detailRow('Notes', lead.notes ? escapeHtml(lead.notes) : '');

        html += '</table></div>';

        html += '<h6 class="text-dark mt-4 mb-2">Recent sends</h6>';

        if (!logs.length) {
            html += '<div class="text-center py-3">' +
                '<i class="mdi mdi-email-outline text-secondary" style="font-size: 2.5rem;"></i>' +
                '<p class="text-dark mt-2 mb-1">Nothing has been sent to this lead yet.</p>' +
                '<small class="text-secondary">Sends appear here once the queue reaches them.</small>' +
                '</div>';

            return html;
        }

        html += '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">' +
            '<thead class="table-light"><tr>' +
            '<th class="text-dark">Subject</th>' +
            '<th class="text-dark">Status</th>' +
            '<th class="text-dark">Sent</th>' +
            '</tr></thead><tbody>';

        var logBadges = {
            queued: 'bg-info text-white',
            sent: 'bg-success',
            failed: 'bg-danger',
            bounced: 'bg-warning text-dark'
        };

        $.each(logs, function(index, log) {
            var badgeClass = logBadges[log.status] || 'bg-secondary';
            var subject = escapeHtml(log.subject);

            if (log.aiRephrased) {
                subject += ' <span class="badge bg-light text-dark">AI</span>';
            }

            if (log.error) {
                subject += '<br><small class="text-danger">' + escapeHtml(log.error) + '</small>';
            }

            html += '<tr>' +
                '<td class="text-dark">' + subject + '</td>' +
                '<td><span class="badge ' + badgeClass + '">' + escapeHtml(log.status) + '</span></td>' +
                '<td class="text-secondary">' + escapeHtml(log.sentAt || log.createdAt || '-') + '</td>' +
                '</tr>';
        });

        html += '</tbody></table></div>';

        return html;
    }

    // ==================== EDIT ====================

    $('#leads-table').on('click', '.lead-edit-btn', function() {
        var leadId = $(this).data('lead-id');

        $('#leadEditForm').hide();
        $('#leadEditLoading').show();
        $('#leadEditModal').modal('show');

        $.ajax({
            url: LEAD_SHOW_URL + '?id=' + encodeURIComponent(leadId),
            type: 'GET',
            success: function(response) {
                if (!response.success || !response.data) {
                    $('#leadEditModal').modal('hide');
                    toastr.error('Unable to load this lead.', 'Error!', { timeOut: 5000 });
                    return;
                }

                fillEditForm(response.data.lead || {});
                $('#leadEditLoading').hide();
                $('#leadEditForm').show();
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while loading the lead.';

                $('#leadEditModal').modal('hide');
                toastr.error(message, 'Error!', { timeOut: 5000 });
            }
        });
    });

    function fillEditForm(lead) {
        $('#editLeadId').val(lead.id || '');
        $('#editBusinessName').val(lead.businessName || '');
        $('#editEmail').val(lead.email || '');
        $('#editPhone').val(lead.phone || '');
        $('#editWebsite').val(lead.website || '');
        $('#editCity').val(lead.city || '');
        $('#editProvince').val(lead.province || '');
        $('#editNotes').val(lead.notes || '');
        $('#editOutreachStatus').val(lead.outreachStatus || 'uncontacted');
        $('#editEnrichmentStatus').val(lead.enrichmentStatus || 'pending');

        // Carried, not shown - see the note on the hidden inputs above.
        $('#editCategory').val(lead.category || '');
        $('#editAddress').val(lead.address || '');
        $('#editFacebookUrl').val(lead.facebookUrl || '');
    }

    $('#saveLeadBtn').on('click', function() {
        var leadId = $('#editLeadId').val();

        if (!leadId) {
            return;
        }

        var $btn = $(this);
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: LEAD_UPDATE_URL,
            type: 'POST',
            data: {
                _token: CSRF_TOKEN,
                id: leadId,
                businessName: $('#editBusinessName').val(),
                email: $('#editEmail').val(),
                phone: $('#editPhone').val(),
                website: $('#editWebsite').val(),
                city: $('#editCity').val(),
                province: $('#editProvince').val(),
                notes: $('#editNotes').val(),
                outreachStatus: $('#editOutreachStatus').val(),
                enrichmentStatus: $('#editEnrichmentStatus').val(),
                category: $('#editCategory').val(),
                address: $('#editAddress').val(),
                facebookUrl: $('#editFacebookUrl').val()
            },
            success: function(response) {
                if (!response.success) {
                    toastr.error(response.message || 'The lead could not be saved.', 'Error!', { timeOut: 5000 });
                    return;
                }

                $('#leadEditModal').modal('hide');
                toastr.success(response.message, 'Success!');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while saving the lead.';
                toastr.error(message, 'Error!', { timeOut: 5000 });
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ==================== SINGLE-LEAD ENRICH ====================

    $('#leads-table').on('click', '.lead-enrich-btn', function() {
        var $btn = $(this);
        var leadId = $btn.data('lead-id');
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: LEAD_ENRICH_URL,
            type: 'POST',
            data: { _token: CSRF_TOKEN, id: leadId },
            success: function(response) {
                if (!response.success) {
                    toastr.error(response.message || 'Enrichment failed.', 'Error!', { timeOut: 5000 });
                    return;
                }

                if (response.data && response.data.found) {
                    toastr.success(response.message, 'Email found!');
                } else {
                    toastr.warning(response.message, 'No email');
                }

                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while enriching the lead.';
                toastr.error(message, 'Error!', { timeOut: 5000 });
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ==================== DELETE ====================

    var leadToDelete = null;

    $('#leads-table').on('click', '.lead-delete-btn', function() {
        leadToDelete = {
            id: $(this).data('lead-id'),
            name: $(this).data('lead-name'),
            row: $(this).closest('tr')
        };

        $('#deleteItemName').text(leadToDelete.name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!leadToDelete) {
            return;
        }

        var $btn = $(this);
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: LEAD_DELETE_URL,
            type: 'POST',
            data: { _token: CSRF_TOKEN, id: leadToDelete.id },
            success: function(response) {
                if (!response.success) {
                    toastr.error(response.message || 'The lead could not be deleted.', 'Error!', { timeOut: 5000 });
                    return;
                }

                $('#deleteModal').modal('hide');
                toastr.success(response.message, 'Success!');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while deleting the lead.';
                toastr.error(message, 'Error!', { timeOut: 5000 });
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
                leadToDelete = null;
            }
        });
    });

});
</script>
@endsection
