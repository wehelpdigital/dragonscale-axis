<?php $__env->startSection('title'); ?> Crypto History <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Crypto <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Crypto History <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<!-- Page Description -->
<div class="row">
    <div class="col-12">
        <p class="text-muted mb-4">
            Track and analyze the complete historical price movements of cryptocurrency values converted to Philippine Peso (PHP).
            This comprehensive module provides detailed insights into price trends, percentage changes, and value fluctuations over time,
            helping you make informed decisions based on historical market data.
        </p>
    </div>
</div>

<div class="row">
    <!-- Filters -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Filters</h4>
                <form method="GET" action="<?php echo e(route('crypto-history')); ?>" class="row g-3">
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
                            <a href="<?php echo e(route('crypto-history')); ?>" class="btn btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bar Chart -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Price Trend Chart</h4>
                        <p class="card-title-desc">Hover over bars to see detailed information</p>
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

                <!-- Independent Difference Legend -->
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="d-flex align-items-center me-4">
                            <span style="background: linear-gradient(90deg, #f46a6a 50%, #34c38f 50%); width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 8px;"></span>
                            <span class="fw-medium">Difference (Red: Decrease, Green: Increase)</span>
                        </div>
                    </div>
                </div>

                <div id="priceChart" style="height: 400px; position: relative;">
                    <canvas id="drawingCanvas" style="position: absolute; top: 0; left: 0; pointer-events: none; z-index: 99999;"></canvas>
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
                    <p class="text-muted small mb-3">Enter a PHP value to add a horizontal marker line. Range: ₱<span id="minValue">0</span> - ₱<span id="maxValue">0</span></p>

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
                        <i class="bx bx-chevron-up" id="historicalDataIcon"></i>
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
                                    <th>% Change from Previous</th>
                                    <th>Difference from Previous</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $historicalPrices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $price): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $currentPrice = $price->valueInPhp;
                                        $previousPrice = $index > 0 ? $historicalPrices[$index - 1]->valueInPhp : null;

                                        if ($previousPrice) {
                                            $difference = $currentPrice - $previousPrice;
                                            $percentageChange = ($difference / $previousPrice) * 100;
                                        } else {
                                            $difference = 0;
                                            $percentageChange = 0;
                                        }
                                    ?>
                                    <tr>
                                        <td><strong>₱<?php echo e(number_format($price->valueInPhp, 2)); ?></strong></td>
                                        <td><?php echo e($price->created_at->setTimezone('Asia/Manila')->format('F j, Y')); ?></td>
                                        <td><?php echo e($price->created_at->setTimezone('Asia/Manila')->format('g:iA')); ?></td>
                                        <td>
                                            <?php if($index > 0): ?>
                                                <span class="badge <?php echo e($percentageChange >= 0 ? 'bg-success text-dark' : 'bg-danger'); ?>">
                                                    <?php echo e($percentageChange >= 0 ? '+' : ''); ?><?php echo e(number_format($percentageChange, 2)); ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($index > 0): ?>
                                                <span class="badge <?php echo e($difference >= 0 ? 'bg-success text-dark' : 'bg-danger'); ?>">
                                                    <?php echo e($difference >= 0 ? '+' : ''); ?>₱<?php echo e(number_format($difference, 2)); ?>

                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No historical price data found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        <?php echo e($historicalPrices->appends(request()->query())->links()); ?>

                    </div>
                </div>
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
    const chartData = <?php echo json_encode($chartData, 15, 512) ?>;

    // Check if chart data is available
    if (!chartData || !chartData.values || chartData.values.length === 0) {
        document.getElementById('priceChart').innerHTML = '<div class="text-center p-4"><p class="text-muted">No data available for the selected filters.</p></div>';
        return;
    }

    // Drawing functionality variables (moved to top to avoid reference issues)
    let isDrawingMode = false;
    let isDrawing = false;
    let drawingCanvas = document.getElementById('drawingCanvas');
    let ctx = drawingCanvas.getContext('2d');
    let lastX = 0;
    let lastY = 0;
    let drawings = [];

    // Generate dynamic colors based on previous bar values
    function generateDynamicColors(values) {
        const colors = [];
        for (let i = 0; i < values.length; i++) {
            if (i === 0) {
                // First bar - use green color
                colors.push('#34c38f');
            } else {
                const currentValue = values[i];
                const previousValue = values[i - 1];

                if (currentValue > previousValue) {
                    // Current bar is higher than previous - green
                    colors.push('#34c38f');
                } else if (currentValue < previousValue) {
                    // Current bar is lower than previous - red
                    colors.push('#f46a6a');
                } else {
                    // Same value - use green color
                    colors.push('#34c38f');
                }
            }
        }
        return colors;
    }

    // Calculate percentage change and difference from previous bar
    function calculateChange(currentValue, previousValue) {
        if (!previousValue) return { percentage: 0, difference: 0 };

        const difference = currentValue - previousValue;
        const percentage = (difference / previousValue) * 100;

        return {
            percentage: percentage,
            difference: difference
        };
    }

    // Initialize ApexCharts
    const options = {
        series: [{
            name: 'BTC Price (PHP)',
            data: chartData.values
        }],
        chart: {
            type: 'bar',
            height: 400,
            toolbar: {
                show: true,
                tools: {
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true,
                    reset: true
                }
            },
            zoom: {
                enabled: true,
                type: 'x',
                autoScaleYaxis: true
            },
            selection: {
                enabled: true,
                type: 'x'
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '70%',
                endingShape: 'rounded',
                borderRadius: 4,
                distributed: true,
                colors: {
                    ranges: [{
                        from: 0,
                        to: chartData.values.length - 1,
                        color: '#556ee6'
                    }]
                }
            },
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: chartData.labels,
            labels: {
                show: true,
                formatter: function(value, opts) {
                    // Check if value is a string and has the expected format
                    if (typeof value !== 'string' || !value) {
                        return value || '';
                    }

                    // The value is already formatted as "M j H:i" from the controller
                    // Convert 24-hour format to 12-hour format with am/pm
                    const parts = value.split(' ');
                    if (parts.length >= 3) {
                        const month = parts[0];
                        const day = parts[1];
                        const time = parts[2];

                        // Parse time (HH:MM format)
                        const [hours, minutes] = time.split(':').map(Number);

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
                    }

                    // Fallback to original value if parsing fails
                    return value;
                },
                style: {
                    fontSize: '11px',
                    colors: '#666'
                },
                rotate: -45,
                rotateAlways: false
            }
        },
        yaxis: {
            title: {
                text: 'Price (PHP)'
            },
            min: Math.min(...chartData.values) - 50000,
            max: Math.max(...chartData.values) + 50000,
            labels: {
                formatter: function (val) {
                    return '₱' + val.toLocaleString();
                }
            }
        },
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return '₱' + val.toLocaleString();
                }
            },
            custom: function({ series, seriesIndex, dataPointIndex, w }) {
                const currentValue = series[seriesIndex][dataPointIndex];
                const date = chartData.dates[dataPointIndex];
                const previousValue = dataPointIndex > 0 ? chartData.values[dataPointIndex - 1] : null;
                const change = calculateChange(currentValue, previousValue);

                let tooltipContent = '<div class="custom-tooltip">' +
                                   '<div class="tooltip-date">' + date + '</div>' +
                                   '<div class="tooltip-value">₱' + currentValue.toLocaleString() + '</div>';

                if (dataPointIndex > 0) {
                    const percentageSign = change.percentage >= 0 ? '+' : '';
                    const differenceSign = change.difference >= 0 ? '+' : '';
                    const changeColor = change.percentage >= 0 ? '#34c38f' : '#f46a6a';
                    const differenceColor = change.difference >= 0 ? '#34c38f' : '#f46a6a';

                    tooltipContent += '<div class="tooltip-change" style="color: ' + changeColor + ';">Change: ' + percentageSign + change.percentage.toFixed(2) + '%</div>' +
                                    '<div class="tooltip-difference" style="color: ' + differenceColor + ';">Difference: ' + differenceSign + '₱' + change.difference.toLocaleString() + '</div>';
                }

                tooltipContent += '</div>';
                return tooltipContent;
            }
        },
        grid: {
            borderColor: '#f1f1f1',
        },
        legend: {
            show: false
        },
        annotations: {
            yaxis: []
        },
        colors: generateDynamicColors(chartData.values)
    };

    const chart = new ApexCharts(document.querySelector("#priceChart"), options);
    chart.render();
    window.priceChart = chart; // Store chart instance globally



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

    // Add click event listener for bar selection
    chart.addEventListener('dataPointSelection', function(event, chartContext, config) {
        const dataPointIndex = config.dataPointIndex;
        const seriesIndex = config.seriesIndex;

        // Only handle clicks on the bars (series index 0)
        if (seriesIndex === 0) {
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
        if (window.priceChart) {
            // Generate new colors array with selected bars as orange
            var newColors = generateDynamicColors(chartData.values).map(function(color, index) {
                if (selectedBars.includes(index)) {
                    return '#ffa500'; // Orange for selected bars
                } else {
                    return color; // Original color
                }
            });

            // Update the chart with new colors
            window.priceChart.updateOptions({
                colors: newColors
            });
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
                const currentValue = chartData.values[index];
                const previousValue = index > 0 ? chartData.values[index - 1] : null;
                const change = calculateChange(currentValue, previousValue);

                if (index > 0) { // Only include bars that have a previous value to compare against
                    totalDifference += change.difference;
                    count++;
                }
            });

            if (count > 0) {
                const averageDifference = totalDifference / count;
                const formattedAverage = averageDifference >= 0 ?
                    '+' + averageDifference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) :
                    averageDifference.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                averageText.textContent = '₱' + formattedAverage;
                averageDisplay.style.display = 'block';
            } else {
                averageDisplay.style.display = 'none';
            }
        } else {
            averageDisplay.style.display = 'none';
        }
    }

    // Update range values in the form
    document.getElementById('minValue').textContent = Math.min(...chartData.values).toLocaleString();
    document.getElementById('maxValue').textContent = Math.max(...chartData.values).toLocaleString();

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
        if (window.priceChart) {
            // Create annotations array with custom markers only
            const annotations = [];

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
            window.priceChart.updateOptions({
                annotations: {
                    yaxis: annotations
                }
            });

            // Re-initialize canvas after chart update
            reinitCanvas();
        }
    }

        // Re-initialize canvas when chart updates (for horizontal markers)
    function reinitCanvas() {
        setTimeout(function() {
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

    // Drawing functionality

        // Initialize canvas
    function initCanvas() {
        var chartContainer = document.getElementById('priceChart');

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

        var rect = chartContainer.getBoundingClientRect();

        // Always update dimensions to ensure proper sizing
        drawingCanvas.width = rect.width;
        drawingCanvas.height = rect.height;

        // Set canvas styles
        ctx.strokeStyle = '#ffa500'; // Orange color for drawing
        ctx.lineWidth = 4; // Thicker line
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        // Ensure canvas is properly positioned
        drawingCanvas.style.width = rect.width + 'px';
        drawingCanvas.style.height = rect.height + 'px';

        console.log('Canvas initialized:', rect.width, 'x', rect.height);
    }

    // Toggle drawing mode
    document.getElementById('drawModeBtn').addEventListener('click', function() {
        isDrawingMode = !isDrawingMode;
        this.classList.toggle('btn-secondary');
        this.classList.toggle('btn-outline-secondary');

        if (isDrawingMode) {
            drawingCanvas.style.pointerEvents = 'auto';
            drawingCanvas.style.display = 'block';
            this.innerHTML = '<i class="bx bx-pen" style="color: #fff;"></i>';
            this.title = 'Drawing Mode Active - Click to Disable';
        } else {
            drawingCanvas.style.pointerEvents = 'none';
            drawingCanvas.style.display = 'block';
            this.innerHTML = '<i class="bx bx-pen"></i>';
            this.title = 'Toggle Drawing Mode';
        }
    });

        // Add zoom selection mode button
    const zoomSelectionBtn = document.createElement('button');
    zoomSelectionBtn.type = 'button';
    zoomSelectionBtn.className = 'btn btn-outline-primary btn-sm';
    zoomSelectionBtn.innerHTML = '<i class="bx bx-crop"></i>';
    zoomSelectionBtn.title = 'Custom Zoom Selection - Draw rectangle to zoom';
    zoomSelectionBtn.style.marginLeft = '5px';

    // Add reset zoom button
    const resetZoomBtn = document.createElement('button');
    resetZoomBtn.type = 'button';
    resetZoomBtn.className = 'btn btn-outline-secondary btn-sm';
    resetZoomBtn.innerHTML = '<i class="bx bx-reset"></i>';
    resetZoomBtn.title = 'Reset Zoom';
    resetZoomBtn.style.marginLeft = '5px';

    // Insert after the draw mode button
    document.getElementById('drawModeBtn').parentNode.appendChild(zoomSelectionBtn);
    document.getElementById('drawModeBtn').parentNode.appendChild(resetZoomBtn);

    let isZoomSelectionMode = false;
    let zoomStartX = 0;
    let zoomStartY = 0;
    let isZoomSelecting = false;

    zoomSelectionBtn.addEventListener('click', function() {
        isZoomSelectionMode = !isZoomSelectionMode;
        this.classList.toggle('btn-primary');
        this.classList.toggle('btn-outline-primary');

        if (isZoomSelectionMode) {
            // Enable custom zoom selection
            drawingCanvas.style.pointerEvents = 'auto';
            drawingCanvas.style.display = 'block';
            this.innerHTML = '<i class="bx bx-crop" style="color: #fff;"></i>';
            this.title = 'Custom Zoom Active - Draw rectangle to zoom';

            // Clear any existing drawing and reset state
            ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);
            isZoomSelecting = false;
            zoomStartX = null;
            zoomStartY = null;

            console.log('Zoom selection mode enabled');
        } else {
            drawingCanvas.style.pointerEvents = 'none';
            drawingCanvas.style.display = 'block';
            this.innerHTML = '<i class="bx bx-crop"></i>';
            this.title = 'Custom Zoom Selection - Draw rectangle to zoom';

            // Clear any existing drawing and reset state
            ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);
            isZoomSelecting = false;
            zoomStartX = null;
            zoomStartY = null;

            console.log('Zoom selection mode disabled');
        }
    });

    // Reset zoom button functionality
    resetZoomBtn.addEventListener('click', function() {
        if (window.priceChart) {
            try {
                // Reset zoom by restoring original data and categories
                window.priceChart.updateOptions({
                    series: [{
                        name: 'BTC Price (PHP)',
                        data: chartData.values
                    }],
                    xaxis: {
                        categories: chartData.labels
                    }
                }, false, true);
                console.log('Zoom reset succeeded - showing all', chartData.values.length, 'bars');

                                // Clear canvas and reset zoom selection state
                ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);
                isZoomSelecting = false;
                zoomStartX = null;
                zoomStartY = null;

                                // Re-initialize canvas to ensure proper drawing context
                setTimeout(() => {
                    initCanvas();

                    // Re-attach event listeners
                    attachDrawingListeners();

                    // If zoom selection mode is active, ensure it's ready for new selection
                    if (isZoomSelectionMode) {
                        drawingCanvas.style.pointerEvents = 'auto';
                        console.log('Zoom selection mode ready for new selection');
                    }
                }, 200);

            } catch (error) {
                console.log('Zoom reset failed:', error);
            }
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

        // Add new listeners (removed wheel listener to allow zoom)
        drawingCanvas.addEventListener('mousedown', handleMouseDown);
        drawingCanvas.addEventListener('mousemove', handleMouseMove);
        drawingCanvas.addEventListener('mouseup', handleMouseUp);
        drawingCanvas.addEventListener('mouseleave', handleMouseLeave);
    }

            // Mouse event handlers
    function handleMouseDown(e) {
        if (!isDrawingMode && !isZoomSelectionMode) return;

        const rect = drawingCanvas.getBoundingClientRect();

        if (isDrawingMode) {
            isDrawing = true;
            lastX = e.clientX - rect.left;
            lastY = e.clientY - rect.top;
        } else if (isZoomSelectionMode) {
            isZoomSelecting = true;
            zoomStartX = e.clientX - rect.left;
            zoomStartY = e.clientY - rect.top;
        }
    }

    function handleMouseMove(e) {
        const rect = drawingCanvas.getBoundingClientRect();
        const currentX = e.clientX - rect.left;
        const currentY = e.clientY - rect.top;

        if (isDrawingMode && isDrawing) {
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(currentX, currentY);
            ctx.stroke();

            lastX = currentX;
            lastY = currentY;
        } else if (isZoomSelectionMode && isZoomSelecting) {
            // Clear previous selection rectangle
            ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);

            // Draw selection rectangle
            ctx.strokeStyle = '#007bff';
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 5]);
            ctx.strokeRect(zoomStartX, zoomStartY, currentX - zoomStartX, currentY - zoomStartY);

            // Reset drawing style
            ctx.strokeStyle = '#ffa500';
            ctx.lineWidth = 4;
            ctx.setLineDash([]);
        }
    }

    function handleMouseUp() {
        if (isDrawingMode) {
            isDrawing = false;
        } else if (isZoomSelectionMode && isZoomSelecting) {
            isZoomSelecting = false;

            // Get the selection area
            const rect = drawingCanvas.getBoundingClientRect();
            const endX = event.clientX - rect.left;
            const endY = event.clientY - rect.top;

            // Calculate zoom area
            const startX = Math.min(zoomStartX, endX);
            const startY = Math.min(zoomStartY, endY);
            const width = Math.abs(endX - zoomStartX);
            const height = Math.abs(endY - zoomStartY);

            // Only zoom if selection is large enough
            if (width > 10 && height > 10) {
                console.log('Zoom selection:', { startX, endX, width, height });

                // Convert pixel coordinates to chart coordinates and zoom
                if (window.priceChart) {
                    const chartWidth = rect.width;

                    // Calculate zoom range based on data points
                    const totalDataPoints = chartData.values.length;
                    const xStart = Math.floor((startX / chartWidth) * totalDataPoints);
                    const xEnd = Math.floor(((startX + width) / chartWidth) * totalDataPoints);

                    // Ensure valid range
                    const validStart = Math.max(0, Math.min(xStart, totalDataPoints - 1));
                    const validEnd = Math.max(validStart + 1, Math.min(xEnd, totalDataPoints));

                    console.log('Zoom range:', { validStart, validEnd, totalDataPoints });

                    // Update chart to show only selected bars
                    try {
                        // Get the selected data range
                        const selectedValues = chartData.values.slice(validStart, validEnd + 1);
                        const selectedLabels = chartData.labels.slice(validStart, validEnd + 1);

                        // Update the chart with only the selected data
                        window.priceChart.updateOptions({
                            series: [{
                                name: 'BTC Price (PHP)',
                                data: selectedValues
                            }],
                            xaxis: {
                                categories: selectedLabels
                            }
                        }, false, true);

                        console.log('Zoom succeeded - showing', selectedValues.length, 'bars');
                    } catch (error) {
                        console.log('Zoom failed:', error);
                    }
                }
            }

            // Clear selection rectangle
            ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);
        }
    }

    function handleMouseLeave() {
        if (isDrawingMode) {
            isDrawing = false;
        } else if (isZoomSelectionMode) {
            isZoomSelecting = false;
            // Clear selection rectangle
            ctx.clearRect(0, 0, drawingCanvas.width, drawingCanvas.height);
        }
    }

    function handleWheel(e) {
        // Allow wheel events to pass through to the chart for zooming
        // Don't prevent default or stop propagation
        return true;
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
    setInterval(function() {
        if (isDrawingMode) {
            initCanvas();
            attachDrawingListeners();
        }
    }, 2000);

    // Handle collapse icon rotation for Historical Data
    const historicalDataIcon = document.getElementById('historicalDataIcon');
    const historicalDataContent = document.getElementById('historicalDataContent');

    historicalDataContent.addEventListener('show.bs.collapse', function () {
        historicalDataIcon.classList.remove('bx-chevron-up');
        historicalDataIcon.classList.add('bx-chevron-down');
    });

    historicalDataContent.addEventListener('hide.bs.collapse', function () {
        historicalDataIcon.classList.remove('bx-chevron-down');
        historicalDataIcon.classList.add('bx-chevron-up');
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

.tooltip-value {
    color: #556ee6;
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
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/crypto-history.blade.php ENDPATH**/ ?>