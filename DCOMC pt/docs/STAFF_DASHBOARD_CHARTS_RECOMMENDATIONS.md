# Staff Dashboard Charts: Design & Enhancement Recommendations

This document provides a concrete guide to improve the five Enrollment & classification charts (Approved/Pending/Rejected donut, Student type, Gender, Financial, Block assignments) for a professional, institutional analytics dashboard. It is based on the current implementation and aligned with DCOMC’s Certificate of Registration (COR) and formal report style.

---

## 1. Overall Layout

### Current state
- Grid: 1 col (mobile) → 2 cols (sm) → 3 cols (lg); 3+2 row pattern.
- Cards: `rounded-xl`, `shadow-sm`, `border border-gray-200`, header `bg-gray-50/80`.
- Chart area: fixed `h-36` / `min-h-[140px]` in a `p-4` container.

### Recommendations

| Priority | Recommendation | Rationale |
|----------|----------------|-----------|
| High | **Add a subtle card “document” frame** | Use a 1px border (e.g. `#E5E7EB`) and optional very light inner border or divider under the title to echo COR’s bordered, sectioned layout. |
| High | **Unify card min-height** | Set one `min-height` (e.g. 220–240px) for all five cards so rows align and the block feels like one “report panel.” |
| Medium | **Section label** | Add a small, uppercase, gray label above “Enrollment & classification” (e.g. “Analytics”) to mirror “Office of the College Registrar”–style hierarchy. |
| Medium | **Consistent gutters** | Use the same horizontal padding as the rest of the staff dashboard (e.g. match hero/content padding) so the chart block is visually part of one layout. |
| Low | **Optional: numbered chart titles** | For a formal report feel, use “1. Approved / Pending / Rejected”, “2. Student type”, etc., similar to numbered sections in certificates. |

---

## 2. Color Palette

### Current state
- **Donut:** Approved `#1E40AF`, Pending `#F59E0B`, Rejected `#dc2626`.
- **Pies:** DCOMC blue scale (`#1E40AF`, `#3B82F6`, `#60A5FA`, …) plus grays and accent colors (Block: greens, purples, etc.).

### Recommendations

| Priority | Recommendation | Rationale |
|----------|----------------|-----------|
| High | **Semantic consistency** | Use the same status colors everywhere: e.g. Approved/success = one green or blue; Pending/warning = one amber; Rejected/error = one red. Donut already does this; keep and reuse in any status badges or other charts. |
| High | **Accessibility (contrast)** | Ensure all segment colors meet WCAG 2.1 AA (4.5:1 for normal text, 3:1 for large shapes). Test `#1E40AF`, `#F59E0B`, `#dc2626`, and grays on white; darken or add borders if needed. |
| High | **Limit palette per chart** | Prefer 3–5 distinct colors per chart. For Block (many segments), use a single hue with stepped lightness (e.g. blue 900 → 100) plus one neutral gray for “Other” to avoid a rainbow look. |
| Medium | **DCOMC Blue as primary** | Keep `#1E40AF` as the main institutional color for the “positive” or primary category (e.g. Approved, first segment). Align with COR header color. |
| Medium | **Gray scale for neutrals** | Use one gray scale (e.g. 700/500/400) for secondary or “other” categories so the dashboard feels cohesive and professional. |
| Low | **Optional color key** | Add a one-line “Color key” under the section title (e.g. “Blue = Approved, Amber = Pending, Red = Rejected”) for the donut to support quick scanning. |

---

## 3. Typography

### Current state
- Card titles: `font-heading` (Figtree), `text-sm font-bold text-gray-800`.
- Subtitles: `font-data` (Roboto), `text-xs text-gray-500`.

### Recommendations

