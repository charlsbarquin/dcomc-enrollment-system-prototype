# System Analysis & Feature Status vs. FEATURES_TO_ADD_OR_IMPROVE.md

This document analyzes the codebase against **docs/FEATURES_TO_ADD_OR_IMPROVE.md** and states what is **finished**, what is **still needed**, and what was **verified** in code.

---

## 1. System Overview (Summary from Code)

| Area | Implementation |
|------|----------------|
| **Backend** | Laravel; roles: admin, registrar, student, staff, unifast, dean. Key models: User, FormResponse, Block, ScopeScheduleSlot, StudentCorRecord, CorScope, Assessment, EnrollmentForm, Program, Department, Subject, Room, etc. `StudentCorRecord` has `cor_source`: `schedule_by_program` (Dean) or `create_schedule` (Registrar Irregular). |
| **Flows** | Student enrollment (form → FormResponse → approve/reject/enroll); Admin/Registrar Student Status (filter by process_status, edit record); Schedule by Program (Registrar + Dean) → scope slots → Dean deploys COR → StudentCorRecord; Irregularities → Create Schedule (Registrar) → save/deploy → StudentCorRecord (`create_schedule`). Reports/Analytics with filters and CSV export; Block management; assessments (UniFast/Staff). |
| **COR by type** | Regular/Transferee/Returnee: Dean COR Archive (`schedule_by_program`). Irregular/Shifter: Registrar Irregular COR Archive (`create_schedule`). Student "View COR" button: blue for regular/transferee/returnee, yellow for irregular (StudentServicesController::cor, isIrregularType()). |
| **Connections** | Dashboard cards → Student Status with `process_status`; Form Responses approve/reject; AdminAccountController enroll/reject/needs-correction/delete application by FormResponse id. Orphan FormResponses (deleted user) are filtered and removed (RegistrarController, AdminAccountController::destroy). |

---

## 2. Finished Items (Verified in Code)

All 14 "Finished" items in FEATURES_TO_ADD_OR_IMPROVE.md were cross-checked; they match the codebase:

- **#1–4** Student Status process_status filter, color coding, enrollment list, dashboard card links, approval workflow — implemented in `AdminAccountController`, views, routes.
- **#5–6** Reports/Analytics (ReportController, AnalyticsController), two deans (department scoping) — implemented.
- **#7–9** Gender and block schedule, auto-save (student enrollment form), block naming and capacity — BlockAssignmentService, config `block_naming.php` (BEED number, others letter), `blocks.php` (50/capacity), student-enrollment-form auto-save.
- **#10–12** COR archive (Dean + Registrar Irregular), Schedule by Program, Create Schedule, student COR by type and button color — implemented in CorArchiveController, IrregularCorArchiveController, StudentServicesController, views.
- **#13** Dean Schedule by Program: room dropdown filtered by `available_room_ids` — DeanScheduleByScopeController and dean-schedule-by-scope view.
- **#14** Professor employment rules (Permanent 8–5/24 units, Part-time weekends, COS no restriction) — ProfessorWorkloadService, DeanScheduleByScopeController, DeanManageProfessorController.

---

## 3. Need to Add — Current Status

### High priority

| # | Area | Doc action | Code finding | Status |
|---|------|------------|--------------|--------|
| 1 | **Admin dashboard widgets** | (a) Enrollment Trends chart / link to Analytics; (b) Pie "Approved vs Not Approved"; (c) "Enrollees by program" widget + link to Reports; (d) AY and Semester dropdowns that filter dashboard widgets; (e) "Enrollees by location" (Daraga, Legazpi, Guinobatan) + link to Reports. | **admin.blade.php**: Has 4 process-status cards, 3 list widgets (Needs Correction, Approved Unscheduled, Scheduled Pending), one "Open Enrollment Analytics" link. **Missing**: trends chart, pie/donut, enrollees-by-program widget, enrollees-by-location widget, AY/semester filters for the dashboard. Reports already have `programBreakdown`, `locationBreakdown`, `daragaCount`, `legazpiCount`, `guinobatanCount` — only need to surface them on the dashboard and add filters. | **Need to add** |

### Medium priority

