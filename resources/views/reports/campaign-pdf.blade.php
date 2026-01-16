<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Campaign Report - {{ $campaign->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #333; }
        h1 { font-size: 20pt; color: #1e40af; margin-bottom: 5px; }
        h2 { font-size: 14pt; color: #1e40af; margin-top: 20px; margin-bottom: 10px; border-bottom: 2px solid #1e40af; }
        h3 { font-size: 12pt; color: #334155; margin-top: 15px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th { background: #e0e7ff; color: #1e40af; padding: 8px; text-align: left; border: 1px solid #c7d2fe; font-weight: bold; }
        td { padding: 6px 8px; border: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 10px; border-bottom: 3px solid #1e40af; }
        .metadata { background: #f1f5f9; padding: 10px; margin: 15px 0; border-radius: 4px; }
        .stat-grid { display: table; width: 100%; }
        .stat-item { display: table-cell; width: 16.66%; padding: 5px; }
        .stat-label { font-size: 8pt; color: #64748b; }
        .stat-value { font-size: 12pt; font-weight: bold; color: #1e40af; }
        .footer { text-align: center; font-size: 8pt; color: #64748b; margin-top: 30px; padding-top: 10px; border-top: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>{{ $campaign->name }}</h1>
        <p style="color: #64748b; margin: 0;">Campaign Report</p>
        <p style="font-size: 9pt; color: #64748b; margin: 5px 0 0 0;">
            Generated: {{ $generated_at->format('F j, Y \a\t g:i A') }}
        </p>
    </div>

    {{-- Campaign Metadata --}}
    <h2>Campaign Overview</h2>
    <div class="metadata">
        <table style="border: none;">
            <tr>
                <td style="border: none; width: 25%; font-weight: bold;">Status:</td>
                <td style="border: none;">{{ ucfirst($campaign->status) }}</td>
                <td style="border: none; width: 25%; font-weight: bold;">Owner:</td>
                <td style="border: none;">{{ $campaign->user->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="border: none; font-weight: bold;">Created:</td>
                <td style="border: none;">{{ $campaign->created_at->format('M d, Y') }}</td>
                <td style="border: none; font-weight: bold;">Data Points:</td>
                <td style="border: none;">{{ $metadata['data_point_count'] ?? 0 }}</td>
            </tr>
        </table>
    </div>

    @if($campaign->description)
    <p><strong>Description:</strong> {{ $campaign->description }}</p>
    @endif

    {{-- Quality Assurance Statistics --}}
    @if($qa_stats)
    <h2>Data Quality</h2>
    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-label">Approved</div>
            <div class="stat-value" style="color: #059669;">{{ $qa_stats->approved_count ?? 0 }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Pending</div>
            <div class="stat-value" style="color: #d97706;">{{ $qa_stats->pending_count ?? 0 }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Draft</div>
            <div class="stat-value" style="color: #64748b;">{{ $qa_stats->draft_count ?? 0 }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Rejected</div>
            <div class="stat-value" style="color: #dc2626;">{{ $qa_stats->rejected_count ?? 0 }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Avg GPS Accuracy</div>
            <div class="stat-value">{{ number_format($qa_stats->avg_accuracy_meters ?? 0, 1) }}m</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Satellite Enriched</div>
            <div class="stat-value">{{ $qa_stats->satellite_enriched_count ?? 0 }}</div>
        </div>
    </div>
    @endif

    {{-- Survey Zones --}}
    @if($zones->count() > 0)
    <h2>Survey Zones</h2>
    <table>
        <thead>
            <tr>
                <th>Zone Name</th>
                <th>Description</th>
                <th>Area (km²)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($zones as $zone)
            <tr>
                <td>{{ $zone->name }}</td>
                <td>{{ $zone->description ?? 'N/A' }}</td>
                <td>{{ number_format($zone->area_km2, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Statistical Summary --}}
    @if(count($statistics) > 0)
    <h2>Statistical Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Metric</th>
                <th>Count (n)</th>
                <th>Min</th>
                <th>Max</th>
                <th>Average</th>
                <th>Median</th>
                <th>Std Dev (σ)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statistics as $name => $stats)
            <tr>
                <td><strong>{{ $name }}</strong></td>
                <td>{{ $stats['count'] }}</td>
                <td>{{ number_format($stats['min'], 2) }} {{ $stats['unit'] }}</td>
                <td>{{ number_format($stats['max'], 2) }} {{ $stats['unit'] }}</td>
                <td>{{ number_format($stats['average'], 2) }} {{ $stats['unit'] }}</td>
                <td>{{ number_format($stats['median'], 2) }} {{ $stats['unit'] }}</td>
                <td>{{ number_format($stats['std_dev'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Satellite Index Summary --}}
    @if(isset($metadata['satellite_indices']))
    <h2>Satellite Index Coverage</h2>
    <p style="font-size: 9pt; color: #64748b; margin-bottom: 10px;">
        Data from Sentinel-2 (Copernicus Data Space) at 10-20m resolution
    </p>
    <table>
        <thead>
            <tr>
                <th>Index</th>
                <th>Description</th>
                <th>Use Case</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>NDVI</strong></td>
                <td>Normalized Difference Vegetation Index</td>
                <td style="font-size: 8pt;">General vegetation health</td>
            </tr>
            <tr>
                <td><strong>NDMI</strong></td>
                <td>Normalized Difference Moisture Index</td>
                <td style="font-size: 8pt;">Soil moisture content</td>
            </tr>
            <tr>
                <td><strong>NDRE</strong></td>
                <td>Red Edge Index (R²=0.80-0.90)</td>
                <td style="font-size: 8pt;">Chlorophyll content</td>
            </tr>
            <tr>
                <td><strong>EVI</strong></td>
                <td>Enhanced Vegetation Index (R²=0.75-0.85)</td>
                <td style="font-size: 8pt;">Dense canopy LAI, FAPAR</td>
            </tr>
            <tr>
                <td><strong>MSI</strong></td>
                <td>Moisture Stress Index (R²=0.70-0.80)</td>
                <td style="font-size: 8pt;">Drought stress levels</td>
            </tr>
            <tr>
                <td><strong>SAVI</strong></td>
                <td>Soil-Adjusted Vegetation (R²=0.70-0.80)</td>
                <td style="font-size: 8pt;">Sparse vegetation LAI</td>
            </tr>
            <tr>
                <td><strong>GNDVI</strong></td>
                <td>Green Vegetation Index (R²=0.75-0.85)</td>
                <td style="font-size: 8pt;">Chlorophyll (sensitive)</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- Methodology --}}
    <h2>Methodology</h2>
    <p><strong>Coordinate System:</strong> {{ $metadata['coordinate_system'] ?? 'WGS84 (EPSG:4326)' }}</p>
    <p><strong>GPS Accuracy:</strong> All measurements with accuracy <10m (consumer GPS devices)</p>
    <p><strong>Satellite Data:</strong> Sentinel-2 Level-2A surface reflectance products</p>
    <p><strong>Statistical Methods:</strong> 95% confidence intervals calculated when n ≥ 3</p>

    {{-- Footer --}}
    <div class="footer">
        <p>EcoSurvey - Environmental Field Data Collection Platform</p>
        <p>Report generated on {{ $generated_at->format('F j, Y') }} | Page 1</p>
    </div>
</body>
</html>