| Priority | Recommendation | Rationale |
|----------|----------------|-----------|
| High | **Chart.js font family** | Set Chart.js `options.plugins.legend.labels.font.family` and `tooltip.bodyFont` to match the app (e.g. `'Roboto', sans-serif`) so labels and tooltips match the rest of the UI. |
| High | **Legend and label size** | Use at least 12px for legend and axis labels so they remain readable when the card is small; avoid going below 11px. |
| Medium | **Title hierarchy** | Keep card title as the main identifier; subtitle can be slightly smaller or lighter (current `text-xs text-gray-500` is fine). Ensure title is one line or truncate with `title` attribute for full text. |
| Medium | **Number formatting in tooltips** | In tooltips, show counts with locale formatting (e.g. 1,234) and always show percentage where relevant (e.g. “1,234 (45.2%)”) for quick comparison. |
| Low | **Optional: serif for “report” feel** | For a certificate-like tone, consider a serif (e.g. Georgia or “Times New Roman”) only for the section title “Enrollment & classification,” keeping the rest in Roboto/Figtree for clarity. |

---

## 4. Data Representation

### Current state
- **Approved/Pending/Rejected:** Doughnut (3 segments).
- **Student type, Gender, Financial, Block:** Pie charts (multiple segments).

### Recommendations by chart

| Chart | Current type | Recommendation | Rationale |
|-------|--------------|----------------|-----------|
| **Approved / Pending / Rejected** | Doughnut | **Keep donut** or use **horizontal bar** | Donut is good for 3-part composition; if you want easier comparison of exact values, a horizontal bar chart with one bar per status (and counts on the axis) is very clear. |
| **Student type** | Pie | **Keep pie** or **donut with center total** | Pie is fine for 3–5 categories. Option: donut with total count in the center for “Total enrollees by type.” |
| **Gender** | Pie | **Keep pie** or **donut** | Few segments; both work. Donut can look slightly more modern. |
| **Financial** | Pie | **Keep pie**; consider **bar if many classes** | If income classes are 5+, a horizontal bar chart makes “highest vs lowest” easier to read than small pie slices. |
| **Block assignments** | Pie (many segments) | **Prefer horizontal bar or donut + “Top N”** | With many blocks, pie slices become hard to compare. Use horizontal bar (blocks on Y, count on X) or a donut showing “Top 5 blocks” + “Others” for clarity. |

### General
- **Avoid more than 5–6 pie segments** in one chart; aggregate the rest as “Other” or switch to bar.
- **Show totals** where helpful (e.g. “Total: 1,234” in card header or donut center) to support quick context.

---

## 5. Interactive Elements

### Current state
- Chart.js default tooltips; no custom hover or filters in the card UI.

### Recommendations

| Priority | Recommendation | Rationale |
|----------|----------------|-----------|
| High | **Rich tooltips** | For every segment: show **label**, **count**, and **percentage of total**. Block chart already does this; apply the same pattern to Approved/Pending/Rejected, Student type, Gender, and Financial. |
| High | **Hover emphasis** | Use Chart.js hover style (e.g. slightly lighter border or scale) so the active segment is obvious; keep animation subtle (e.g. 200ms). |
| Medium | **Card-level hover** | Add a very subtle card hover state (e.g. `shadow-md` or border color change) to show the block is interactive. |
| Medium | **“View report” per card** | Add a small “View in report →” or “Details” link per card (or one for the section) linking to the Reports page with a filter or tab that opens the relevant breakdown. |
| Low | **Optional filters** | If the dashboard supports AY/semester filters, ensure the chart section reflects the same filters and show a one-line “As of [date]” or “For [AY, Semester]” near the section title. |

---

## 6. Chart-Specific Refinements

### Donut / Pie charts

