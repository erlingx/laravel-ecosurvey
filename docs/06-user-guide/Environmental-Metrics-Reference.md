# Environmental Metrics Reference

**Measurement Units & Ranges**

---

## Temperature

**Unit:** °C (Celsius)  
**Typical Range:** -40°C to +50°C  
**Urban Range:** -10°C to +40°C  
**Sensor Accuracy:** ±0.5°C (calibrated)

**What It Measures:**
- Air temperature at sensor height
- Urban heat island effects
- Microclimate variations

**Field Tips:**
- Shade sensor from direct sunlight
- Measure at consistent height (1.5-2m)
- Avoid heat sources (buildings, pavement)

---

## Humidity

**Unit:** % RH (Relative Humidity)  
**Range:** 0-100%  
**Typical Urban:** 30-80%  
**Sensor Accuracy:** ±3% RH

**What It Measures:**
- Moisture content in air
- Comfort index
- Condensation risk

**Field Tips:**
- Pair with temperature for full picture
- Morning/evening shows highest variability
- Near water bodies = higher values

---

## Noise Level

**Unit:** dB (Decibels)  
**Range:** 30-120 dB  
**Urban Range:** 40-90 dB  
**Sensor Type:** SPL meter (A-weighted)

**Reference Levels:**
- 30 dB: Whisper
- 50 dB: Normal conversation
- 70 dB: Traffic
- 85 dB: Hearing damage threshold (8hr exposure)
- 120 dB: Pain threshold

**What It Measures:**
- Sound pressure level
- Noise pollution
- Acoustic comfort

**Field Tips:**
- Take 30-second average (not peak)
- Note wind conditions (causes noise)
- Avoid measurements near single loud events

---

## Air Quality Index (AQI)

**Unit:** AQI (0-500 scale)  
**Range:** 0-500  
**Health Breakpoints:**
- 0-50: Good (green)
- 51-100: Moderate (yellow)
- 101-150: Unhealthy for sensitive groups (orange)
- 151-200: Unhealthy (red)
- 201-300: Very unhealthy (purple)
- 301-500: Hazardous (maroon)

**What It Measures:**
- PM2.5, PM10, O₃, NO₂, SO₂, CO
- Overall air quality
- Health risk level

**Field Tips:**
- Real-time sensors vs satellite estimates
- Higher in mornings (traffic) and inversions
- Lower on windy/rainy days

---

## PM2.5 & PM10

**Units:** µg/m³ (micrograms per cubic meter)

**PM2.5 (Fine Particles):**
- Size: ≤2.5 micrometers
- WHO Guideline: 5 µg/m³ (annual), 15 µg/m³ (24hr)
- Sources: Combustion, traffic, industry

**PM10 (Coarse Particles):**
- Size: ≤10 micrometers
- WHO Guideline: 15 µg/m³ (annual), 45 µg/m³ (24hr)
- Sources: Dust, pollen, road wear

**Health Impact:**
- PM2.5: Penetrates deep into lungs
- PM10: Respiratory irritation

---

## Soil Moisture

**Units:** % VWC (Volumetric Water Content)  
**Range:** 0-100% (typically 10-50%)  
**Measurement Depth:** 0-10cm (typical)

**Field Capacity Ranges:**
- Sand: 10-20%
- Loam: 25-35%
- Clay: 35-45%

**What It Measures:**
- Water content in soil
- Plant water availability
- Drought stress indicator

**Validation:**
- Correlates with NDMI (satellite)
- Correlates inversely with MSI (satellite)

---

## GPS Accuracy

**Unit:** meters (m)  
**Consumer Devices:** ±5-10m  
**Professional GPS:** ±1-3m  
**RTK GPS:** ±0.01-0.02m

**Factors Affecting Accuracy:**
- Satellite visibility (open sky = better)
- Urban canyon effect (buildings block signal)
- Atmospheric conditions
- Device quality

**Field Tips:**
- Wait for GPS lock (4+ satellites)
- Check accuracy indicator before recording
- Avoid measurements under heavy tree cover

---

## Measurement Best Practices

**Temporal Alignment:**
- 0-3 days from satellite: Excellent correlation
- 4-7 days: Good correlation
- 8-14 days: Acceptable correlation
- 15+ days: Poor correlation

**Sample Size:**
- Minimum n = 3 for statistics
- n ≥ 30 for normal distribution assumption
- More samples = lower uncertainty

**Calibration:**
- Calibrate sensors before campaigns
- Cross-validate with reference stations
- Document sensor models and serial numbers

**Documentation:**
- Record weather conditions
- Note unusual circumstances
- Photograph measurement locations
- Track sensor height/position

---

## Statistical Confidence

**Standard Error:** Decreases with √n  
**95% Confidence Interval:** Requires n ≥ 3  
**Margin of Error:** ±1.96 × (σ/√n)

**Example:**
- 10 measurements: ±0.62 × σ
- 30 measurements: ±0.36 × σ
- 100 measurements: ±0.20 × σ

---

## Data Quality Flags

**Good Quality:**
- GPS accuracy <10m
- Clear weather
- Calibrated sensors
- Proper measurement technique

**Questionable Quality:**
- GPS accuracy 10-20m
- Partial cloud cover
- Uncalibrated sensors
- Suboptimal conditions

**Poor Quality:**
- GPS accuracy >20m
- Extreme weather
- Sensor malfunction
- Measurement errors

---

## References

**Environmental Monitoring:**
- WHO Air Quality Guidelines (2021)
- EPA Noise Pollution Standards (2020)
- WMO Meteorological Measurement Guide (2018)

**Statistical Methods:**
- ISO 5725: Accuracy (trueness and precision)
- GUM: Guide to Expression of Uncertainty in Measurement

**GPS & Positioning:**
- GPS.gov: Official U.S. Government GPS Information
- PDOP/HDOP Standards: <5 (good), 5-10 (moderate), >10 (poor)
