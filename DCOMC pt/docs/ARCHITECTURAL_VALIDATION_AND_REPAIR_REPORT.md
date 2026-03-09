# DCOMC — Architectural Validation and Repair Report

**Role:** Senior Software Architect, System Integrity Auditor, Full-Stack Debugging Specialist  
**Objective:** Role-based interface isolation, feature integrity, ghost data audit, school-year isolation, and production readiness  
**Date:** March 6, 2025

---

## 1. System Architecture Overview

The DCOMC Enrollment & Registration System is a **Laravel 12** web application for a Philippine college (Daraga Community College). It provides:

- **Three login portals:** Student (`/`), DCOMC staff (`/dcomc-login`), Admin (`/admin-login`), with strict role-vs-portal enforcement.
- **Six roles:** `admin`, `registrar`, `staff`, `dean`, `unifast`, `student`, each with distinct route prefixes (`/admin/*`, `/registrar/*`, `/staff/*`, `/dean/*`, `/unifast/*`, `/student/*`).
- **Layered structure:** Routes → Controllers → Services → Models → Database. Middleware: `role`, `ensure.student.profile`, `dean.department`, `EnsureSelectedSchoolYear`, and feature-flag middleware for staff/unifast.
- **Key modules:** Enrollment (forms, responses, approval, block assignment), Block management (explorer, transfer, rebalance, promotion, block change requests), COR (scope templates, deployment, archive regular/irregular), Scheduling (dean scope slots, schedule templates), Assessments (UniFast eligibility), Reports and Analytics, Settings (school years, semesters, year levels, blocks, subjects, fees, professors, rooms, staff/unifast access).

---

## 2. Role-Based Interface and Navigation Isolation — Validation and Repairs

### 2.1 Issue Identified

Shared views (e.g. Student Status, Reports, Blocks, Form Responses, COR Archive, Settings) were choosing the sidebar with a mix of:

- `auth()->user()->role` or `effectiveRole()`
- `request()->routeIs('registrar.*')` vs `request()->routeIs('admin.*')` without covering `staff`, `dean`, `unifast`

When a **Staff** user opened **Student Status**, the view only checked “registrar vs admin” and rendered **admin-sidebar**, so the interface switched to another role’s navigation. The same risk existed for UniFast and Dean on any shared page that did not explicitly handle their routes.

### 2.2 Repair Implemented

1. **Single source of truth for sidebar by route**  
   A new partial was added that **derives the sidebar only from the current route name** (no user role in the decision):

   - **File:** `resources/views/dashboards/partials/role-sidebar.blade.php`
   - **Logic:** `match (true)` on `request()->routeIs('admin.*')`, `registrar.*`, `staff.*`, `dean.*`, `unifast.*` → include the corresponding `admin-sidebar`, `registrar-sidebar`, `staff-sidebar`, `dean-sidebar`, or `unifast-sidebar`.
   - **Result:** For any given URL (e.g. `/staff/student-status`), the sidebar is always the staff sidebar, regardless of who is logged in (middleware already enforces that only staff can access that route).

2. **All shared views updated to use `role-sidebar`**  
   Every view that previously chose a sidebar with `@if`/`@else` on role or route was changed to:

   ```blade
   @include('dashboards.partials.role-sidebar')
   ```

   **Updated views (partial list):**  
   `admin-student-status`, `registrar-blocks`, `reports-index`, `analytics`, `settings-fees`, `registrar-block-change-requests`, `block-explorer`, `workflow-qa`, `settings-staff-access`, `form-responses`, `form-builder`, `form-library`, `form-response-folder`, `registrar-program-schedule`, `cor-archive-index`, `cor-archive-program`, `cor-archive-year`, `cor-archive-show`, `irregular-cor-archive-index`, `irregular-cor-archive-show`, `registrar-irregularities`, `settings-professors`, `settings-rooms`, `students-explorer`, `manual-registration`.

3. **Route-based link generation where needed**  
   In views that build links (e.g. back, export, print, or tab links), logic was changed from `auth()->user()->role` to `request()->routeIs('staff.*')`, `request()->routeIs('registrar.*')`, etc., so that:

   - The sidebar and all in-page links stay consistent with the current route.
   - Admin role-switch (impersonation) does not change which sidebar or links are shown; the **URL** (and thus the route) defines the interface.

