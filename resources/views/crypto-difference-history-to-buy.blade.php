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
                                <tbody>
                                    @foreach($differenceHistoryWithMovement as $record)
                                        <tr>
                                            <td>{{ $record['date'] }}</td>
                                            <td>{{ $record['time'] }}</td>
                                            <td>₱{{ number_format($record['current_cash_value'], 2) }}</td>
                                            <td>{{ number_format($record['starting_coin_value'], 8) }} BTC</td>
                                            <td class="{{ $record['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                ₱{{ number_format($record['difference'], 2) }}
                                            </td>
                                            <td>
                                                @if($record['movement'] !== null)
                                                    <span class="badge {{ $record['movement'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $record['movement'] >= 0 ? '+' : '' }}{{ number_format($record['movement'], 2) }}%
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <p class="text-muted">
                                    Showing {{ $differenceHistory->firstItem() }} to {{ $differenceHistory->lastItem() }} of {{ $differenceHistory->total() }} results
                                </p>
                            </div>
                            <div>
                                {{ $differenceHistory->links() }}
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

@section('script')
    <!-- Apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>

    <script>
        $(document).ready(function() {

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
    </script>
@endsection
