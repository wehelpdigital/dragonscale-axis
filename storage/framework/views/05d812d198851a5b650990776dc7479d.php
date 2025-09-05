<?php $__env->startSection('title'); ?> Crypto Ladder History <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Crypto <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Crypto Ladder History <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<!-- Page Description -->
<div class="row">
    <div class="col-12">
        <p class="text-muted mb-4">
            This module displays the percentage difference between the most recent cryptocurrency price in Philippine Peso (PHP)
            and historical coin prices. Compare current market values against past performance to identify trends and make
            informed trading decisions based on price variations over time.
        </p>
    </div>
</div>

<div class="row">
        <!-- Filters -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Filters</h4>
                <form method="GET" action="<?php echo e(route('crypto-pricing-history')); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date"
                               value="<?php echo e(request('start_date')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date"
                               value="<?php echo e(request('end_date')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="coin_type" class="form-label">Coin Type</label>
                        <select class="form-select" id="coin_type" name="coin_type">
                            <option value="btc" <?php echo e($coinType == 'btc' ? 'selected' : ''); ?>>BTC</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="<?php echo e(route('crypto-pricing-history')); ?>" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AI Analysis Button -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-success btn-lg" id="generateAiAnalysis">
                        <i class="bx bx-brain me-2"></i>
                        Generate AI Analysis
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ladder Graph -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Crypto Price Ladder</h4>
                        <p class="card-title-desc">Percentage change from current price over time</p>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="drawModeBtn" title="Toggle Drawing Mode">
                            <i class="bx bx-pen"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="clearDrawingsBtn" title="Clear All Drawings">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Most Recent Legend -->
                <?php if(count($ladderData['values']) > 0): ?>
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="d-flex align-items-center">
                                <div style="width: 12px; height: 12px; background-color: #ff4560; border-radius: 50%; margin-right: 8px;"></div>
                                <span class="fw-medium">Most Recent: ₱<?php echo e(number_format(end($ladderData['values']), 2)); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="ladderChart" style="height: 500px; position: relative;">
                    <canvas id="drawingCanvas" style="position: absolute; top: 0; left: 0; pointer-events: none; z-index: 10; border-radius: 4px;"></canvas>
                </div>

                <!-- Selected Bars Average Display -->
                <div id="selectedBarsAverage" class="mt-3" style="display: none;">
                    <div class="alert alert-info">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-bar-chart-alt-2 me-2"></i>
                            <div>
                                <strong>Selected Bars Average:</strong>
                                <span id="averageDifference">₱0.00</span>
                                <small class="text-muted ms-2">(Average difference from current price for selected bars)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Horizontal Marker Form -->
                <div class="mt-4">
                    <h6 class="mb-3">Add Horizontal Marker</h6>
                    <p class="text-muted small mb-3">Enter a PHP value to add a horizontal marker line.</p>

                    <div class="row">
                        <div class="col-md-4">
                            <label for="markerValue" class="form-label">Enter PHP Value</label>
                            <input type="number" class="form-control" id="markerValue" placeholder="Enter PHP value" step="0.01">
                            <div id="markerError" class="invalid-feedback" style="display: none;"></div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" id="addMarkerBtn" title="Add Marker">
                                    <i class="bx bx-plus"></i>
                                </button>
                                <button type="button" class="btn btn-danger" id="removeAllMarkersBtn" title="Remove All Markers">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historical Data Table -->
    <div class="col-12">
        <div class="card">
            <div class="card-header" id="historicalDataHeader" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#historicalDataContent" aria-expanded="false" aria-controls="historicalDataContent">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Historical Price Data</h4>
                        <p class="card-title-desc mb-0">Showing <?php echo e($historicalPrices->total()); ?> records</p>
                    </div>
                    <div>
                        <i class="bx bx-chevron-up" id="collapseIcon"></i>
                    </div>
                </div>
            </div>
            <div class="collapse" id="historicalDataContent" aria-labelledby="historicalDataHeader">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Value in PHP</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>% Change from Current</th>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Difference Analysis Card -->
    <div class="col-12">
        <div class="card">
            <div class="card-header" id="differenceAnalysisHeader" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#differenceAnalysisContent" aria-expanded="false" aria-controls="differenceAnalysisContent">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Difference Analysis</h4>
                        <p class="card-title-desc mb-0">Compare two different time periods and values</p>
                    </div>
                    <div>
                        <i class="bx bx-chevron-up" id="differenceAnalysisIcon"></i>
                    </div>
                </div>
            </div>
            <div class="collapse" id="differenceAnalysisContent" aria-labelledby="differenceAnalysisHeader">
                <div class="card-body">
                    <form id="differenceAnalysisForm">
                    <!-- Dropdowns Row -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="coinType" class="form-label">Coin Type</label>
                            <select class="form-select" id="coinType" name="coinType">
                                <option value="">Select Coin Type</option>
                                <option value="btc" selected>BTC</option>
                            </select>
                            <div class="invalid-feedback">Please select a coin type.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="valueType" class="form-label">Value Type</label>
                            <select class="form-select" id="valueType" name="valueType">
                                <option value="">Select Value Type</option>
                                <option value="crypto">Crypto</option>
                                <option value="php">PHP</option>
                            </select>
                            <div class="invalid-feedback">Please select a value type.</div>
                        </div>
                    </div>

                    <!-- Comparison Section -->
                    <div class="row">
                        <!-- Comparison A -->
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary">
                                    <h5 class="card-title mb-0" style="color: #fff;">Comparison A</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-3">
                                        <small><strong>Instruction:</strong> Comparison A should be the later time period (nearer to present)</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="dateA" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="dateA" name="dateA">
                                        <div class="invalid-feedback">Please select a date.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="timeA" class="form-label">Time</label>
                                        <select class="form-select" id="timeA" name="timeA">
                                            <option value="">Select Time</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a time.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="valueA" class="form-label">Value</label>
                                        <div id="valueAInput">
                                            <input type="number" class="form-control" id="valueA" name="valueA" step="0.00000001" placeholder="Enter crypto value">
                                        </div>
                                        <div class="invalid-feedback">Please enter a valid value.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Comparison B -->
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="card-title mb-0">Comparison B</h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-3">
                                        <small><strong>Instruction:</strong> Comparison B should be the earlier time period (further from present)</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="dateB" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="dateB" name="dateB">
                                        <div class="invalid-feedback">Please select a date.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="timeB" class="form-label">Time</label>
                                        <select class="form-select" id="timeB" name="timeB">
                                            <option value="">Select Time</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a time.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="valueB" class="form-label">Value</label>
                                        <div id="valueBInput">
                                            <input type="number" class="form-control" id="valueB" name="valueB" step="0.00000001" placeholder="Enter crypto value">
                                        </div>
                                        <div class="invalid-feedback">Please enter a valid value.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calculate Button -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary btn-lg" id="calculateDifference">
                                <i class="bx bx-calculator me-2"></i>
                                Calculate Difference
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Results Modal -->
<div class="modal fade" id="differenceResultsModal" tabindex="-1" aria-labelledby="differenceResultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="differenceResultsModalLabel">Difference Analysis Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart data from PHP
    const ladderData = <?php echo json_encode($ladderData, 15, 512) ?>;

    if (ladderData.labels.length === 0) {
        document.getElementById('ladderChart').innerHTML = '<div class="text-center p-4"><p class="text-muted">No data available for the selected filters.</p></div>';
        return;
    }

    // Initialize data loading
    loadHistoricalData(1);

    // Get the most recent value for horizontal line
    const mostRecentValue = ladderData.values[ladderData.values.length - 1];

    // Track selected bars
    let selectedBars = [];
    let originalColors = [];

    // Track custom horizontal markers
    let customMarkers = [];
    let markerColors = [
        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
        '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
        '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#D7BDE2'
    ];

    // Initialize ApexCharts for ladder graph
    const options = {
        series: [{
            name: 'Price (PHP)',
            type: 'line',
            data: ladderData.values
        }, {
            name: 'Ladder (% Change)',
            type: 'bar',
            data: ladderData.ladderValues
        }],
        chart: {
            height: 500,
            type: 'line',
            toolbar: {
                show: false  // Disable toolbar completely like crypto-history
            },
            zoom: {
                enabled: true,
                type: 'x',
                autoScaleYaxis: true
            }
        },
        stroke: {
            width: [3, 1],
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
                        color: '#f46a6a'  // Red for negative values (below current price)
                    }, {
                        from: 0,
                        to: Infinity,
                        color: '#34c38f'  // Green for positive values (above current price)
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
        xaxis: {
            categories: ladderData.labels,
            labels: {
                show: true,
                formatter: function(value, opts) {
                    // Format the date to show as "Aug. 10 - 11:00pm"
                    const date = new Date(value);
                    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    const month = monthNames[date.getMonth()];
                    const day = date.getDate();
                    const hours = date.getHours();
                    const minutes = date.getMinutes();

                    // Format time to 12-hour format with am/pm
                    let timeString;
                    if (hours === 0) {
                        timeString = `12:${minutes.toString().padStart(2, '0')}am`;
                    } else if (hours < 12) {
                        timeString = `${hours}:${minutes.toString().padStart(2, '0')}am`;
                    } else if (hours === 12) {
                        timeString = `12:${minutes.toString().padStart(2, '0')}pm`;
                    } else {
                        timeString = `${hours - 12}:${minutes.toString().padStart(2, '0')}pm`;
                    }

                    return `${month}. ${day} - ${timeString}`;
                },
                style: {
                    fontSize: '11px',
                    colors: '#666'
                },
                rotate: -45,
                rotateAlways: false
            }
        },
        markers: {
            size: 0
        },
        yaxis: [
            {
                title: {
                    text: 'Price (PHP)',
                },
                labels: {
                    formatter: function (val) {
                        return '₱' + val.toLocaleString();
                    }
                }
            },
            {
                opposite: true,
                title: {
                    text: 'Percentage Change (%)',
                },
                labels: {
                    formatter: function (val) {
                        return val.toFixed(2) + '%';
                    }
                }
            }
        ],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y, { seriesIndex }) {
                    if (seriesIndex === 0) {
                        return '₱' + y.toLocaleString();
                    } else {
                        return y.toFixed(2) + '%';
                    }
                }
            },
            custom: function({ series, seriesIndex, dataPointIndex, w }) {
                const price = series[0][dataPointIndex];
                const ladderValue = series[1][dataPointIndex];
                const date = ladderData.dates[dataPointIndex];
                const mostRecentValue = ladderData.values[ladderData.values.length - 1];
                const difference = price - mostRecentValue; // historical price - current price
                const differenceFormatted = difference >= 0 ? '+' + difference.toLocaleString() : difference.toLocaleString();

                // Determine colors based on positive/negative values
                const ladderColor = ladderValue >= 0 ? '#34c38f' : '#f46a6a';
                const differenceColor = difference >= 0 ? '#34c38f' : '#f46a6a';

                return '<div class="custom-tooltip">' +
                       '<div class="tooltip-date">' + date + '</div>' +
                       '<div class="tooltip-price">Price: ₱' + price.toLocaleString() + '</div>' +
                       '<div class="tooltip-ladder" style="color: ' + ladderColor + ';">Ladder: ' + ladderValue.toFixed(2) + '%</div>' +
                       '<div class="tooltip-difference" style="color: ' + differenceColor + ';">Difference: ₱' + differenceFormatted + '</div>' +
                       '</div>';
            }
        },
        colors: ['#556ee6'],
        grid: {
            borderColor: '#f1f1f1',
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
            yaxis: [{
                y: mostRecentValue,
                borderColor: '#ff4560',
                borderWidth: 2,
                strokeDashArray: 5,
                opacity: 0.7
            }]
        }
    };

    const chart = new ApexCharts(document.querySelector("#ladderChart"), options);
    chart.render();
    window.ladderChart = chart; // Store chart instance globally

    // Add click event listener for bar selection
    chart.addEventListener('dataPointSelection', function(event, chartContext, config) {
        const dataPointIndex = config.dataPointIndex;
        const seriesIndex = config.seriesIndex;

        // Only handle clicks on the ladder bars (series index 1)
        if (seriesIndex === 1) {
            toggleBarSelection(dataPointIndex);
        }
    });

    // Function to toggle bar selection
    function toggleBarSelection(dataPointIndex) {
        const index = selectedBars.indexOf(dataPointIndex);

        if (index > -1) {
            // Remove from selection
            selectedBars.splice(index, 1);
        } else {
            // Add to selection
            selectedBars.push(dataPointIndex);
        }

        updateBarColors();
        updateAverageDisplay();
    }

    // Function to update bar colors based on selection
    function updateBarColors() {
        if (window.ladderChart) {
            // Update the colors array for the ladder series
            const newColors = ladderData.ladderValues.map((value, index) => {
                if (selectedBars.includes(index)) {
                    return '#ffa500'; // Orange for selected bars
                } else {
                    return value >= 0 ? '#34c38f' : '#f46a6a'; // Original colors
                }
            });

            // Update the chart with new colors
            window.ladderChart.updateOptions({
                colors: ['#556ee6'], // Keep original line color
                plotOptions: {
                    bar: {
                        columnWidth: '50%',
                        borderRadius: 4,
                        distributed: true,
                        colors: {
                            ranges: [{
                                from: -Infinity,
                                to: 0,
                                color: '#f46a6a'  // Red for negative values (below current price)
                            }, {
                                from: 0,
                                to: Infinity,
                                color: '#34c38f'  // Green for positive values (above current price)
                            }]
                        }
                    }
                }
            });

            // Manually update the colors for the ladder series
            window.ladderChart.updateSeries([{
                name: 'Price (PHP)',
                data: window.ladderChart.w.config.series[0].data,
                type: 'line'
            }, {
                name: 'Ladder (% Change)',
                data: ladderData.ladderValues,
                type: 'bar'
            }]);

            // Apply custom colors to bars
            setTimeout(() => {
                const bars = document.querySelectorAll('.apexcharts-bar-area');
                bars.forEach((bar, index) => {
                    if (selectedBars.includes(index)) {
                        bar.style.fill = '#ffa500';
                    }
                });
            }, 100);
        }
    }

        // Function to update average display
    function updateAverageDisplay() {
        const averageDisplay = document.getElementById('selectedBarsAverage');
        const averageText = document.getElementById('averageDifference');

        if (selectedBars.length >= 2) {
            let totalDifference = 0;
            let count = 0;

            selectedBars.forEach(index => {
                const price = ladderData.values[index];
                const difference = price - mostRecentValue;
                totalDifference += difference;
                count++;
            });

            const averageDifference = totalDifference / count;
            const formattedAverage = averageDifference >= 0 ?
                '+' + averageDifference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) :
                averageDifference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            averageText.textContent = '₱' + formattedAverage;
            averageDisplay.style.display = 'block';
        } else {
            averageDisplay.style.display = 'none';
        }
    }

    // Drawing functionality
    let isDrawingMode = false;
    let isDrawing = false;
    let drawingCanvas = document.getElementById('drawingCanvas');
    let ctx = drawingCanvas.getContext('2d');
    let lastX = 0;
    let lastY = 0;
    let drawings = [];

        // Initialize canvas
    function initCanvas() {
        const chartContainer = document.getElementById('ladderChart');

        // Check if canvas still exists, if not recreate it
        if (!drawingCanvas || !drawingCanvas.parentNode) {
            drawingCanvas = document.createElement('canvas');
            drawingCanvas.id = 'drawingCanvas';
            drawingCanvas.style.position = 'absolute';
            drawingCanvas.style.top = '0';
            drawingCanvas.style.left = '0';
            drawingCanvas.style.pointerEvents = 'none';
            drawingCanvas.style.zIndex = '10';
            chartContainer.appendChild(drawingCanvas);
            ctx = drawingCanvas.getContext('2d');
        }

        const rect = chartContainer.getBoundingClientRect();

        // Only update dimensions if they've changed
        if (drawingCanvas.width !== rect.width || drawingCanvas.height !== rect.height) {
            drawingCanvas.width = rect.width;
            drawingCanvas.height = rect.height;
        }

        // Set canvas styles
        ctx.strokeStyle = '#ffa500'; // Orange color like crypto-history
        ctx.lineWidth = 4; // Thicker line like crypto-history
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        // Ensure canvas is properly positioned
        drawingCanvas.style.width = rect.width + 'px';
        drawingCanvas.style.height = rect.height + 'px';
    }

    // Toggle drawing mode
    document.getElementById('drawModeBtn').addEventListener('click', function() {
        isDrawingMode = !isDrawingMode;
        this.classList.toggle('btn-secondary');
        this.classList.toggle('btn-outline-secondary');

        if (isDrawingMode) {
            // Ensure canvas is properly initialized
            initCanvas();
            attachDrawingListeners();
            drawingCanvas.style.pointerEvents = 'auto';
            this.innerHTML = '<i class="bx bx-pen" style="color: #fff;"></i>';
            this.title = 'Drawing Mode Active - Click to Disable';
        } else {
            drawingCanvas.style.pointerEvents = 'none';
            this.innerHTML = '<i class="bx bx-pen"></i>';
            this.title = 'Toggle Drawing Mode';
        }
    });

    // Clear all drawings
    document.getElementById('clearDrawingsBtn').addEventListener('click', function() {
        ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);
        drawings = [];
    });

    // Function to attach drawing event listeners
    function attachDrawingListeners() {
        // Remove existing listeners first
        drawingCanvas.removeEventListener('mousedown', handleMouseDown);
        drawingCanvas.removeEventListener('mousemove', handleMouseMove);
        drawingCanvas.removeEventListener('mouseup', handleMouseUp);
        drawingCanvas.removeEventListener('mouseleave', handleMouseLeave);

        // Add new listeners
        drawingCanvas.addEventListener('mousedown', handleMouseDown);
        drawingCanvas.addEventListener('mousemove', handleMouseMove);
        drawingCanvas.addEventListener('mouseup', handleMouseUp);
        drawingCanvas.addEventListener('mouseleave', handleMouseLeave);
    }

    // Mouse event handlers
    function handleMouseDown(e) {
        if (!isDrawingMode) return;

        e.preventDefault();
        isDrawing = true;
        const rect = drawingCanvas.getBoundingClientRect();
        lastX = e.clientX - rect.left;
        lastY = e.clientY - rect.top;
    }

    function handleMouseMove(e) {
        if (!isDrawingMode || !isDrawing) return;

        e.preventDefault();
        const rect = drawingCanvas.getBoundingClientRect();
        const currentX = e.clientX - rect.left;
        const currentY = e.clientY - rect.top;

        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        ctx.lineTo(currentX, currentY);
        ctx.stroke();

        lastX = currentX;
        lastY = currentY;
    }

    function handleMouseUp() {
        if (!isDrawingMode) return;
        isDrawing = false;
    }

    function handleMouseLeave() {
        if (!isDrawingMode) return;
        isDrawing = false;
    }

    // Attach initial event listeners
    attachDrawingListeners();

    // Initialize canvas after chart renders
    setTimeout(initCanvas, 1000);

    // Resize canvas when window resizes
    window.addEventListener('resize', function() {
        setTimeout(initCanvas, 100);
    });

    // Re-initialize canvas periodically to ensure it stays on top
    setInterval(() => {
        if (isDrawingMode) {
            initCanvas();
            attachDrawingListeners();
        }
    }, 2000);

    // Re-initialize canvas when chart updates (for horizontal markers)
    function reinitCanvas() {
        setTimeout(() => {
            // Re-initialize canvas (this will recreate if needed)
            initCanvas();

            // Re-attach event listeners
            attachDrawingListeners();

            // Re-set pointer events if drawing mode is active
            if (isDrawingMode) {
                drawingCanvas.style.pointerEvents = 'auto';
            }
        }, 100);
    }

    // Horizontal Marker Functionality
    const markerValueInput = document.getElementById('markerValue');
    const addMarkerBtn = document.getElementById('addMarkerBtn');
    const removeAllMarkersBtn = document.getElementById('removeAllMarkersBtn');
    const markerError = document.getElementById('markerError');

    // Add marker button click handler
    addMarkerBtn.addEventListener('click', function() {
        addCustomMarker();
    });

    // Remove all markers button click handler
    removeAllMarkersBtn.addEventListener('click', function() {
        removeAllCustomMarkers();
    });

    // Enter key handler for input
    markerValueInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addCustomMarker();
        }
    });

    // Function to add custom horizontal marker
    function addCustomMarker() {
        const value = parseFloat(markerValueInput.value);

        // Clear previous error
        clearMarkerError();

        // Validate input
        if (!markerValueInput.value || isNaN(value)) {
            showMarkerError('Please enter a valid PHP value.');
            return;
        }

        // Check if marker already exists
        if (customMarkers.some(marker => Math.abs(marker.value - value) < 0.01)) {
            showMarkerError('A marker with this value already exists.');
            return;
        }

        // Get random color
        const color = markerColors[customMarkers.length % markerColors.length];

        // Add marker to array
        customMarkers.push({
            value: value,
            color: color
        });

        // Update chart with new marker
        updateChartWithMarkers();

        // Clear input
        markerValueInput.value = '';
    }

    // Function to remove all custom markers
    function removeAllCustomMarkers() {
        customMarkers = [];
        updateChartWithMarkers();
    }



    // Function to update chart with markers
    function updateChartWithMarkers() {
        if (window.ladderChart) {
            // Create annotations array with default marker and custom markers
            const annotations = [{
                y: mostRecentValue,
                borderColor: '#ff4560',
                borderWidth: 2,
                strokeDashArray: 5,
                opacity: 0.7
            }];

            // Add custom markers
            customMarkers.forEach(marker => {
                annotations.push({
                    y: marker.value,
                    borderColor: marker.color,
                    borderWidth: 2,
                    strokeDashArray: 5,
                    opacity: 0.7,
                    label: {
                        borderColor: marker.color,
                        style: {
                            color: '#fff',
                            background: marker.color
                        },
                        text: '₱' + marker.value.toLocaleString()
                    }
                });
            });

            // Update chart
            window.ladderChart.updateOptions({
                annotations: {
                    yaxis: annotations
                }
            });

            // Re-initialize canvas after chart update
            reinitCanvas();
        }
    }

    // Function to show marker error
    function showMarkerError(message) {
        markerError.textContent = message;
        markerError.style.display = 'block';
        markerValueInput.classList.add('is-invalid');
    }

    // Function to clear marker error
    function clearMarkerError() {
        markerError.style.display = 'none';
        markerValueInput.classList.remove('is-invalid');
    }

        // Handle collapse icon rotation for Historical Data
    const collapseIcon = document.getElementById('collapseIcon');
    const historicalDataContent = document.getElementById('historicalDataContent');

    historicalDataContent.addEventListener('show.bs.collapse', function () {
        collapseIcon.classList.remove('bx-chevron-up');
        collapseIcon.classList.add('bx-chevron-down');
    });

    historicalDataContent.addEventListener('hide.bs.collapse', function () {
        collapseIcon.classList.remove('bx-chevron-down');
        collapseIcon.classList.add('bx-chevron-up');
    });

    // Handle collapse icon rotation for Difference Analysis
    const differenceAnalysisIcon = document.getElementById('differenceAnalysisIcon');
    const differenceAnalysisContent = document.getElementById('differenceAnalysisContent');

    differenceAnalysisContent.addEventListener('show.bs.collapse', function () {
        differenceAnalysisIcon.classList.remove('bx-chevron-up');
        differenceAnalysisIcon.classList.add('bx-chevron-down');
    });

    differenceAnalysisContent.addEventListener('hide.bs.collapse', function () {
        differenceAnalysisIcon.classList.remove('bx-chevron-down');
        differenceAnalysisIcon.classList.add('bx-chevron-up');
    });

    // Difference Analysis Form Functionality
    const valueTypeSelect = document.getElementById('valueType');
    const valueAInput = document.getElementById('valueAInput');
    const valueBInput = document.getElementById('valueBInput');
    const valueA = document.getElementById('valueA');
    const valueB = document.getElementById('valueB');
    const timeA = document.getElementById('timeA');
    const timeB = document.getElementById('timeB');
    const form = document.getElementById('differenceAnalysisForm');

    // Generate time options (5-minute intervals)
    function generateTimeOptions() {
        const times = [];
        for (let hour = 0; hour < 24; hour++) {
            for (let minute = 0; minute < 60; minute += 5) {
                const time = new Date();
                time.setHours(hour, minute, 0, 0);
                const timeString = time.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                times.push(timeString);
            }
        }
        return times;
    }

    // Populate time dropdowns
    function populateTimeDropdowns() {
        const times = generateTimeOptions();
        const timeOptions = times.map(time => `<option value="${time}">${time}</option>`).join('');

        timeA.innerHTML = '<option value="">Select Time</option>' + timeOptions;
        timeB.innerHTML = '<option value="">Select Time</option>' + timeOptions;
    }

        // Update value input based on value type
    function updateValueInputs() {
        const selectedValueType = valueTypeSelect.value;
        const valueAContainer = document.querySelector('.col-md-6:first-child .card-body .mb-3:last-child');
        const valueBContainer = document.querySelector('.col-md-6:last-child .card-body .mb-3:last-child');

        if (selectedValueType === 'crypto') {
            // Show value inputs for crypto
            valueAContainer.style.display = 'block';
            valueBContainer.style.display = 'block';

            valueAInput.innerHTML = '<input type="number" class="form-control" id="valueA" name="valueA" step="0.00000001" placeholder="Enter crypto value" required>';
            valueBInput.innerHTML = '<input type="number" class="form-control" id="valueB" name="valueB" step="0.00000001" placeholder="Enter crypto value" required>';

            // Re-attach event listeners
            document.getElementById('valueA').addEventListener('input', validateField);
            document.getElementById('valueB').addEventListener('input', validateField);
        } else if (selectedValueType === 'php') {
            // Hide value inputs for PHP
            valueAContainer.style.display = 'none';
            valueBContainer.style.display = 'none';

            // Clear the inputs
            valueAInput.innerHTML = '';
            valueBInput.innerHTML = '';
        }
    }

    // Validate individual field
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        let isValid = true;
        let errorMessage = '';

        // Remove existing validation classes
        field.classList.remove('is-invalid', 'is-valid');

        // Validate based on field type
        switch (fieldName) {
            case 'coinType':
                if (!value) {
                    isValid = false;
                    errorMessage = 'Please select a coin type.';
                }
                break;
            case 'valueType':
                if (!value) {
                    isValid = false;
                    errorMessage = 'Please select a value type.';
                }
                break;
            case 'dateA':
            case 'dateB':
                if (!value) {
                    isValid = false;
                    errorMessage = 'Please select a date.';
                }
                break;
            case 'timeA':
            case 'timeB':
                if (!value) {
                    isValid = false;
                    errorMessage = 'Please select a time.';
                }
                break;
            case 'valueA':
            case 'valueB':
                if (!value) {
                    isValid = false;
                    errorMessage = 'Please enter a valid value.';
                } else if (parseFloat(value) <= 0) {
                    isValid = false;
                    errorMessage = 'Value must be greater than 0.';
                }
                break;
        }

        // Apply validation classes
        if (isValid) {
            field.classList.add('is-valid');
        } else {
            field.classList.add('is-invalid');
            // Update error message
            const feedbackElement = field.parentNode.querySelector('.invalid-feedback');
            if (feedbackElement) {
                feedbackElement.textContent = errorMessage;
            }
        }

        return isValid;
    }

    // Validate form
    function validateForm() {
        const form = document.getElementById('differenceAnalysisForm');
        const selectedValueType = valueTypeSelect.value;
        let isValid = true;

        // Get all form fields
        const fields = form.querySelectorAll('input, select');

        // Validate each field
        fields.forEach(field => {
            // Skip hidden value fields if PHP is selected
            if (selectedValueType === 'php' && (field.name === 'valueA' || field.name === 'valueB')) {
                return;
            }

            if (!validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    // Initialize form
    populateTimeDropdowns();

    // Event listeners
    valueTypeSelect.addEventListener('change', updateValueInputs);

    // Add validation listeners to all form fields
    document.querySelectorAll('#differenceAnalysisForm input, #differenceAnalysisForm select').forEach(field => {
        field.addEventListener('input', () => validateField(field));
        field.addEventListener('change', () => validateField(field));
    });

        // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (validateForm()) {
            // Show loading state
            const submitBtn = document.getElementById('calculateDifference');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-2"></i>Calculating...';
            submitBtn.disabled = true;

            // Prepare form data
            const formData = new FormData(form);

            // Make AJAX request
            fetch('<?php echo e(route("crypto-difference-calculation")); ?>', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;

                if (data.success) {
                    displayResults(data);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                showError('An error occurred while calculating the difference.');
                console.error('Error:', error);
            });
        }
    });

        // Display results in modal
    function displayResults(data) {
        const modalContent = document.getElementById('modalContent');
        const valueTypeLabel = data.valueDifference.label;
        const isCrypto = data.comparisonA.valueType === 'crypto';

        modalContent.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="card-title mb-0">Comparison A</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Date:</strong> ${data.comparisonA.date}</p>
                            <p><strong>Time:</strong> ${data.comparisonA.time}</p>
                            <p><strong>Historical Price:</strong> ₱${data.comparisonA.historicalPrice}</p>
                            ${isCrypto ? `<p><strong>Your Value:</strong> ${data.comparisonA.userValue} BTC</p>` : ''}
                            ${isCrypto ? `<p><strong>Value in PHP:</strong> ₱${data.comparisonA.userValueInPhp}</p>` : ''}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="card-title mb-0">Comparison B</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Date:</strong> ${data.comparisonB.date}</p>
                            <p><strong>Time:</strong> ${data.comparisonB.time}</p>
                            <p><strong>Historical Price:</strong> ₱${data.comparisonB.historicalPrice}</p>
                            ${isCrypto ? `<p><strong>Your Value:</strong> ${data.comparisonB.userValue} BTC</p>` : ''}
                            ${isCrypto ? `<p><strong>Value in PHP:</strong> ₱${data.comparisonB.userValueInPhp}</p>` : ''}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="${isCrypto ? 'col-md-6' : 'col-md-12'}">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Coin Price Difference</h6>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="${data.coinPriceDifference.direction === 'positive' ? 'text-success' : 'text-danger'}">
                                ${data.coinPriceDifference.direction === 'positive' ? '+' : ''}₱${data.coinPriceDifference.formatted}
                            </h4>
                            <small class="text-muted">Historical price difference between the two time periods</small>
                        </div>
                    </div>
                </div>
                ${isCrypto ? `
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">${valueTypeLabel}</h6>
                        </div>
                        <div class="card-body text-center">
                            <h4 class="${data.valueDifference.direction === 'positive' ? 'text-success' : 'text-danger'}">
                                ${data.valueDifference.direction === 'positive' ? '+' : ''}₱${data.valueDifference.formatted}
                            </h4>
                            <small class="text-muted">Difference between your crypto values (converted to PHP)</small>
                        </div>
                    </div>
                </div>
                ` : ''}
            </div>
        `;

            // Show modal with debugging
    const modalElement = document.getElementById('differenceResultsModal');
    console.log('Modal element:', modalElement);

    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        console.log('Modal instance:', modal);
        modal.show();

        // Set proper modal z-index
        modalElement.style.zIndex = '1055';
    } else {
        console.error('Modal element not found!');
    }
    }

        // Show error message
    function showError(message) {
        const modalContent = document.getElementById('modalContent');
        modalContent.innerHTML = `
            <div class="alert alert-danger">
                <i class="bx bx-error-circle me-2"></i>
                ${message}
            </div>
        `;

        const modalElement = document.getElementById('differenceResultsModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Set proper modal z-index
            modalElement.style.zIndex = '1055';
        }
    }

    // Load historical data via AJAX
    function loadHistoricalData(page = 1) {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const coinType = document.getElementById('coin_type').value;

        // Show loading state
        document.getElementById('historical-data-tbody').innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>';

        // Build query parameters
        const params = new URLSearchParams();
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
        if (coinType) params.append('coin_type', coinType);
        params.append('page', page);

        // Make AJAX request
        fetch(`<?php echo e(route('crypto-pricing-history.data')); ?>?${params.toString()}`, {
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
                document.getElementById('historical-data-tbody').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading data: ' + data.message + '</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('historical-data-tbody').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading data. Please try again.</td></tr>';
        });
    }

    // Render historical data in table
    function renderHistoricalData(data) {
        const tbody = document.getElementById('historical-data-tbody');

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center">No historical price data found.</td></tr>';
            return;
        }

        let html = '';
        data.forEach((price) => {
            const percentageClass = price.percentage_change >= 0 ? 'bg-success' : 'bg-danger';
            const sign = price.percentage_change >= 0 ? '+' : '';

            html += `
                <tr>
                    <td><strong>₱${price.valueInPhp}</strong></td>
                    <td>${price.date_formatted}</td>
                    <td>${price.time_formatted}</td>
                    <td>
                        <span class="badge ${percentageClass}">
                            ${sign}${price.percentage_change_formatted}%
                        </span>
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

    document.getElementById('coin_type').addEventListener('change', function() {
        loadHistoricalData(1);
    });
});
</script>

