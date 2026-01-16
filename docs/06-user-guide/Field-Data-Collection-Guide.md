# Field Data Collection Guide

**Scientific measurement principles for accurate field data**

---

## GPS Position Accuracy

**Wait for Lock:**
- Minimum 4 satellites visible
- Accuracy indicator <10m (green)
- Stable reading (not jumping)

**Avoid:**
- Dense tree canopy
- Urban canyons (tall buildings)
- Indoor measurements

**Best Practice:**
- Open sky view
- Wait 30 seconds after lock
- Record accuracy value

---

## Temperature Measurement

**Sensor Position:**
- 1.5-2m above ground (standard)
- Shaded from direct sun
- Away from heat sources

**Timing:**
- Morning: Coolest readings
- Afternoon: Warmest readings
- Evening: Intermediate

**Avoid:**
- Direct sunlight on sensor
- Near buildings/pavement (heat radiation)
- Windy conditions (rapid changes)

---

## Humidity Measurement

**Pair with Temperature:**
- Always record both
- Calculate dew point if needed
- Note comfort levels

**Timing:**
- Morning: Highest RH
- Afternoon: Lowest RH
- Near water: Higher values

---

## Noise Measurement

**Duration:**
- 30-second minimum
- Use average, not peak
- Note sound sources

**Position:**
- 1.5m height
- Away from reflective surfaces
- Microphone unobstructed

**Document:**
- Traffic conditions
- Events (construction, sirens)
- Wind speed

---

## Air Quality Measurement

**Sensor Warmup:**
- 2-5 minutes before recording
- Check sensor status
- Stable baseline

**Location:**
- Breathing zone height (1.5m)
- Representative of area
- Avoid direct exhaust

---

## Photo Documentation

**What to Capture:**
- Measurement location overview
- Sensor position
- Surrounding context
- Notable features

**Photo Metadata:**
- Auto-geotagged
- Timestamp recorded
- Links to data point

---

## Weather Conditions

**Document:**
- Cloud cover (clear/partly/overcast)
- Wind (calm/light/strong)
- Precipitation (none/rain/snow)
- Temperature (cold/mild/hot)

**Impact on Data:**
- Wind: Affects temperature, noise
- Clouds: Affects temperature, solar radiation
- Rain: Affects AQI, noise patterns

---

## Measurement Sequence

**Recommended Order:**
1. Navigate to location
2. Wait for GPS lock
3. Check accuracy
4. Take photo (auto-geotagged)
5. Wait for sensor stabilization
6. Record measurement
7. Add notes if unusual
8. Submit

---

## Quality Checks

**Before Submitting:**
- GPS accuracy <10m
- Sensor reading reasonable
- Photo attached (if applicable)
- Notes added (if unusual)

**Red Flags:**
- GPS accuracy >20m
- Extreme values (outliers)
- Sensor error messages
- Unstable readings

---

## Common Mistakes

**GPS Issues:**
- ❌ Not waiting for lock
- ❌ Indoor measurements
- ❌ Moving during reading
- ✅ Wait, verify accuracy, stay still

**Temperature Issues:**
- ❌ Sensor in direct sun
- ❌ Near heat sources
- ❌ Too close to ground
- ✅ Shade sensor, standard height

**Noise Issues:**
- ❌ Single event recorded
- ❌ Too short duration
- ❌ Wind on microphone
- ✅ Average over time, shield sensor

---

## Data Validation

**Satellite Correlation:**
- Best: 0-3 days difference
- Good: 4-7 days
- Acceptable: 8-14 days
- Poor: 15+ days

**Statistical Validity:**
- Minimum n = 3 per day
- n ≥ 30 for robust statistics
- More data = better CI

---

## Safety Considerations

**Personal Safety:**
- Avoid dangerous areas
- Traffic awareness
- Weather hazards
- Stay in groups if needed

**Equipment Safety:**
- Protect from rain
- Avoid extreme temperatures
- Secure during transport
- Regular calibration

---

## Reference Values

**Temperature (Urban):**
- Winter: -10°C to +10°C
- Summer: +15°C to +35°C
- Extremes: Flag if <-20°C or >40°C

**Humidity:**
- Comfortable: 40-60% RH
- Dry: <30% RH
- Humid: >70% RH

**Noise (Urban):**
- Quiet residential: 40-50 dB
- Normal residential: 50-60 dB
- Busy street: 70-80 dB
- Highway: 80-90 dB

**AQI:**
- Good: 0-50
- Moderate: 51-100
- Unhealthy: 101-150
- Very unhealthy: >150