### 2.3 Validation Result

- **Role-based interface isolation:** Achieved. Each role’s pages use a single, route-derived sidebar. Navigating under one role (e.g. Staff) no longer causes the sidebar to switch to Admin or Registrar.
- **Navigation consistency:** Sidebar and in-view links (dashboard, reports, export, print, tabs) are derived from the same route, so they stay consistent.
- **Dedicated layouts per role:** Each role still has its own sidebar partial (admin, registrar, staff, dean, unifast); the only change is **how** the view chooses which partial to include (route-based, not user-role-based).

---

## 3. List of System Features and Functionality

A full feature list with purpose, trigger, and data flow is in **docs/COMPLETE_SYSTEM_ANALYSIS_REPORT.md** (Sections 2–2.8). Summary by area:

| Area | Features |
|------|----------|
| **Auth** | Three portals, login/logout, role switch (admin), password reset, email verification |
| **Admin** | Dashboard, accounts CRUD, student status (enroll/reject/needs-correction/delete), blocks, block change requests, reports, analytics, workflow QA, staff/registrar access settings |
| **Registrar** | Manual registration, form builder, responses (approve/reject), global enrollment toggle, students, irregularities, COR archive (regular + irregular), block explorer, blocks, block change requests, settings (school years, semesters, year levels, blocks, subjects, fees, professors, rooms, staff/unifast access), COR scopes, program schedule, schedule forms (templates, deploy/undeploy) |
| **Staff** | Subset of registrar (same controllers, `/staff/*` routes); feature flags gate access (e.g. responses approve/reject, block-requests approve/reject, fees) |
| **Student** | Dashboard, profile (complete/edit), enrollment form submit, block change request, view COR |
| **Dean** | Dashboard, student status (view), scheduling, schedule by scope, COR deploy/fetch, COR archive, professors/rooms settings, manage professor (load, assignments, max-units) |
| **UniFast** | Dashboard, student status, assessments (list, export, eligibility), fees settings, reports |

---

## 4. Feature Connection Validation (Frontend, Backend, Database)

### 4.1 Validation Approach

- **Routes:** All feature entry points are defined in `routes/web.php` under the correct middleware (`role:admin`, `role:registrar`, etc.) and, where applicable, `staff.feature:*` or `unifast.feature:*`.
- **Controllers:** Each route points to a controller method; shared actions (e.g. student status, reports) use the same controller with role-specific route names so links and redirects stay correct.
- **Models and DB:** Enrollment uses `EnrollmentForm`, `FormResponse`; block assignment uses `Block`, `User`, `BlockAssignmentService`; COR uses `StudentCorRecord`, `ScopeScheduleSlot`, `CorDeploymentService`. Relationships (e.g. FormResponse → User, EnrollmentForm, Block) are consistent with usage.
- **Views:** Buttons and forms post to the intended routes (e.g. `route('registrar.student-status.enroll', ['id' => $id])`). After the role-sidebar fix, no view relies on `auth()->user()->role` to choose sidebar or primary action links; they use the current route or route-based variables.

### 4.2 Result

- **Feature connection:** Features are wired end-to-end: frontend (Blade) → route → controller → service/model → database. No missing or obviously wrong bindings were found for the flows analyzed.
- **Role-based access:** Enforced by middleware and, for staff/unifast, by feature-flag middleware on sensitive actions (e.g. approve/reject). Sidebar and navigation are now aligned with the route, avoiding “wrong role UI” when a shared view is used by multiple roles.

---

## 5. Broken Connections Discovered and Repairs Performed

| Issue | Location | Repair |
|-------|----------|--------|
| **Sidebar switching to wrong role** | Multiple shared Blade views | Introduced `role-sidebar.blade.php` and switched all shared views to `@include('dashboards.partials.role-sidebar')` so sidebar is determined only by `request()->routeIs('admin.*')`, etc. |
| **Staff/UniFast/Dean seeing Admin sidebar on Student Status** | `admin-student-status.blade.php` | Replaced `@if($isRegistrarView)` / `@else` (admin) with `@include('dashboards.partials.role-sidebar')` so staff/unifast/dean get the correct sidebar. |
| **Links in shared views using wrong route prefix** | e.g. reports-index, form-responses, registrar-irregularities, irregular-cor-archive | Back/export/print and tab links now use route-based logic (e.g. `request()->routeIs('staff.*') ? route('staff.reports.export') : ...`) or a single `$irregularitiesRoute` derived from route. |
| **Update-record route for Student Status** | admin-student-status (form action) | Kept template for admin and registrar; staff uses `staff.student-status.update-record`. UniFast/Dean do not have update-record routes; they only view (no edit modal submission). |