<style>
.custom-tooltip {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.tooltip-date {
    font-weight: bold;
    color: #333;
    margin-bottom: 4px;
}

.tooltip-price {
    color: #556ee6;
    font-size: 14px;
    margin-bottom: 2px;
}

.tooltip-ladder {
    color: #34c38f;
    font-size: 14px;
}

.tooltip-difference {
    color: #ffa500;
    font-size: 14px;
}

.table th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-color: #dee2e6;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
}

.pagination {
    margin-top: 20px;
}

.page-link {
    color: #556ee6;
    border-color: #dee2e6;
}

.page-item.active .page-link {
    background-color: #556ee6;
    border-color: #556ee6;
}

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

.badge {
    font-size: 0.75em;
    padding: 0.5em 0.75em;
}

/* Collapsible card styling */
.card-header[data-bs-toggle="collapse"] {
    transition: background-color 0.2s ease;
}

.card-header[data-bs-toggle="collapse"]:hover {
    background-color: #f8f9fa;
}

#collapseIcon {
    transition: transform 0.3s ease;
    font-size: 1.2rem;
    color: #6c757d;
}

#collapseIcon.bx-chevron-up {
    transform: rotate(180deg);
}

#differenceAnalysisIcon {
    transition: transform 0.3s ease;
    font-size: 1.2rem;
    color: #6c757d;
}

