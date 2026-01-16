# Trend Analysis - User Guide

**Feature:** Time Series Analytics (Phase 5)  
**Access:** `/analytics/trends`

---

## Quick Start

1. Select **Metric** (required)
2. (Optional) Filter by **Campaign**
3. Choose **Interval** (Daily/Weekly/Monthly)
4. View trend chart with 95% Confidence Interval

---

## Trend Chart

**Blue Line:** Average values over time  
**Blue Band:** 95% Confidence Interval (statistical uncertainty)  
**Dashed Line:** Overall average (reference)

**CI shown when:** Sample size n ≥ 3 (statistically valid)

---

## Interactive Controls

**Zoom:** Mouse wheel on chart  
**Pan:** Ctrl + drag left/right  
**Reset:** Click "Reset Zoom" button  
**Toggle Min/Max:** Show/hide range lines

---

## Tooltips

Hover over data points to see:
- Sample size (n)
- Standard deviation (σ)
- 95% CI range
- Min/Avg/Max values

---

## Distribution Histogram

Shows data spread with optimal binning (Freedman-Diaconis rule)

**X-axis:** Value ranges  
**Y-axis:** Frequency count

---

## Intervals

**Daily:** See day-to-day variations  
**Weekly:** Smooth short-term noise  
**Monthly:** Long-term trends

---

## Scientific Use

- Detect temporal patterns
- Quantify uncertainty
- Identify anomalies
- Publication-ready with CI bands
