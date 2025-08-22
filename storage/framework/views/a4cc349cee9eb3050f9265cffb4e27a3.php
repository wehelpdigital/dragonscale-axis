<?php $__env->startSection('title'); ?> <?php echo app('translator')->get('translation.Crypto_Difference_History_To_Sell'); ?> <?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
    <!-- Datepicker -->
    <link href="<?php echo e(URL::asset('build/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css')); ?>" rel="stylesheet" type="text/css" />
    <!-- Apexcharts -->
    <link href="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?> Crypto Checker <?php $__env->endSlot(); ?>
        <?php $__env->slot('li_2'); ?> Difference History <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?> To Sell <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <!-- Page Description -->
    <div class="row">
        <div class="col-12">
            <p class="text-muted mb-4">
                This module displays the difference between your configured "to sell" price settings and the historical pricing data of the cryptocurrency.
                Track how your sell targets perform against actual market trends to maximize your profit potential and make informed decisions about exit strategies.
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
                    <form method="GET" action="<?php echo e(route('crypto-difference-history-to-sell')); ?>" class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo e($startDate); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo e($endDate); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="<?php echo e(route('crypto-difference-history-to-sell')); ?>" class="btn btn-secondary">Reset</a>
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
                    <p class="card-title-desc">Cash difference over time for sell opportunities.</p>
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
                    <p class="card-title-desc">Detailed view of all sell difference history records.</p>
                </div>
                <div class="card-body">
                    <?php if(count($differenceHistoryWithMovement) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Current Coin Value</th>
                                        <th>Starting PHP Value</th>
                                        <th>Difference</th>
                                        <th>Movement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $differenceHistoryWithMovement; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($record['date']); ?></td>
                                            <td><?php echo e($record['time']); ?></td>
                                            <td><?php echo e(number_format($record['current_cash_value'], 8)); ?> BTC</td>
                                            <td>₱<?php echo e(number_format($record['starting_coin_value'], 2)); ?></td>
                                            <td class="<?php echo e($record['difference'] >= 0 ? 'text-success' : 'text-danger'); ?>">
                                                ₱<?php echo e(number_format($record['difference'], 2)); ?>

                                            </td>
                                            <td>
                                                <?php if($record['movement'] !== null): ?>
                                                    <span class="badge <?php echo e($record['movement'] >= 0 ? 'bg-success' : 'bg-danger'); ?>">
                                                        <?php echo e($record['movement'] >= 0 ? '+' : ''); ?><?php echo e(number_format($record['movement'], 2)); ?>%
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <p class="text-muted">
                                    Showing <?php echo e($differenceHistory->firstItem()); ?> to <?php echo e($differenceHistory->lastItem()); ?> of <?php echo e($differenceHistory->total()); ?> results
                                </p>
                            </div>
                            <div>
                                <?php echo e($differenceHistory->links()); ?>

                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <div class="avatar-md mx-auto">
                                <div class="avatar-title bg-light text-danger rounded-circle font-size-24">
                                    <i class="bx bx-info-circle"></i>
                                </div>
                            </div>
                            <div class="mt-4">
                                <h4>No Data Found</h4>
                                <p class="text-muted">No difference history records found for the selected date range.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <!-- Apexcharts -->
    <script src="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.min.js')); ?>"></script>

    <script>
        $(document).ready(function() {

            // Initialize Chart
            var chartData = <?php echo json_encode($chartData, 15, 512) ?>;
            var chartDataCount = <?php echo json_encode(count($chartData), 15, 512) ?>;

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
                    colors: ['#f46a6a'],
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

            console.log('Difference History To Sell page loaded');
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/crypto-difference-history-to-sell.blade.php ENDPATH**/ ?>