#differenceAnalysisIcon.bx-chevron-up {
    transform: rotate(180deg);
}

/* Drawing Canvas Styling */
#drawingCanvas {
    border-radius: 4px;
}

#drawModeBtn.active {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
}

.btn-group .btn {
    border-radius: 0.25rem;
}

.btn-group .btn:first-child {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.btn-group .btn:last-child {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

/* Modal Styling */
#differenceResultsModal {
    z-index: 1055 !important;
}

#differenceResultsModal .modal-dialog {
    z-index: 1056 !important;
}

.modal-backdrop {
    z-index: 1050 !important;
}

/* Ensure modal appears above all other elements */
.modal.show {
    display: block !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}

/* Chart interaction styling */
#ladderChart {
    cursor: default;
}

#ladderChart .apexcharts-bar-area {
    cursor: pointer;
    transition: opacity 0.2s ease;
}

#ladderChart .apexcharts-bar-area:hover {
    opacity: 0.8;
}

/* Selected bars average display styling */
#selectedBarsAverage .alert {
    border-left: 4px solid #17a2b8;
    background-color: #f8f9fa;
}

#averageDifference {
    font-weight: bold;
    color: #17a2b8;
}

/* Difference Analysis Form Styling */
.card.border-primary {
    border-width: 2px !important;
}

.card.border-success {
    border-width: 2px !important;
}

.form-control.is-valid {
    border-color: #198754;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 3.03-3.03-1.06-1.06-1.97 1.97-.94.94-1.06-1.06z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 1.4 1.4m0-1.4-1.4 1.4'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-select.is-valid {
    border-color: #198754;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 4 4 4-4'/%3e%3c/svg%3e"), url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='m2.3 6.73.94-.94 3.03-3.03-1.06-1.06-1.97 1.97-.94.94-1.06-1.06z'/%3e%3c/svg%3e");
    background-position: right 0.75rem center, center right 2.25rem;
    background-size: 16px 12px, 16px 12px;
}

.form-select.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 4 4 4-4'/%3e%3c/svg%3e"), url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 1.4 1.4m0-1.4-1.4 1.4'/%3e%3c/svg%3e");
    background-position: right 0.75rem center, center right 2.25rem;
    background-size: 16px 12px, 16px 12px;
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/crypto-pricing-history.blade.php ENDPATH**/ ?>