No broken **backend–database** or **controller–service** connections were identified; the repairs were focused on **frontend–route** consistency and **role–UI** isolation.

---

## 6. Ghost Data Audit

A targeted scan was performed for:

- **Unused routes:** All named routes in `web.php` are referenced in Blade (e.g. `route('registrar.dashboard')`) or in redirects. No obviously dead route names were found.
- **Unused variables in views:** Views that were refactored (e.g. to use `role-sidebar`) now derive `$isStaff`, `$backRoute`, etc., from the current route; old role-based variables were removed or replaced.
- **Duplicate or legacy sidebar logic:** Replaced by the single `role-sidebar` partial; duplicate `@if`/`@else` sidebar blocks were removed from many files.

**Recommendation:** Run a static or IDE-based “find unused” pass for custom helpers, config keys, and database columns if the codebase grows. No large-scale removal of “ghost” code was required for this validation; the main cleanup was consolidating sidebar and route-based link logic.

---

## 7. Validation of UI Actions (Buttons, Dropdowns, Search, Filtering, API Calls)

- **Buttons and forms:** Submit to the correct named routes (e.g. enroll, reject, needs-correction, update-record, block-change approve/reject). Form actions use `route(...)` with the appropriate prefix for the current context (registrar/staff/admin) after the repairs above.
- **Dropdowns:** School year selector posts to `route('set-school-year')`. Filters (e.g. student status: level, program, block, process_status) are passed as query parameters and applied in the controller.
- **Search/filtering:** Student status, reports, and analytics use request parameters and scopes (e.g. `forSelectedSchoolYear()`, filters on course/year/semester). Data is filtered before being passed to the view.
- **Data fetching:** Lists (students, responses, blocks, COR archive) are loaded in the controller and passed to the view; no orphaned or unused API endpoints were identified for these flows.
- **Role and feature checks:** Staff/UniFast feature flags are enforced in middleware for sensitive actions; sidebar and nav links are route-based, so they match the actual permissions of the route.

---

## 8. Database Integrity and Data Relationships

- **Core tables:** `users`, `blocks`, `enrollment_forms`, `form_responses`, `school_years`, `academic_semesters`, `academic_year_levels`, `programs`, `majors`, `departments` — used consistently by controllers and models.
- **Scheduling and COR:** `scope_schedule_slots`, `student_cor_records`, `schedule_templates`, `cor_scopes`, `cor_scope_subjects`, `cor_scope_fees` — relationships and foreign keys align with deployment and archive logic.
- **Block operations:** `block_transfer_logs`, `block_change_requests`, `student_block_assignments` — used by BlockManagementController and related services.
- **Assessments and fees:** `assessments`, `fees`, `fee_categories` — used by FinanceMonitoringController and fee settings.
- **Scopes:** `FormResponse::forSelectedSchoolYear()` and `EnrollmentForm::forSelectedSchoolYear()` rely on `school_year_id` and session `selected_school_year_id`; enrollment forms are tied to a school year.

No referential or naming inconsistencies were found; the schema supports the current feature set.

---

## 9. School Year Data Isolation and Archival Structure

### 9.1 Current Behavior

- **Session and active year:** For staff roles (admin, registrar, staff, dean, unifast), `EnsureSelectedSchoolYear` middleware ensures the session has `selected_school_year_id` (defaulting to the active school year from `academic_calendar_settings`). The school year selector in the sidebar allows changing the selected year; `AcademicCalendarService::getSelectedSchoolYearId()` and `getSelectedSchoolYearLabel()` are used throughout.
- **Enrollment and forms:** `EnrollmentForm` has `school_year_id`. Scopes `forSelectedSchoolYear()` filter by this and the session’s selected year. `FormResponse` is scoped via its enrollment form’s school year. New forms and responses are associated with the selected/active school year.
- **Blocks:** `blocks` has `school_year_label`; listing and counting use `AcademicCalendarService::getSelectedSchoolYearLabel()` or the requested year so that block data is filtered by school year.
- **Students:** `users.school_year` stores the school year in which the student was enrolled; student status and reports can filter by school year. Admin student-status list is restricted to students with at least one `FormResponse` in the **selected** school year.
- **COR and schedules:** `StudentCorRecord` and schedule data carry `school_year` (or equivalent) so archives and reports can be filtered by year. Historical data remains in the database; switching the selected year shows that year’s data without deleting past years.

