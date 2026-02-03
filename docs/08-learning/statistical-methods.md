# Statistical Methods

## Outlier Detection

### IQR Method (Interquartile Range)
```
1. Sort values ascending
2. Q1 = 25th percentile
3. Q3 = 75th percentile
4. IQR = Q3 - Q1
5. Lower bound = Q1 - (1.5 × IQR)
6. Upper bound = Q3 + (1.5 × IQR)
7. Outlier if value < lower OR value > upper
```

**Percentile Calculation (Linear Interpolation)**
```php
function percentile($values, $p) {
    $index = ($p / 100) * (count($values) - 1);
    $lower = floor($index);
    $upper = ceil($index);
    $weight = $index - $lower;
    
    if ($lower == $upper) {
        return $values[$lower];
    }
    
    return $values[$lower] * (1 - $weight) 
         + $values[$upper] * $weight;
}
```

**Requirements:**
- Minimum 4 data points
- Data must be numeric
- Same campaign + metric

**Use case:** Robust to extreme outliers

---

### Z-Score Method
```
1. Calculate mean: μ = Σx / n
2. Calculate variance: σ² = Σ(x - μ)² / n
3. Calculate std dev: σ = √σ²
4. For each value: z = (x - μ) / σ
5. Outlier if |z| > threshold (typically 3.0)
```

**Implementation**
```php
function standardDeviation($values) {
    $mean = $values->avg();
    
    $variance = $values->map(function($x) use ($mean) {
        return pow($x - $mean, 2);
    })->avg();
    
    return sqrt($variance);
}

function zScore($value, $mean, $stdDev) {
    return ($value - $mean) / $stdDev;
}
```

**Requirements:**
- Minimum 3 data points
- Assumes normal distribution
- σ ≠ 0 (all values not identical)

**Threshold interpretation:**
- |z| > 3.0 → 99.7% confidence outlier
- |z| > 2.5 → 98.8% confidence
- |z| > 2.0 → 95.4% confidence

**Use case:** Data follows normal distribution

---

## Variance Calculation

### User vs Official Reading
```php
variance_% = ((user_value - official_value) / official_value) × 100
```

**Example:**
- User reading: 65
- Official station: 50
- Variance: (65 - 50) / 50 × 100 = 30%

**Thresholds:**
- |variance| < 15% → Acceptable
- 15% ≤ |variance| < 30% → Review
- |variance| ≥ 30% → Flag

---

## Distance Calculations

### Haversine Formula (Great-Circle Distance)
```php
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // Earth radius in meters
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $R * $c; // meters
}
```

**Accuracy:** ±0.5% for distances < 1000km

**Alternative:** PostGIS `ST_Distance(::geography, ::geography)`

---

## Correlation Analysis

### Pearson Correlation Coefficient (R)
```
r = Σ((x - x̄)(y - ȳ)) / √(Σ(x - x̄)² × Σ(y - ȳ)²)
```

**Interpretation:**
- r = 1.0 → Perfect positive correlation
- r = 0.5-0.7 → Moderate correlation
- r = 0.0 → No correlation
- r < 0 → Negative correlation

**R² (Coefficient of Determination):**
- R² = r²
- % of variance explained by model
- 0.80 → 80% of variance explained

**Application:**
```php
// Satellite NDVI vs ground biomass
$r_squared = 0.75; // 75% explained
// Remaining 25% = measurement error, other factors
```

---

## Aggregation Methods

### Spatial Averaging (Grid)
```
1. Divide area into grid cells (e.g., 100m × 100m)
2. Assign each point to cell
3. Calculate per-cell statistics:
   - Mean
   - Median
   - Std deviation
   - Count
4. Interpolate between cells
```

### Temporal Aggregation
```php
// Daily average
DataPoint::whereDate('collected_at', $date)
    ->avg('value')

// Moving average (7-day window)
function movingAverage($values, $window = 7) {
    $result = [];
    for ($i = 0; $i < count($values) - $window + 1; $i++) {
        $slice = array_slice($values, $i, $window);
        $result[] = array_sum($slice) / $window;
    }
    return $result;
}
```

---

## Quality Metrics

### Accuracy Assessment
```
MAE (Mean Absolute Error):
  MAE = Σ|predicted - actual| / n

RMSE (Root Mean Square Error):
  RMSE = √(Σ(predicted - actual)² / n)
  
Percentage Error:
  PE = |actual - predicted| / actual × 100%
```

**Application:**
```php
// User readings vs satellite indices
$mae = collect($dataPoints)->map(function($dp) {
    return abs($dp->value - $dp->predicted_from_ndvi);
})->avg();
```

### Confidence Intervals
```
CI = mean ± (z × σ / √n)

z-values:
  90% confidence → z = 1.645
  95% confidence → z = 1.960
  99% confidence → z = 2.576
```

---

## Pitfalls

### Outlier Detection
- **IQR:** Assumes symmetric distribution
- **Z-score:** Fails with non-normal data
- **Both:** Need minimum sample size
- **Don't auto-reject:** Only flag for review

### Division by Zero
```php
// Variance calculation
if ($officialValue == 0) {
    return 0.0; // Or null, or skip
}

// Standard deviation
if ($stdDev == 0) {
    return []; // All values identical
}
```

### Sample Size
```php
// Too few points → unreliable statistics
if ($dataPoints->count() < 4) {
    Log::info('Not enough data for IQR');
    return [];
}
```

### Precision Errors
```php
// Float comparison
if (abs($value1 - $value2) < 0.0001) {
    // Considered equal
}

// Use decimal cast in database
$table->decimal('value', 10, 2);
```

### Spatial Autocorrelation
- Nearby points are not independent
- Affects statistical tests
- Consider clustering/stratification

### Temporal Lag
- Satellite pass ≠ ground collection time
- Use ±7 day window for validation
- Account for seasonal changes