| Recommendation | Implementation note |
|----------------|----------------------|
| **Center total in donut** | For Approved/Pending/Rejected, use Chart.js `plugins` (e.g. custom plugin or chartjs-plugin-datalabels) to draw the total (approved + pending + rejected) in the donut center. |
| **Consistent border** | Use `borderWidth: 1` and `borderColor: '#fff'` (or background color) so segments are clearly separated, especially in print. |
| **Legend position** | Keep legend at bottom; if space is tight, use `legend.labels.boxWidth: 10` and `padding: 8` so legend doesn’t overlap the chart. |
| **Order segments** | Sort by value descending (largest first) so the most important slice is at the top or in a consistent position. |
| **“No data” state** | When a chart has no data, show a single gray segment with “No data” and hide or simplify the legend. |

### Bar / line charts (if you introduce them)

| Recommendation | Implementation note |
|----------------|----------------------|
| **Axis labels** | Always label axes (e.g. “Count” on X, category name on Y). Use `scale.*.title.display: true` and a short title. |
| **Grid** | Use light horizontal grid lines (e.g. `color: 'rgba(0,0,0,0.06)'`); avoid heavy grid. |
| **Data labels** | For bar charts, consider showing the value at the end of each bar (Chart.js datalabels plugin or custom draw). |
| **Line charts** | Use a single DCOMC blue for the main series; dashed or lighter line for comparison if needed. |

---

## 7. Comparison to Institutional Document (COR / Certificate Style)

DCOMC’s Certificate of Registration and formal reports use:

- **Clear hierarchy:** Republic → College name → Office → Document title → Sections.
- **Bordered, sectioned layout:** Tables and blocks with simple borders (`1px solid`), no decorative clutter.
- **Serif for authority:** Times New Roman for body and titles.
- **Institutional blue:** `#1E40AF` for college name and key headings.
- **Neutral text:** Black or dark gray (`#1f2937`, `#374151`) for body; one accent color.

To align the charts with that authority and clarity:

| Principle | Application to charts |
|----------|------------------------|
| **Hierarchy** | One clear section title (“Enrollment & classification”), then card titles, then subtitles. No competing visual weight. |
| **Borders and sections** | Card borders and a clear header strip (current gray header) mirror “section blocks” in the COR. Keep borders light and consistent. |
| **Restraint** | Avoid decorative gradients or flashy animations. Use solid fills, subtle hovers, and a limited palette. |
| **Institutional blue** | Use DCOMC blue for the primary metric (e.g. Approved) and for links (“Reports →”); keep the rest of the palette muted. |
| **Readability** | Sufficient font size and contrast so that, like the COR, the dashboard can be read quickly and cited. |
| **Print-friendly** | Ensure charts and cards print cleanly (e.g. avoid heavy shadows; use borders and solid colors). |

---

## 8. Implementation Checklist (Concrete Next Steps)

- [ ] **Layout:** Set a single `min-height` for all five cards; add optional section subtitle (“Analytics” or “As of [date]”).  
- [ ] **Colors:** Audit all segment colors for WCAG AA; standardize “Approved/Pending/Rejected” semantics across the app; reduce Block pie to a blue scale + gray.  
- [ ] **Typography:** Set Chart.js `legend.labels.font` and `tooltip` font to Roboto; ensure minimum 12px.  
- [ ] **Tooltips:** Add count + percentage to every chart’s tooltip (mirror Block chart).  
- [ ] **Donut:** Add center total (approved + pending + rejected) via plugin or custom draw.  
- [ ] **Block chart:** Consider converting to horizontal bar or “Top N + Others” if segment count is high.  
- [ ] **Borders:** Add `borderWidth: 1`, `borderColor: '#fff'` to pie/donut datasets for separation.  
- [ ] **Empty state:** Handle zero/no data with a single “No data” segment and clear legend.  
- [ ] **Links:** Add “Reports →” or “Details” at section or card level linking to the corresponding report view.  
- [ ] **Print:** Test print view; reduce or remove shadow; ensure labels and legends are visible.

---

This guide is intended to turn the five Enrollment & classification charts into clear, consistent, and professional analytics visuals that fit the institutional style of DCOMC’s formal documents while remaining accessible and easy to use on screen and in print.