| # | Area | Doc action | Code finding | Status |
|---|------|------------|--------------|--------|
| 2 | **Registrar Schedule: room dropdown** | Use `availableRooms` API so room dropdown per slot excludes occupied rooms, or confirm UI calls it. | **RegistrarScheduleController::availableRooms()** exists (day/time conflict check) but **no route** in `web.php`. Dean has `Route::get('/dean/scheduling/available-rooms', ...)`. Registrar Create Schedule (create-schedule-workspace) uses **slot picker from COR Archive** (day/time/room/professor/block as one option), not a per-slot room dropdown. Registrar Program Schedule view only adds/removes **subjects** (no per-slot room UI). So: API exists, not exposed for Registrar; no current Registrar UI with per-slot room dropdown to wire. | **Partially add**: Add route `GET /registrar/schedule/available-rooms` for future use; when/if Registrar gets a slot editor with room dropdown, call this API. No UI change required until that editor exists. |
| 3 | **Accounting Officer / assessment view** | Confirm "Accounting Officer" = Staff with `accounting_access`; Registrar and staff with `accounting_access` have read-only Assessment view. | **User** has `accounting_access` (boolean); Admin can set it when editing staff. **Routes**: `/staff/assessments` is under `role:staff` only — no middleware for `accounting_access` and **no route for Registrar** to view assessments. So: Staff with `accounting_access` use same `/staff/assessments`; Registrar has **no** assessment view. | **Need to add**: (1) Document or enforce that Accounting Officer = staff with `accounting_access`. (2) Add read-only assessment route for Registrar (and optionally allow staff with `accounting_access` only, if desired). |

### Low / optional

| # | Area | Doc action | Code finding | Status |
|---|------|------------|--------------|--------|
| 4 | **Family income sorting in reports** | Sort/filter by family income range; export includes it; optionally finer brackets. | ReportController has `income_classification` in financial CSV export. No **sort/filter by family income range** in Reports index or Analytics. Assessment model has `income_classification`. | **Need to add** (low): Add sort or filter by income range in Reports/Assessment views and ensure export includes it. |
| 5 | **NSTP LTS/ROTC tracking** | Optional: field for NSTP component (LTS/ROTC); show on COR/reports; NSTP fee 180. | No `nstp`, `LTS`, `ROTC`, or fee 180 references in app code. | **Need to add** (optional). |
| 6 | **Block naming and capacity (verify)** | Verify config for all programs (BEED numeric; others letter); block full at 50; fill existing blocks first. | **Verified**: `config/block_naming.php` has BEED → number, others → letter. `config/blocks.php` has `strict_50_per_block`, `default_capacity` 50. BlockAssignmentService uses these and "fill previous blocks first" logic. | **Done** (verification only). |
| 7 | **Auto-save on other forms** | Optional: extend to manual registration, profile edit. | Only student enrollment form has auto-save (localStorage). | **Optional** — add if product requests it. |
| 8 | **Workflow diagram / legend** | Optional: Pending → Approved → Scheduled → Completed on Registrar/Admin dashboard. | Workflow QA page exists; no diagram/legend on admin or registrar dashboard. | **Optional** — add if desired. |

---

## 4. Summary Table (Quick Reference)

| Area | Status |
|------|--------|
| All "Finished" items in doc (#1–14) | **Verified in code** |
| Admin dashboard (trends, pie, program/location widgets, AY/semester filters) | **Need to add** |
| Registrar Schedule: room dropdown | **Add route for availableRooms; wire when Registrar has slot room UI** |
| Accounting Officer assessment view | **Need to add** (Registrar + optional accounting_access access) |
| Family income sort/filter in reports | **Need to add** (low) |
| NSTP LTS/ROTC tracking | **Need to add** (optional) |
| Block naming and capacity | **Verified** |
| Auto-save on other forms / Workflow diagram | **Optional** |

---

## 5. Recommended Next Steps

1. **High**: Implement Admin dashboard widgets (trends link/chart, Approved vs Not Approved pie, enrollees by program, enrollees by location, AY/semester filters) using existing ReportController breakdowns.
2. **Medium**: Add `GET /registrar/schedule/available-rooms` pointing to `RegistrarScheduleController@availableRooms` for future Registrar slot UIs.
3. **Medium**: Define Accounting Officer (staff with `accounting_access`) and add read-only assessment view for Registrar (and optionally restrict `/staff/assessments` to staff with `accounting_access` if required).
4. **Low**: Add family income sort/filter and export in Reports if needed.
5. **Optional**: NSTP component field and fee 180; auto-save on other forms; workflow diagram on dashboard.

Use **FEATURES_TO_ADD_OR_IMPROVE.md** for the original detailed action list; this file is the code-backed status and checklist.
