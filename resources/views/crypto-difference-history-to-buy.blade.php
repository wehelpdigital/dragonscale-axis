@extends('layouts.master')
@section('title') @lang('translation.Crypto_Difference_History_To_Buy') @endsection
@section('css')
    <!-- Datepicker -->
    <link href="{{ URL::asset('build/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Apexcharts -->
    <link href="{{ URL::asset('build/libs/apexcharts/apexcharts.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Crypto Checker @endslot
        @slot('li_2') Difference History @endslot
        @slot('title') To Buy @endslot
    @endcomponent

    <!-- Page Description -->
    <div class="row">
        <div class="col-12">
            <p class="text-muted mb-4">
                This module displays the difference between your configured "to buy" price settings and the historical pricing data of the cryptocurrency.
                Monitor how your buy targets compare against actual market movements to optimize your purchase timing and identify profitable entry points.
            </p>
        </div>
    </div>

    <!-- Date Filter -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Date Range Filter</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('crypto-difference-history-to-buy') }}" class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('crypto-difference-history-to-buy') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Difference History Chart</h4>
                    <p class="card-title-desc">Cash difference over time for buy opportunities.</p>
                </div>
                <div class="card-body">
                    <div id="difference-chart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Difference History Data</h4>
                    <p class="card-title-desc">Detailed view of all buy difference history records.</p>
                </div>
                <div class="card-body">
                    @if(count($differenceHistoryWithMovement) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Current Cash Value</th>
                                        <th>Starting Coin Value</th>
                                        <th>Difference</th>
                                        <th>Movement</th>
                                    </tr>
                                </thead>
                                <tbody id="historical-data-tbody">
                                    <!-- Data will be loaded here via AJAX -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            <div id="pagination-container">
                                <!-- Pagination will be loaded here via AJAX -->
                            </div>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="avatar-md mx-auto">
                                <div class="avatar-title bg-light text-primary rounded-circle font-size-24">
                                    <i class="bx bx-info-circle"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h4>No Data Found</h4>
                                <p class="text-muted">No difference history records found for the selected date range.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('style')
<style>
/* Fix pagination arrow sizes */
.page-link:first-child,
.page-link:last-child {
    font-size: 18px;
    font-weight: bold;
    line-height: 1;
}

/* Custom pagination styling */
.pagination .page-item .page-link {
    border-radius: 4px;
    margin: 0 2px;
    transition: all 0.2s ease;
}

.pagination .page-item .page-link:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #495057;
}

.pagination .page-item.active .page-link:hover {
    background-color: #556ee6;
    border-color: #556ee6;
    color: white;
}
</style>
@endsection

@section('script')
    <!-- Apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>

    <script>
        $(document).ready(function() {

            // Initialize data loading
            loadHistoricalData(1);

            // Initialize Chart
            var chartData = @json($chartData);
            var chartDataCount = @json(count($chartData));

            console.log('Chart data:', chartData);
            console.log('Chart data count:', chartDataCount);
            console.log('Chart container exists:', $('#difference-chart').length > 0);
            console.log('ApexCharts loaded:', typeof ApexCharts !== 'undefined');

            if (chartDataCount > 0) {
                console.log('Creating main chart with data:', chartData);

                var options = {
                    series: [{
                        name: 'Cash Difference',
                        data: chartData
                    }],
                    chart: {
                        type: 'line',
                        height: 350,
                        toolbar: {
                            show: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    colors: ['#556ee6'],
                    xaxis: {
                        type: 'datetime'
                    },
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                return '₱' + val.toLocaleString();
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return '₱' + val.toLocaleString();
                            }
                        }
                    }
                };

                try {
                    var chart = new ApexCharts(document.querySelector("#difference-chart"), options);
                    chart.render();
                    console.log('Main chart rendered successfully');
                } catch (error) {
                    console.error('Error rendering main chart:', error);
                    $('#difference-chart').html('<div class="text-center p-4"><h5>Error loading chart</h5><p class="text-muted">' + error.message + '</p></div>');
                }
            } else {
                // Show message when no data
                $('#difference-chart').html('<div class="text-center p-4"><h5>No chart data available</h5><p class="text-muted">Select a date range with data to view the chart.</p></div>');
            }

            console.log('Difference History To Buy page loaded');
        });

        // Load historical data via AJAX
        function loadHistoricalData(page = 1) {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            // Show loading state
            document.getElementById('historical-data-tbody').innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';

            // Build query parameters
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            params.append('page', page);

            // Make AJAX request
            fetch(`{{ route('crypto-difference-history-to-buy.data') }}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderHistoricalData(data.data);
                    renderPagination(data.pagination);
                } else {
                    document.getElementById('historical-data-tbody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading data: ' + data.message + '</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('historical-data-tbody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
            });
        }

        // Render historical data in table
        function renderHistoricalData(data) {
            const tbody = document.getElementById('historical-data-tbody');

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No difference history records found for the selected date range.</td></tr>';
                return;
            }

            let html = '';
            data.forEach((record) => {
                const differenceClass = record.difference >= 0 ? 'text-success' : 'text-danger';
                const movementClass = record.movement >= 0 ? 'bg-success' : 'bg-danger';
                const movementSign = record.movement >= 0 ? '+' : '';

                html += `
                    <tr>
                        <td>${record.date}</td>
                        <td>${record.time}</td>
                        <td>₱${parseFloat(record.current_cash_value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                        <td>${parseFloat(record.starting_coin_value).toFixed(8)} BTC</td>
                        <td class="${differenceClass}">
                            ₱${parseFloat(record.difference).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </td>
                        <td>
                            ${record.movement !== null ? `<span class="badge ${movementClass}">${movementSign}${parseFloat(record.movement).toFixed(2)}%</span>` : '<span class="text-muted">-</span>'}
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // Render pagination controls
        function renderPagination(pagination) {
            const container = document.getElementById('pagination-container');

            if (pagination.last_page <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '<nav><ul class="pagination">';

            // Previous button
            if (pagination.has_previous_pages) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page - 1}">‹</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">‹</span></li>';
            }

            // Page numbers
            const startPage = Math.max(1, pagination.current_page - 2);
            const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

            for (let i = startPage; i <= endPage; i++) {
                if (i === pagination.current_page) {
                    html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                } else {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                }
            }

            // Next button
            if (pagination.has_more_pages) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${pagination.current_page + 1}">›</a></li>`;
            } else {
                html += '<li class="page-item disabled"><span class="page-link">›</span></li>';
            }

            html += '</ul></nav>';

            // Add page info
            html += `<div class="text-center mt-2"><small class="text-muted">Showing ${pagination.from} to ${pagination.to} of ${pagination.total} results</small></div>`;

            container.innerHTML = html;

            // Add click event listeners to pagination links
            container.querySelectorAll('.page-link[data-page]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    loadHistoricalData(parseInt(this.dataset.page));
                });
            });
        }

        // Add event listeners for filter changes
        document.getElementById('start_date').addEventListener('change', function() {
            loadHistoricalData(1);
        });

        document.getElementById('end_date').addEventListener('change', function() {
            loadHistoricalData(1);
        });
    </script>
@endsection