### 9.2 Alignment with Requirements

- **New data tied to active/selected year:** Enrollment forms, responses, and block operations use the selected school year (or active year where applicable).
- **Previous years preserved:** No automatic deletion of old school year data; it remains available for viewing and reporting when the user selects that year.
- **Queries default to selected year:** Listings and dashboards use `forSelectedSchoolYear()` or the session/selected label so operations default to the chosen year.
- **Reports and analytics:** Can use filters (e.g. academic_year, semester) so multiple years can be compared or a single year viewed.
- **Archived data:** Treated as read-only in normal flows; administrative corrections would require explicit actions (not auto-modified).

**Conclusion:** School year isolation is implemented and used consistently across enrollment, blocks, student status, and reporting. No structural changes were required during this audit.

---

## 10. Evaluation of System Readiness for Real University Deployment

- **Workflow:** Enrollment (form → submit → approve/reject/needs-correction → block assignment → assessment) matches typical Philippine college enrollment. COR deployment and archive support official registration records.
- **Roles:** Admin, Registrar, Staff, Dean, UniFast, and Student have distinct responsibilities and access; feature flags allow fine-tuning for staff and UniFast.
- **School year handling:** Active and selected school years are clearly separated; data is scoped by year and previous years remain available.
- **Role–UI consistency:** With the role-sidebar and route-based link fixes, the interface no longer “flips” to another role’s navigation when using shared pages.

**Gaps for production (unchanged by this report):** Payment/billing integration, dedicated accounting role, in-app/email notifications, and a full audit log for sensitive actions. These do not affect the correctness of the current role isolation or school-year behavior.

---

## 11. Recommendations for Stability, Scalability, and Maintainability

1. **Keep sidebar and links route-based:** Do not reintroduce `auth()->user()->role` or `effectiveRole()` for choosing the sidebar or primary action links in shared views. Keeping the rule “current route → sidebar and links” prevents regression of the role-isolation fix.
2. **Extend feature-flag enforcement (optional):** Consider applying `staff.feature:*` and `unifast.feature:*` to more routes (e.g. entire sections) so that disabling a feature hides both the menu and the URLs.
3. **School year in more modules (if needed):** If new modules (e.g. additional reports or dashboards) need to be year-scoped, reuse `AcademicCalendarService::getSelectedSchoolYearId()` and the same session pattern for consistency.
4. **Documentation:** Keep FEATURE_OVERVIEW.md and COMPLETE_SYSTEM_ANALYSIS_REPORT.md in sync with code so future changes preserve documented behavior and role/school-year rules.
5. **Testing:** Add browser or feature tests for “staff on student-status sees staff sidebar and staff links” and “switching school year changes list results” to guard against regressions.

---

## 12. Summary

- **Role-based interface and navigation isolation** has been validated and repaired by introducing a single route-based sidebar partial (`role-sidebar.blade.php`) and updating all shared dashboard views to use it. The sidebar and in-page links no longer switch to another role’s interface when opening a page under a given role.
- **Feature connectivity** (frontend ↔ backend ↔ database) was reviewed; no broken connections were found. Repairs were limited to ensuring that shared views use the correct sidebar and route-based links for the current URL.
- **Ghost data:** No large-scale dead code or unused routes were removed; sidebar and link logic were consolidated to reduce duplication and inconsistency.
- **School year data isolation** is already in place and consistently applied across enrollment, blocks, student status, and reports; no structural changes were made.
- The system is **suitable for deployment** in a Philippine college setting for the current feature set, with recommendations above for further hardening and maintainability.

---

*Report generated after architectural validation and repair. All changes are in the codebase under `c:\Users\JOHN ELMER\Desktop\DCOMC pt`.*
