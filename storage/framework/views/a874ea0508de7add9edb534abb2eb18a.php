<?php $__env->startSection('title'); ?> <?php echo app('translator')->get('translation.Crypto_Difference_Analysis'); ?> <?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
    <!-- Apexcharts -->
    <link href="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.css')); ?>" rel="stylesheet" type="text/css" />
    <!-- Toastr -->
    <link rel="stylesheet" type="text/css" href="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.css')); ?>">
    <style>
        .task-fields {
            display: none;
        }
    </style>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?> Crypto Checker <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?> Crypto Difference Analysis <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <!-- Page Description -->
    <div class="row">
        <div class="col-12">
            <p class="text-muted mb-4">
                This module is designed to analyze and display the difference between your configured task type values (buy/sell settings)
                and the historical cryptocurrency values. Compare your trading strategies against actual market performance to identify
                optimal entry and exit points for maximizing your investment returns.
            </p>
        </div>
    </div>

    <!-- Analysis Form Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border">
                <div class="card-body">
                    <h5 class="card-title mb-3">Analysis Parameters</h5>
                    <form id="cryptoAnalysisForm" novalidate>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="coinType" class="form-label">Coin Type</label>
                                    <select class="form-select" id="coinType" name="coinType">
                                        <option value="">Select Coin Type</option>
                                        <option value="btc" selected>BTC</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a coin type.</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="taskType" class="form-label">Task Type</label>
                                    <select class="form-select" id="taskType" name="taskType">
                                        <option value="">Select Task Type</option>
                                        <option value="buy">To Buy</option>
                                        <option value="sell">To Sell</option>
                                    </select>
                                    <div class="invalid-feedback">Please select a task type.</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="dateFrom" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="dateFrom" name="dateFrom">
                                    <div class="invalid-feedback">Please select a start date.</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="dateTo" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="dateTo" name="dateTo">
                                    <div class="invalid-feedback">Please select an end date.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Fields for Sell Task Type -->
                        <div class="row task-fields d-none" id="sellFields">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currentCoinValue" class="form-label">Your Current Coin Value</label>
                                    <input type="number" class="form-control" id="currentCoinValue" name="currentCoinValue" step="0.00000001" placeholder="Enter your current coin value" disabled>
                                    <div class="invalid-feedback">Please enter a valid coin value.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastPhpValue" class="form-label">Your Last PHP Value Before Buying</label>
                                    <input type="number" class="form-control" id="lastPhpValue" name="lastPhpValue" step="0.01" placeholder="Enter your last PHP value" disabled>
                                    <div class="invalid-feedback">Please enter a valid PHP value.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Fields for Buy Task Type -->
                        <div class="row task-fields d-none" id="buyFields">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currentPhpValue" class="form-label">Your Current PHP Value</label>
                                    <input type="number" class="form-control" id="currentPhpValue" name="currentPhpValue" step="0.01" placeholder="Enter your current PHP value" disabled>
                                    <div class="invalid-feedback">Please enter a valid PHP value.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="lastCoinValue" class="form-label">Your Last Coin Value Before Selling Coin</label>
                                    <input type="number" class="form-control" id="lastCoinValue" name="lastCoinValue" step="0.00000001" placeholder="Enter your last coin value" disabled>
                                    <div class="invalid-feedback">Please enter a valid coin value.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-success me-2" id="useCurrentSetBtn">
                                    <i class="bx bx-check-circle me-1"></i> Use Current Set
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-chart me-1"></i> Generate Analysis
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Graph Section -->
    <div class="row" id="analysisGraphSection" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Analysis Results</h4>
                    <p class="card-title-desc" id="analysisDescription">Historical price analysis with Your Supposed BTC Price (line) and difference calculations (bars).</p>
                </div>
                <div class="card-body">
                    <!-- Legend for Buy Task Type -->
                    <div id="buyLegend" class="mb-3" style="display: none;">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="d-flex align-items-center">
                                <div style="width: 12px; height: 12px; background-color: #ff4560; border-radius: 50%; margin-right: 8px;"></div>
                                <span class="fw-medium">Current PHP Value: ₱<span id="currentPhpValueDisplay"></span></span>
                            </div>
                        </div>
                    </div>

                    <!-- Legend for Sell Task Type -->
                    <div id="sellLegend" class="mb-3" style="display: none;">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="d-flex align-items-center">
                                <div style="width: 12px; height: 12px; background-color: #ff4560; border-radius: 50%; margin-right: 8px;"></div>
                                <span class="fw-medium">Last PHP Value Before Buying: ₱<span id="lastPhpValueDisplay"></span></span>
                            </div>
                        </div>
                    </div>

                                         <div id="analysisChart" style="height: 400px;"></div>

                                          <!-- Selected Bars Average Section -->
                     <div id="selectedBarsAverage" class="mt-3" style="display: none;">
                         <div class="alert alert-info">
                             <div class="d-flex align-items-center">
                                 <i class="bx bx-calculator me-2"></i>
                                 <div>
                                     <strong>Selected Bars Difference Average:</strong>
                                     <span id="averageDifference">₱0.00</span>
                                     <span id="selectedCount" class="text-muted ms-2">(0 bars selected)</span>
                                 </div>
                             </div>
                         </div>
                     </div>

                     <!-- Horizontal Marker Form -->
                     <div class="mt-4">
                         <div class="card border">
                             <div class="card-body">
                                 <h6 class="card-title mb-3">Add Horizontal Marker</h6>
                                 <p class="text-muted small mb-3">Enter only values that are within the range of the Y-axis label.</p>
                                 <div class="row">
                                     <div class="col-md-6">
                                         <label for="markerPhpValue" class="form-label">Enter PHP Value</label>
                                         <input type="number" class="form-control" id="markerPhpValue" step="0.01" placeholder="Enter PHP value">
                                         <div class="invalid-feedback" id="markerError"></div>
                                     </div>
                                 </div>
                                 <div class="row mt-3">
                                     <div class="col-md-6">
                                         <div class="d-flex gap-2">
                                             <button type="button" class="btn btn-success" id="addMarkerBtn" title="Add Marker">
                                                 <i class="bx bx-plus"></i>
                                             </button>
                                             <button type="button" class="btn btn-danger" id="removeMarkersBtn" title="Remove All Markers">
                                                 <i class="bx bx-trash"></i>
                                             </button>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('script'); ?>
    <!-- Apexcharts -->
    <script src="<?php echo e(URL::asset('build/libs/apexcharts/apexcharts.min.js')); ?>"></script>
    <!-- Toastr -->
    <script src="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.js')); ?>"></script>

    <script>
        $(document).ready(function() {
            // Set default dates (last 1 day)
            const today = new Date();
            const oneDayAgo = new Date(today.getTime() - (1 * 24 * 60 * 60 * 1000));

            $('#dateFrom').val(oneDayAgo.toISOString().split('T')[0]);
            $('#dateTo').val(today.toISOString().split('T')[0]);

            // Task type change handler
            $('#taskType').on('change', function() {
                const taskType = $(this).val();

                // Disable all task-specific fields first
                $('#currentCoinValue, #lastPhpValue, #currentPhpValue, #lastCoinValue').prop('disabled', true);

                // Hide all task-specific fields
                $('.task-fields').addClass('d-none');

                // Show and enable relevant fields based on task type
                if (taskType === 'sell') {
                    $('#sellFields').removeClass('d-none');
                    $('#currentCoinValue, #lastPhpValue').prop('disabled', false);
                } else if (taskType === 'buy') {
                    $('#buyFields').removeClass('d-none');
                    $('#currentPhpValue, #lastCoinValue').prop('disabled', false);
                }

                                 // Clear validation states
                 $('.form-control').removeClass('is-invalid');
             });

             // Add Marker button handler
             $('#addMarkerBtn').on('click', function() {
                 const phpValue = $('#markerPhpValue').val();
                 const input = $('#markerPhpValue');
                 const errorDiv = $('#markerError');

                 // Clear previous error
                 input.removeClass('is-invalid');
                 errorDiv.text('');

                 if (!phpValue || phpValue.trim() === '') {
                     input.addClass('is-invalid');
                     errorDiv.text('Please enter a PHP value');
                     return;
                 }

                 if (isNaN(phpValue)) {
                     input.addClass('is-invalid');
                     errorDiv.text('Please enter a valid number');
                     return;
                 }

                 const value = parseFloat(phpValue);
                 addCustomMarker(value);
                 input.val(''); // Clear input
                 errorDiv.text(''); // Clear any previous error
             });

             // Remove Markers button handler
             $('#removeMarkersBtn').on('click', function() {
                 removeAllCustomMarkers();
             });

                         // Clear marker input errors when user starts typing
            $('#markerPhpValue').on('input', function() {
                $(this).removeClass('is-invalid');
                $('#markerError').text('');
            });

            // Use Current Set button handler
            $('#useCurrentSetBtn').on('click', function() {
                const btn = $(this);
                const originalText = btn.html();

                // Show loading state
                btn.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Loading...');
                btn.prop('disabled', true);

                // Make AJAX call to get current task data
                $.ajax({
                    url: '<?php echo e(route("crypto-difference-analysis.current-task")); ?>',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                                        success: function(response) {
                        console.log('AJAX Response:', response);
                        if (response.success) {
                            const data = response.data;
                            console.log('Task data:', data);

                            // Set coin type if it's BTC
                            if (data.taskCoin && data.taskCoin.toLowerCase() === 'btc') {
                                console.log('Setting coin type to btc');
                                $('#coinType').val('btc');
                            }

                            // Set task type and trigger change to show/hide fields
                            if (data.taskType === 'buy') {
                                console.log('Setting task type to buy');
                                $('#taskType').val('buy').trigger('change');

                                // Wait a moment for the fields to be enabled, then populate
                                setTimeout(() => {
                                    // Populate buy-specific fields
                                    if (data.currentPhpValue) {
                                        console.log('Setting currentPhpValue to:', data.currentPhpValue);
                                        $('#currentPhpValue').val(data.currentPhpValue);
                                    }
                                    if (data.lastCoinValue) {
                                        console.log('Setting lastCoinValue to:', data.lastCoinValue);
                                        $('#lastCoinValue').val(data.lastCoinValue);
                                    }
                                }, 100);

                            } else if (data.taskType === 'sell') {
                                console.log('Setting task type to sell');
                                $('#taskType').val('sell').trigger('change');

                                // Wait a moment for the fields to be enabled, then populate
                                setTimeout(() => {
                                    // Populate sell-specific fields
                                    if (data.currentCoinValue) {
                                        console.log('Setting currentCoinValue to:', data.currentCoinValue);
                                        $('#currentCoinValue').val(data.currentCoinValue);
                                    }
                                    if (data.lastPhpValue) {
                                        console.log('Setting lastPhpValue to:', data.lastPhpValue);
                                        $('#lastPhpValue').val(data.lastPhpValue);
                                    }
                                }, 100);
                            }

                            // Show success notification
                            toastr.success('Form updated from the latest set', 'Success');
                        } else {
                            console.log('AJAX Error Response:', response);
                            toastr.error(response.message || 'Failed to load current task data', 'Error');
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = 'Failed to load current task data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage, 'Error');
                    },
                    complete: function() {
                        // Reset button state
                        btn.html(originalText);
                        btn.prop('disabled', false);
                    }
                });
            });

            // Form validation
            function validateForm() {
                let isValid = true;
                const taskType = $('#taskType').val();

                // Clear previous validation states
                $('.form-control').removeClass('is-invalid');

                // Validate required fields
                if (!$('#coinType').val()) {
                    $('#coinType').addClass('is-invalid');
                    isValid = false;
                }

                if (!$('#taskType').val()) {
                    $('#taskType').addClass('is-invalid');
                    isValid = false;
                }

                if (!$('#dateFrom').val()) {
                    $('#dateFrom').addClass('is-invalid');
                    isValid = false;
                }

                if (!$('#dateTo').val()) {
                    $('#dateTo').addClass('is-invalid');
                    isValid = false;
                }

                // Validate task-specific fields
                if (taskType === 'sell') {
                    if (!$('#currentCoinValue').val()) {
                        $('#currentCoinValue').addClass('is-invalid');
                        isValid = false;
                    }
                    if (!$('#lastPhpValue').val()) {
                        $('#lastPhpValue').addClass('is-invalid');
                        isValid = false;
                    }
                } else if (taskType === 'buy') {
                    if (!$('#currentPhpValue').val()) {
                        $('#currentPhpValue').addClass('is-invalid');
                        isValid = false;
                    }
                    if (!$('#lastCoinValue').val()) {
                        $('#lastCoinValue').addClass('is-invalid');
                        isValid = false;
                    }
                }

                return isValid;
            }

            // Form submission
            $('#cryptoAnalysisForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Form submitted');

                if (validateForm()) {
                    console.log('Form validation passed');

                    // Show loading state
                    const submitBtn = $(this).find('button[type="submit"]');
                    const originalText = submitBtn.html();
                    submitBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i> Generating...');
                    submitBtn.prop('disabled', true);

                    // Collect form data
                    const formData = {
                        coinType: $('#coinType').val(),
                        taskType: $('#taskType').val(),
                        dateFrom: $('#dateFrom').val(),
                        dateTo: $('#dateTo').val(),
                        currentCoinValue: $('#currentCoinValue').val(),
                        currentPhpValue: $('#currentPhpValue').val(),
                        lastPhpValue: $('#lastPhpValue').val(),
                        lastCoinValue: $('#lastCoinValue').val()
                    };

                    console.log('Form data:', formData);

                    // Send AJAX request
                    $.ajax({
                        url: '<?php echo e(route("crypto-difference-analysis.generate")); ?>',
                        type: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log('AJAX success:', response);
                            if (response.success) {
                                createAnalysisChart(response.data);
                                $('#analysisGraphSection').show();

                                // Show/hide legend based on task type and update instruction text
                                if (response.data.taskType === 'buy') {
                                    $('#currentPhpValueDisplay').text(parseFloat(response.data.referenceLines[0].value).toLocaleString());
                                    $('#buyLegend').show();
                                    $('#sellLegend').hide();
                                    $('#analysisDescription').text('Historical price analysis with Your Supposed BTC Price (line) and difference calculations (bars). Red bar indicates lower prices vs. your current PHP value and last coin value which indicates a good purchase opportunity.');
                                } else if (response.data.taskType === 'sell') {
                                    $('#lastPhpValueDisplay').text(parseFloat(response.data.referenceLines[0].value).toLocaleString());
                                    $('#sellLegend').show();
                                    $('#buyLegend').hide();
                                    $('#analysisDescription').text('Historical price analysis with Your Supposed BTC Price (line) and difference calculations (bars). Green bar indicates higher prices vs. your last Php value and current coin value which indicates a good buy.');
                                } else {
                                    $('#buyLegend').hide();
                                    $('#sellLegend').hide();
                                    $('#analysisDescription').text('Historical price analysis with Your Supposed BTC Price (line) and difference calculations (bars).');
                                }
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX error:', xhr, status, error);
                            alert('Error generating analysis: ' + error);
                        },
                        complete: function() {
                            // Reset button state
                            submitBtn.html(originalText);
                            submitBtn.prop('disabled', false);
                        }
                    });
                } else {
                    console.log('Form validation failed');
                }
            });

                         // Global variables for bar selection and markers
             let selectedBars = [];
             let currentDifferenceData = [];
             let customMarkers = [];
             let originalReferenceLine = null; // Store the original reference line
             let markerColors = [
                 '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
                 '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
                 '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#D7BDE2'
             ];

            // Create analysis chart
            function createAnalysisChart(data) {
                console.log('Chart data:', data);
                console.log('Difference data:', data.differenceData);

                // Store difference data globally for calculations
                currentDifferenceData = data.differenceData;
                selectedBars = [];

                                 // Reset average display and custom markers
                 $('#selectedBarsAverage').hide();
                 customMarkers = [];

                // Create series array
                const series = [
                    {
                        name: 'Your Supposed BTC Price',
                        data: data.values,
                        type: 'line'
                    },
                    {
                        name: 'Difference',
                        data: data.differenceData,
                        type: 'bar'
                    }
                ];

                // Add horizontal line for Current PHP Value (independent of legend)
                let annotations = [];
                if (data.referenceLines.length > 0) {
                    const referenceLine = data.referenceLines[0];
                    const originalAnnotation = {
                        y: parseFloat(referenceLine.value),
                        borderColor: '#ff4560',
                        borderWidth: 2,
                        strokeDashArray: 5,
                        opacity: 0.7
                    };

                    // Store the original reference line globally
                    originalReferenceLine = originalAnnotation;
                    annotations.push(originalAnnotation);
                }



                const chartOptions = {
                    series: series,
                    chart: {
                        height: 500,
                        type: 'line',
                        toolbar: {
                            show: false
                        }
                    },
                    colors: ['#556ee6'], // Line color only
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        width: [3, 0],
                        curve: 'smooth'
                    },
                                         plotOptions: {
                         bar: {
                             columnWidth: '50%',
                             borderRadius: 4,
                             distributed: true,
                             colors: {
                                 ranges: [{
                                     from: -Infinity,
                                     to: 0,
                                     color: '#f46a6a'  // Red for negative values (loss)
                                 }, {
                                     from: 0,
                                     to: Infinity,
                                     color: '#34c38f'  // Green for positive values (gain)
                                 }]
                             }
                         }
                     },
                     states: {
                         hover: {
                             filter: {
                                 type: 'darken',
                                 value: 0.1
                             }
                         },
                         active: {
                             filter: {
                                 type: 'darken',
                                 value: 0.35
                             }
                         }
                     },
                    fill: {
                        opacity: [1, 0.8],
                        gradient: {
                            inverseColors: false,
                            shade: 'light',
                            type: "vertical",
                            opacityFrom: 0.85,
                            opacityTo: 0.55,
                            stops: [0, 100, 100, 100]
                        }
                    },
                    grid: {
                        borderColor: '#f1f1f1'
                    },
                    markers: {
                        size: 0
                    },
                    xaxis: {
                        categories: data.labels,
                        title: {
                            text: 'Date & Time'
                        }
                    },
                                         yaxis: [
                         {
                             title: {
                                 text: 'PHP Value'
                             },
                             labels: {
                                 formatter: function(value) {
                                     return '₱' + value.toLocaleString();
                                 }
                             }
                         },
                         {
                             opposite: true,
                             title: {
                                 text: 'Difference (PHP)'
                             },
                             labels: {
                                 formatter: function(value) {
                                     return '₱' + value.toLocaleString();
                                 }
                             }
                         }
                     ],
                    tooltip: {
                        shared: true,
                        intersect: false,
                        custom: function({ series, seriesIndex, dataPointIndex, w }) {
                            const btcPrice = series[0][dataPointIndex];
                            const difference = series[1][dataPointIndex];
                            const dateLabel = w.globals.labels[dataPointIndex];

                            const btcPriceFormatted = '₱' + btcPrice.toLocaleString();
                            const differenceFormatted = '₱' + difference.toLocaleString();
                            const differenceColor = difference >= 0 ? '#34c38f' : '#f46a6a';

                            return '<div class="custom-tooltip" style="padding: 8px; background: #fff; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' +
                                   '<div style="margin-bottom: 4px;"><span style="color: #666;">BTC Price:</span> <span style="color: #333;">' + btcPriceFormatted + '</span></div>' +
                                   '<div><span style="color: #666;">Difference:</span> <span style="color: ' + differenceColor + ';">' + differenceFormatted + '</span></div>' +
                                   '</div>';
                        }
                    },
                                         legend: {
                         position: 'top',
                         horizontalAlign: 'left',
                         markers: {
                             customHTML: [
                                 function() {
                                     return '<span style="background: #556ee6; width: 12px; height: 12px; border-radius: 50%; display: inline-block;"></span>';
                                 },
                                 function() {
                                     return '<span style="background: linear-gradient(90deg, #f46a6a 50%, #34c38f 50%); width: 12px; height: 12px; border-radius: 50%; display: inline-block;"></span>';
                                 }
                             ]
                         }
                     },
                     annotations: {
                         yaxis: annotations
                     }
                };

                // Destroy existing chart if it exists
                if (window.analysisChart && typeof window.analysisChart.destroy === 'function') {
                    window.analysisChart.destroy();
                }

                // Check if ApexCharts is loaded
                if (typeof ApexCharts === 'undefined') {
                    console.error('ApexCharts is not loaded');
                    alert('Chart library not loaded. Please refresh the page.');
                    return;
                }

                                 // Create new chart
                 window.analysisChart = new ApexCharts(document.querySelector("#analysisChart"), chartOptions);
                 window.analysisChart.render().then(() => {
                     // Get Y-axis range after chart is rendered
                     setTimeout(() => {
                         updateYAxisRange();
                     }, 500);
                 });

                // Add click event listener for bar selection
                window.analysisChart.addEventListener('dataPointSelection', function(event, chartContext, config) {
                    const dataPointIndex = config.dataPointIndex;
                    const seriesIndex = config.seriesIndex;

                    // Only handle clicks on the difference bars (series index 1)
                    if (seriesIndex === 1) {
                        toggleBarSelection(dataPointIndex);
                    }
                });
            }

                        // Function to toggle bar selection
            function toggleBarSelection(barIndex) {
                const index = selectedBars.indexOf(barIndex);

                if (index > -1) {
                    // Remove from selection
                    selectedBars.splice(index, 1);
                } else {
                    // Add to selection
                    selectedBars.push(barIndex);
                }

                updateBarColors();
                updateAverageDisplay();
            }

            // Function to update bar colors based on selection
            function updateBarColors() {
                if (window.analysisChart) {
                    // Apply custom colors to bars
                    setTimeout(() => {
                        const bars = document.querySelectorAll('.apexcharts-bar-area');
                        bars.forEach((bar, index) => {
                            if (selectedBars.includes(index)) {
                                bar.style.fill = '#ff8c00'; // Orange for selected bars
                            } else {
                                // Reset to original colors based on value
                                const value = currentDifferenceData[index];
                                bar.style.fill = value < 0 ? '#f46a6a' : '#34c38f'; // Red for negative, Green for positive
                            }
                        });
                    }, 100);
                }
            }

            // Function to update average display
            function updateAverageDisplay() {
                const averageSection = $('#selectedBarsAverage');
                const averageSpan = $('#averageDifference');
                const countSpan = $('#selectedCount');

                if (selectedBars.length >= 2) {
                    // Calculate average of selected bars
                    const selectedValues = selectedBars.map(index => currentDifferenceData[index]);
                    const sum = selectedValues.reduce((acc, val) => acc + val, 0);
                    const average = sum / selectedValues.length;

                    // Update display
                    averageSpan.text('₱' + average.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    countSpan.text(`(${selectedBars.length} bars selected)`);
                    averageSection.show();
                } else {
                    // Hide average section if less than 2 bars selected
                                                              averageSection.hide();
                 }
             }

            // Function to add custom horizontal marker
            function addCustomMarker(phpValue) {
                const markerColor = markerColors[customMarkers.length % markerColors.length];
                const marker = {
                    y: phpValue,
                    borderColor: markerColor,
                    borderWidth: 2,
                    strokeDashArray: 3,
                    opacity: 0.8,
                    label: {
                        borderColor: markerColor,
                        style: {
                            color: '#fff',
                            background: markerColor
                        },
                        text: '₱' + phpValue.toLocaleString()
                    }
                };

                customMarkers.push(marker);
                updateChartAnnotations();
            }

            // Function to remove all custom markers
            function removeAllCustomMarkers() {
                customMarkers = [];
                updateChartAnnotations();
            }

                         // Function to update Y-axis range display
             function updateYAxisRange() {
                 if (window.analysisChart && window.analysisChart.w.config.yaxis && window.analysisChart.w.config.yaxis[0]) {
                     const yAxis = window.analysisChart.w.config.yaxis[0];

                     // Get the actual Y-axis range from the chart
                     const yAxisLabels = document.querySelectorAll('.apexcharts-yaxis-label');
                     if (yAxisLabels.length > 0) {
                         // Get the first and last visible labels
                         const firstLabel = yAxisLabels[0];
                         const lastLabel = yAxisLabels[yAxisLabels.length - 1];

                         if (firstLabel && lastLabel) {
                             const minValue = parseFloat(firstLabel.textContent.replace(/[₱,]/g, ''));
                             const maxValue = parseFloat(lastLabel.textContent.replace(/[₱,]/g, ''));

                             if (!isNaN(minValue) && !isNaN(maxValue)) {
                                 $('#yAxisMin').text(minValue.toLocaleString());
                                 $('#yAxisMax').text(maxValue.toLocaleString());
                             }
                         }
                     }
                 }
             }

             // Function to update chart annotations
             function updateChartAnnotations() {
                if (window.analysisChart) {
                    // Get the original reference line annotations
                    let allAnnotations = [];

                    // Always add the original reference line if it exists
                    if (originalReferenceLine) {
                        allAnnotations.push(originalReferenceLine);
                    }

                    // Add custom markers
                    allAnnotations = allAnnotations.concat(customMarkers);

                    // Update the chart
                    window.analysisChart.updateOptions({
                        annotations: {
                            yaxis: allAnnotations
                        }
                    });
                }
            }
         });
     </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/crypto-difference-analysis.blade.php ENDPATH**/ ?>