@extends('layouts.master')

@section('title') Location Heatmap @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
    .data-source-btn {
        padding: 12px 24px;
        border: 2px solid #e9ecef;
        background: #fff;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
    }
    .data-source-btn:hover {
        border-color: #556ee6;
        background: #f8f9ff;
    }
    .data-source-btn.active {
        border-color: #556ee6;
        background: #556ee6;
        color: #fff;
    }
    .data-source-btn.active i {
        color: #fff;
    }
    .data-source-btn i {
        font-size: 24px;
        display: block;
        margin-bottom: 8px;
        color: #74788d;
    }
    .data-source-btn .title {
        font-weight: 600;
        font-size: 14px;
    }

    .heatmap-container {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        min-height: 400px;
    }

    .province-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 8px;
        transition: all 0.2s ease;
    }
    .province-item:hover {
        background: #f0f4ff;
    }
    .province-bar {
        height: 8px;
        background: linear-gradient(90deg, #556ee6, #34c38f);
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    .province-rank {
        width: 30px;
        height: 30px;
        background: #556ee6;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 12px;
        margin-right: 12px;
    }
    .province-rank.bronze {
        background: #cd7f32;
    }
    .province-rank.silver {
        background: #adb5bd;
    }
    .province-rank.gold {
        background: #f59e0b;
    }

    .municipality-table {
        max-height: 400px;
        overflow-y: auto;
    }
    .municipality-table .table {
        margin-bottom: 0;
    }
    .municipality-table tbody tr:hover {
        background: #f8f9fa;
    }

    .stat-card {
        background: linear-gradient(135deg, #556ee6, #34c38f);
        color: #fff;
        border-radius: 12px;
        padding: 20px;
    }
    .stat-card .stat-value {
        font-size: 32px;
        font-weight: 700;
    }
    .stat-card .stat-label {
        opacity: 0.9;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 12px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #74788d;
    }
    .empty-state i {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    /* Map Container */
    #philippinesMap {
        width: 100%;
        height: 450px;
        border-radius: 12px;
        background: #e8f4f8;
    }
    .leaflet-container {
        border-radius: 12px;
    }
    .map-tooltip {
        background: #fff;
        border: none;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 8px 12px;
    }
    .map-tooltip .province-name {
        font-weight: 600;
        color: #343a40;
    }
    .map-tooltip .province-count {
        color: #556ee6;
        font-weight: 700;
    }

    /* Map Level Toggle */
    .map-level-toggle {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }
    .map-level-btn {
        flex: 1;
        padding: 8px 12px;
        border: 2px solid #e9ecef;
        background: #fff;
        border-radius: 6px;
        cursor: pointer;
        text-align: center;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    .map-level-btn:hover {
        border-color: #556ee6;
    }
    .map-level-btn.active {
        border-color: #556ee6;
        background: #556ee6;
        color: #fff;
    }
    .map-level-btn i {
        margin-right: 4px;
    }
    .map-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
        background: rgba(255,255,255,0.9);
        padding: 20px 30px;
        border-radius: 8px;
        text-align: center;
    }

    /* Color legend */
    .color-legend {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 16px;
    }
    .color-legend-bar {
        flex: 1;
        height: 8px;
        background: linear-gradient(90deg, #c8e6c9, #66bb6a, #43a047, #2e7d32, #1b5e20);
        border-radius: 4px;
    }
    .color-legend span {
        font-size: 12px;
        color: #74788d;
    }
</style>
@endsection

@section('content')

@component('components.breadcrumb')
    @slot('li_1') Reports @endslot
    @slot('title') Location Heatmap @endslot
@endcomponent

<div class="row">
    <!-- Data Source Selection -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bx bx-data me-2"></i>Select Data Source</h5>
                <div class="row g-3">
                    <div class="col-6 col-md">
                        <div class="data-source-btn active" data-source="orders">
                            <i class="bx bx-shopping-bag"></i>
                            <div class="title">Orders</div>
                            <small class="text-secondary">Completed orders</small>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="data-source-btn" data-source="leads">
                            <i class="bx bx-user-plus"></i>
                            <div class="title">Leads</div>
                            <small class="text-secondary">With location data</small>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="data-source-btn" data-source="affiliates">
                            <i class="bx bx-group"></i>
                            <div class="title">Affiliates</div>
                            <small class="text-secondary">Registered affiliates</small>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="data-source-btn" data-source="clients">
                            <i class="bx bx-map-pin"></i>
                            <div class="title">Clients</div>
                            <small class="text-secondary">Shipping addresses</small>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="data-source-btn" data-source="refunds">
                            <i class="bx bx-undo"></i>
                            <div class="title">Refunds</div>
                            <small class="text-secondary">Approved/Processed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bx bx-filter me-2"></i>Filters</h5>
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label text-dark">Start Date</label>
                        <input type="date" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark">End Date</label>
                        <input type="date" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark">Store</label>
                        <select class="form-select" id="storeFilter">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->storeName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2" id="productFilterCol">
                        <label class="form-label text-dark">Product</label>
                        <select class="form-select" id="productFilter">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ Str::limit($product->productName, 30) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark">Province</label>
                        <select class="form-select" id="provinceFilter">
                            <option value="">All Provinces</option>
                            @foreach($provinces as $province)
                                <option value="{{ $province }}">{{ $province }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary w-100" id="generateBtn">
                            <i class="bx bx-refresh me-1"></i>Generate
                        </button>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 text-end">
                        <button class="btn btn-secondary me-2" id="clearFiltersBtn">
                            <i class="bx bx-reset me-1"></i>Clear Filters
                        </button>
                        <button class="btn btn-success" id="exportBtn" disabled>
                            <i class="bx bx-download me-1"></i>Export CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-value" id="totalRecords">0</div>
            <div class="stat-label">Total Records</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">
            <div class="stat-value" id="totalProvinces">0</div>
            <div class="stat-label">Provinces Covered</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #8b5cf6, #ec4899);">
            <div class="stat-value" id="totalMunicipalities">0</div>
            <div class="stat-label">Municipalities/Cities</div>
        </div>
    </div>

    <!-- Map & Province Ranking -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body position-relative">
                <h5 class="card-title mb-3"><i class="bx bx-map me-2"></i>Philippines Map</h5>
                <div class="map-level-toggle">
                    <div class="map-level-btn active" data-level="province">
                        <i class="bx bx-map-alt"></i>Province
                    </div>
                    <div class="map-level-btn" data-level="municipality">
                        <i class="bx bx-buildings"></i>Municipality
                    </div>
                </div>
                <div id="mapLoadingOverlay" class="map-loading d-none">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="text-dark">Loading map...</div>
                </div>
                <div id="philippinesMap"></div>
                <div class="color-legend">
                    <span>Low</span>
                    <div class="color-legend-bar"></div>
                    <span>High</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Province Ranking -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body position-relative">
                <div class="loading-overlay d-none" id="provinceLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bx bx-trophy me-2 text-warning"></i>Province Ranking</h5>
                    <span class="badge bg-primary" id="provinceCount">0 provinces</span>
                </div>
                <div id="provinceRanking">
                    <div class="empty-state">
                        <i class="bx bx-bar-chart-alt-2"></i>
                        <p class="mb-0 text-dark">No data yet</p>
                        <small>Click "Generate" to load data</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Municipality Breakdown -->
    <div class="col-12">
        <div class="card">
            <div class="card-body position-relative">
                <div class="loading-overlay d-none" id="municipalityLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bx bx-buildings me-2"></i>Municipality/City Breakdown</h5>
                    <span class="badge bg-info" id="municipalityCount">0 municipalities</span>
                </div>
                <div class="municipality-table" id="municipalityTable">
                    <div class="empty-state">
                        <i class="bx bx-building-house"></i>
                        <p class="mb-0 text-dark">No data yet</p>
                        <small>Click "Generate" to load data</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
$(document).ready(function() {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    let currentSource = 'orders';
    let heatmapData = null;
    let map = null;
    let geojsonLayer = null;
    let currentMapLevel = 'province';
    let geoJSONCache = {
        province: null,
        municipality: null
    };

    const geoJSONUrls = {
        province: 'https://raw.githubusercontent.com/macoymejia/geojsonph/master/Province/Provinces.minimal.json',
        municipality: 'https://raw.githubusercontent.com/macoymejia/geojsonph/master/MuniCities/MuniCities.minimal.json'
    };

    // Map level toggle
    $('.map-level-btn').on('click', function() {
        const level = $(this).data('level');
        if (level === currentMapLevel) return;

        $('.map-level-btn').removeClass('active');
        $(this).addClass('active');
        currentMapLevel = level;

        loadGeoJSON(level);
    });

    // Initialize the map
    function initMap() {
        map = L.map('philippinesMap', {
            center: [12.8797, 121.7740], // Philippines center
            zoom: 5.5,
            minZoom: 5,
            maxZoom: 12,
            zoomControl: true,
            scrollWheelZoom: true
        });

        // Add tile layer (light background)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_nolabels/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // Load default (province) GeoJSON
        loadGeoJSON('province');
    }

    // Load GeoJSON data based on level
    function loadGeoJSON(level) {
        // Check cache first
        if (geoJSONCache[level]) {
            renderGeoJSON(geoJSONCache[level], level);
            if (heatmapData) {
                updateMapColors(level === 'province' ? heatmapData.byProvince : heatmapData.byMunicipality, level);
            }
            return;
        }

        $('#mapLoadingOverlay').removeClass('d-none');

        $.ajax({
            url: geoJSONUrls[level],
            dataType: 'json',
            success: function(data) {
                geoJSONCache[level] = data;
                renderGeoJSON(data, level);
                if (heatmapData) {
                    updateMapColors(level === 'province' ? heatmapData.byProvince : heatmapData.byMunicipality, level);
                }
            },
            error: function() {
                toastr.error('Could not load ' + level + ' map data');
            },
            complete: function() {
                $('#mapLoadingOverlay').addClass('d-none');
            }
        });
    }

    // Render GeoJSON on map
    function renderGeoJSON(geoData, level) {
        if (!geoData || !map) return;

        if (geojsonLayer) {
            map.removeLayer(geojsonLayer);
        }

        geojsonLayer = L.geoJSON(geoData, {
            style: function(feature) {
                return {
                    fillColor: '#e0e0e0',
                    weight: level === 'municipality' ? 0.5 : 1,
                    opacity: 1,
                    color: '#fff',
                    fillOpacity: 0.7
                };
            },
            onEachFeature: function(feature, layer) {
                const name = getFeatureName(feature, level);
                layer.bindTooltip(name, {
                    permanent: false,
                    direction: 'auto',
                    className: 'map-tooltip'
                });
            }
        }).addTo(map);

        // Fit map to bounds
        map.fitBounds(geojsonLayer.getBounds());
    }

    // Get feature name based on level
    function getFeatureName(feature, level) {
        if (level === 'municipality') {
            const muni = feature.properties.NAME_2 || feature.properties.MUNICIPALITY || '';
            const prov = feature.properties.PROVINCE || feature.properties.NAME_1 || '';
            return muni + (prov ? ', ' + prov : '');
        }
        return feature.properties.PROVINCE ||
               feature.properties.NAME_1 ||
               feature.properties.ADM2_EN ||
               feature.properties.NAME_2 ||
               feature.properties.name ||
               feature.properties.NAME ||
               'Unknown';
    }

    // Update map colors based on data
    function updateMapColors(locationData, level) {
        if (!geojsonLayer) return;

        const maxCount = locationData.length > 0 ? locationData[0].count : 1;

        // Create lookup maps
        const dataLookup = {};
        locationData.forEach(item => {
            if (level === 'municipality') {
                // For municipalities, create key with province
                const muniKey = normalizeName(item.municipality);
                const provKey = normalizeName(item.province);
                const combinedKey = muniKey + '|' + provKey;
                dataLookup[combinedKey] = item;
                dataLookup[muniKey] = item; // Also add just municipality for fallback
            } else {
                const normalizedName = normalizeName(item.name);
                dataLookup[normalizedName] = item;
            }
        });

        // Update each feature's style
        geojsonLayer.eachLayer(function(layer) {
            const feature = layer.feature;
            let matchedData = null;
            let displayName = '';

            if (level === 'municipality') {
                const muniName = feature.properties.NAME_2 || feature.properties.MUNICIPALITY || '';
                const provName = feature.properties.PROVINCE || feature.properties.NAME_1 || '';
                displayName = muniName + (provName ? ', ' + provName : '');

                const muniKey = normalizeName(muniName);
                const provKey = normalizeName(provName);
                const combinedKey = muniKey + '|' + provKey;

                // Try exact match first
                matchedData = dataLookup[combinedKey];

                // Fallback to municipality-only match
                if (!matchedData) {
                    matchedData = dataLookup[muniKey];
                }

                // Try partial matching
                if (!matchedData) {
                    for (const key in dataLookup) {
                        const keyMuni = key.split('|')[0];
                        if (keyMuni && (keyMuni.includes(muniKey) || muniKey.includes(keyMuni))) {
                            matchedData = dataLookup[key];
                            break;
                        }
                    }
                }
            } else {
                const provinceName = feature.properties.PROVINCE || feature.properties.NAME_1 || '';
                displayName = provinceName;
                const normalizedName = normalizeName(provinceName);

                matchedData = dataLookup[normalizedName];

                // Try partial matching
                if (!matchedData) {
                    for (const key in dataLookup) {
                        if (key.includes(normalizedName) || normalizedName.includes(key)) {
                            matchedData = dataLookup[key];
                            break;
                        }
                    }
                }
            }

            let fillColor = '#e0e0e0';
            let tooltipContent = displayName;

            if (matchedData) {
                const intensity = matchedData.count / maxCount;
                fillColor = getHeatmapColor(intensity);
                tooltipContent = `<div class="map-tooltip-content">
                    <div class="province-name">${escapeHtml(displayName)}</div>
                    <div class="province-count">${matchedData.count.toLocaleString()} records</div>
                </div>`;
            }

            layer.setStyle({
                fillColor: fillColor,
                weight: level === 'municipality' ? 0.5 : 1,
                opacity: 1,
                color: '#fff',
                fillOpacity: 0.8
            });

            layer.unbindTooltip();
            layer.bindTooltip(tooltipContent, {
                permanent: false,
                direction: 'auto',
                className: 'map-tooltip'
            });
        });
    }

    // Normalize name for matching (works for both provinces and municipalities)
    function normalizeName(name) {
        if (!name) return '';
        return name.toLowerCase()
            .replace(/\s+/g, '')
            .replace(/[^a-z]/g, '')
            .replace(/province$/i, '')
            .replace(/city$/i, '')
            .replace(/municipality$/i, '')
            .replace(/^the/, '');
    }

    // Get heatmap color based on intensity (0-1)
    function getHeatmapColor(intensity) {
        // Gradient from light green to dark green
        const colors = [
            '#c8e6c9', // 0-20%
            '#81c784', // 20-40%
            '#66bb6a', // 40-60%
            '#43a047', // 60-80%
            '#2e7d32'  // 80-100%
        ];

        const index = Math.min(Math.floor(intensity * 5), 4);
        return colors[index];
    }

    // Initialize map on page load
    initMap();

    // Data source selection
    $('.data-source-btn').on('click', function() {
        $('.data-source-btn').removeClass('active');
        $(this).addClass('active');
        currentSource = $(this).data('source');

        // Show/hide product filter (only for orders)
        if (currentSource === 'orders') {
            $('#productFilterCol').show();
        } else {
            $('#productFilterCol').hide();
            $('#productFilter').val('');
        }

        // Show/hide store filter (not applicable for clients)
        if (currentSource === 'clients') {
            $('#storeFilter').prop('disabled', true);
        } else {
            $('#storeFilter').prop('disabled', false);
        }
    });

    // Generate heatmap
    $('#generateBtn').on('click', function() {
        loadHeatmapData();
    });

    // Clear filters
    $('#clearFiltersBtn').on('click', function() {
        $('#startDate').val('');
        $('#endDate').val('');
        $('#storeFilter').val('');
        $('#productFilter').val('');
        $('#provinceFilter').val('');
    });

    // Export
    $('#exportBtn').on('click', function() {
        if (!heatmapData) {
            toastr.warning('Please generate data first');
            return;
        }

        const params = new URLSearchParams({
            source: currentSource,
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val(),
            store_id: $('#storeFilter').val(),
            product_id: $('#productFilter').val(),
            province: $('#provinceFilter').val()
        });

        window.location.href = '{{ route("ecom-reports.heatmap.export") }}?' + params.toString();
    });

    function loadHeatmapData() {
        $('#provinceLoading, #municipalityLoading').removeClass('d-none');
        $('#generateBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Loading...');

        $.ajax({
            url: '{{ route("ecom-reports.heatmap.data") }}',
            method: 'GET',
            data: {
                source: currentSource,
                start_date: $('#startDate').val(),
                end_date: $('#endDate').val(),
                store_id: $('#storeFilter').val(),
                product_id: $('#productFilter').val(),
                province: $('#provinceFilter').val()
            },
            success: function(response) {
                if (response.success) {
                    heatmapData = response.data;
                    renderHeatmap(response.data, response.total);
                    $('#exportBtn').prop('disabled', false);
                } else {
                    toastr.error('Failed to load data');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred');
            },
            complete: function() {
                $('#provinceLoading, #municipalityLoading').addClass('d-none');
                $('#generateBtn').prop('disabled', false).html('<i class="bx bx-refresh me-1"></i>Generate');
            }
        });
    }

    function renderHeatmap(data, total) {
        // Update stats
        $('#totalRecords').text(total.toLocaleString());
        $('#totalProvinces').text(data.byProvince.length);
        $('#totalMunicipalities').text(data.byMunicipality.length);
        $('#provinceCount').text(data.byProvince.length + ' provinces');
        $('#municipalityCount').text(data.byMunicipality.length + ' municipalities');

        // Update map colors based on current level
        if (currentMapLevel === 'municipality') {
            updateMapColors(data.byMunicipality, 'municipality');
        } else {
            updateMapColors(data.byProvince, 'province');
        }

        // Render province ranking
        if (data.byProvince.length === 0) {
            $('#provinceRanking').html(`
                <div class="empty-state">
                    <i class="bx bx-search-alt"></i>
                    <p class="mb-0 text-dark">No data found</p>
                    <small>Try adjusting your filters</small>
                </div>
            `);
        } else {
            const maxCount = data.byProvince[0]?.count || 1;
            let html = '';

            data.byProvince.slice(0, 15).forEach((province, index) => {
                const percentage = (province.count / maxCount) * 100;
                let rankClass = '';
                if (index === 0) rankClass = 'gold';
                else if (index === 1) rankClass = 'silver';
                else if (index === 2) rankClass = 'bronze';

                html += `
                    <div class="province-item">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="province-rank ${rankClass}">${index + 1}</div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark">${escapeHtml(province.name)}</strong>
                                    <span class="badge bg-primary">${province.count.toLocaleString()}</span>
                                </div>
                                <div class="province-bar" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                    </div>
                `;
            });

            if (data.byProvince.length > 15) {
                html += `<div class="text-center text-muted mt-2"><small>+ ${data.byProvince.length - 15} more provinces</small></div>`;
            }

            $('#provinceRanking').html(html);
        }

        // Render municipality table
        if (data.byMunicipality.length === 0) {
            $('#municipalityTable').html(`
                <div class="empty-state">
                    <i class="bx bx-building-house"></i>
                    <p class="mb-0 text-dark">No municipality data</p>
                    <small>Try adjusting your filters</small>
                </div>
            `);
        } else {
            let tableHtml = `
                <table class="table table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>#</th>
                            <th>Province</th>
                            <th>Municipality/City</th>
                            <th class="text-end">Count</th>
                            <th class="text-end">Value</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            data.byMunicipality.slice(0, 100).forEach((muni, index) => {
                const valueDisplay = currentSource === 'orders' || currentSource === 'refunds'
                    ? '₱' + parseFloat(muni.totalValue).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})
                    : muni.count;

                tableHtml += `
                    <tr>
                        <td class="text-secondary">${index + 1}</td>
                        <td class="text-dark">${escapeHtml(muni.province)}</td>
                        <td><strong class="text-dark">${escapeHtml(muni.municipality)}</strong></td>
                        <td class="text-end"><span class="badge bg-info">${muni.count.toLocaleString()}</span></td>
                        <td class="text-end text-dark">${valueDisplay}</td>
                    </tr>
                `;
            });

            tableHtml += '</tbody></table>';

            if (data.byMunicipality.length > 100) {
                tableHtml += `<div class="text-center text-muted py-2"><small>Showing top 100 of ${data.byMunicipality.length} municipalities</small></div>`;
            }

            $('#municipalityTable').html(tableHtml);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return $('<div>').text(text).html();
    }

    // Set default date range (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date(today);
    thirtyDaysAgo.setDate(today.getDate() - 30);

    $('#endDate').val(today.toISOString().split('T')[0]);
    $('#startDate').val(thirtyDaysAgo.toISOString().split('T')[0]);

    // Initial load
    loadHeatmapData();
});
</script>
@endsection
