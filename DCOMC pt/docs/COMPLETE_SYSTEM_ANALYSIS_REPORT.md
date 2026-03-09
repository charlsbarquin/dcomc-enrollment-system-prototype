# DCOMC Enrollment & Registration System — Complete System Analysis Report

**Role:** Senior Software Architect, System Analyst, and Code Auditor  
**Scope:** Full codebase analysis — architecture, features, workflow, database, integration, validation, and real-world applicability  
**Date:** March 6, 2025

---

## 1. Overall System Architecture

### 1.1 Technology Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Laravel 12 (PHP 8.2+), Laravel Breeze (auth scaffolding) |
| **Frontend** | Blade templates, TailwindCSS 3, Alpine.js, Vite 7 |
| **Database** | Laravel Eloquent ORM; driver from `.env` (e.g. MySQL/MariaDB, SQLite) |
| **Auth** | Single `web` guard; three login portals with role-vs-portal enforcement |

### 1.2 High-Level Structure

The system follows **MVC plus a dedicated service layer**:

- **Routes:** All web routes in `routes/web.php`; auth in `routes/auth.php`. No separate `api.php`; JSON/form endpoints are under the same app (e.g. form builder, schedule slots).
- **Controllers:** Grouped by role (`AdminAccountController`, `RegistrarController`, `DeanSchedulingController`, etc.) and by feature (`BlockManagementController`, `CorScopeController`, `CorArchiveController`). Shared controllers (e.g. `ReportController`, `AnalyticsController`) are reused across admin/registrar/staff with role-based route prefixes.
- **Models:** All in `app/Models/` (User, Block, FormResponse, EnrollmentForm, Program, Subject, StudentCorRecord, etc.) with Eloquent relationships and scopes (e.g. `FormResponse::forSelectedSchoolYear()`, `EnrollmentForm::forSelectedSchoolYear()`).
- **Services:** Business logic in `app/Services/`: `BlockAssignmentService`, `CorDeploymentService`, `BlockRebalancingService`, `BlockPromotionService`, `IrregularEnrollmentValidationService`, `AcademicCalendarService`, `SchedulingScopeService`, `CorViewService`, `ProfessorWorkloadService`, `AcademicNormalizer`, `BlockManagementService`.
- **Middleware:** `RoleMiddleware` (alias `role`), `EnsureStudentProfileCompleted` (`ensure.student.profile`), `EnsureDeanHasDepartment` (`dean.department`), `EnsureSelectedSchoolYear` (appended to `web` for staff roles).

### 1.3 Authentication and Portal Flow

- **Three portals:**
  - `/` → Student login (`auth.login-student`).
  - `/dcomc-login` → DCOMC staff (Registrar, Staff, Dean, UniFast).
  - `/admin-login` → Admin only.
- **Login:** `POST /login` → `AuthenticatedSessionController@store`. Request must include `portal_type` (`student` | `dcomc` | `admin`). Credentials are validated; then **role vs portal** is enforced:
  - Admin portal: only `user->role === 'admin'`; otherwise logout and error.
  - DCOMC portal: only `registrar`, `staff`, `dean`, `unifast`; otherwise logout and error.
  - Student portal: only `role === 'student'`; otherwise logout and error.
- **Post-login redirect:** `/{role}/dashboard` (e.g. `/student/dashboard`, `/registrar/dashboard`). Session `url.intended` is cleared to avoid bouncing to wrong portal.
- **Role switch:** Admin can impersonate another role via `AdminRoleSwitchController`; session stores `role_switch` (active, as_role, original_role). `User::effectiveRole()` returns the impersonated role when switch is active. Logout is blocked until admin switches back.

### 1.4 Component Interaction Summary

- **Student** submits enrollment via form → `FormResponse` created → Admin/Registrar approve/reject or mark needs_correction. On approval, `BlockAssignmentService::assignStudentToBlock()` updates `users.block_id`, year_level, semester and block `current_size`; `FormResponse` gets `assigned_block_id`; `Assessment` is created/updated.
- **Registrar/Staff** build and deploy enrollment forms; deploy maps form to school year/semester; global enrollment toggle uses `Cache::forever('global_enrollment_active', true|false)`.
- **Dean** (with `department_id` set) manages schedule by scope (`ScopeScheduleSlot`), deploys COR via `CorDeploymentService` → immutable `StudentCorRecord` rows. Registrar can also deploy COR from schedule templates (irregular and program-based).
- **Block management** (transfer, rebalance, promotion) uses `BlockManagementService`, `BlockRebalancingService`, `BlockPromotionService`; block change requests use `BlockChangeRequest` and are approved/rejected by Admin/Registrar.
- **COR archive** and **irregular COR archive** are read-only views over deployed data (program/year/semester or date/deployed-by).

---

## 2. List of All System Features and Their Functions

### 2.1 Authentication & Portals

| Feature | Purpose | Trigger | Input | Processing | Output |
|--------|---------|---------|--------|------------|--------|
| Student login | Allow students to authenticate | User visits `/`, submits credentials + `portal_type=student` | email (or school_id), password, portal_type | Auth::attempt; role check | Redirect to `/student/dashboard` or error |
| DCOMC staff login | Allow registrar/staff/dean/unifast to authenticate | User visits `/dcomc-login`, submits credentials + `portal_type=dcomc` | email, password, portal_type | Auth::attempt; role in [registrar, staff, dean, unifast] | Redirect to `/{role}/dashboard` or error |
| Admin login | Allow admin only | User visits `/admin-login`, submits credentials + `portal_type=admin` | email, password, portal_type | Auth::attempt; role === admin | Redirect to `/admin/dashboard` or error |
| Logout | End session | POST to `logout` | — | Session invalidate; if admin with role_switch active, blocked | Redirect to `/` or error to switch back first |
| Role switch (admin) | Impersonate another role | Admin posts to `admin.role-switch.start` | as_role | Session stores role_switch | Redirect to impersonated role dashboard |
| Role switch stop | End impersonation | DELETE to `admin.role-switch.stop` | — | Session forget role_switch | Redirect back |

### 2.2 Admin Module

| Feature | Purpose | Trigger | Input | Processing | Output |
|--------|---------|---------|--------|------------|--------|
| Admin dashboard | Overview and QA counts | GET `/admin/dashboard` | — | Same view as registrar dashboard (QA counts from FormResponse for selected school year) | View with needs_correction, approved, scheduled, completed counts |
| Admin accounts | CRUD users | GET/POST/PUT/DELETE `/admin/accounts` | name, email, password, role, department_id, faculty_type, program_scope, max_units, accounting_access | Validate; Hash password; User::create/update/destroy; on delete, FormResponse for user deleted | Success/error message; list of users |
| Admin student status | List and act on applications | GET `/admin/student-status`; PATCH enroll/reject/needs-correction; DELETE | Filters: student, level, program, block, shift, process_status | Query students with formResponses; enrollApplication uses BlockAssignmentService, updates FormResponse and User, creates Assessment | View with students and actions |
| Admin blocks | List and delete blocks | GET `/admin/blocks`; DELETE `/admin/blocks/{id}` | — | RegistrarOperationsController::blocks, deleteBlock | Block list; block deleted |
| Admin block change requests | Approve/reject block change requests | GET; PATCH approve/reject | — | RegistrarOperationsController | List; request approved/rejected |
| Admin reports | Reports and export | GET `/admin/reports`, print, export | — | ReportController | View; PDF/CSV |
| Admin analytics | Analytics and export | GET `/admin/analytics`, print, export | — | AnalyticsController | View; PDF/CSV |
| Admin settings (staff-access, registrar-access) | Feature toggles for staff/registrar | GET/POST StaffAccessController, RegistrarAccessController | Feature keys and states | StaffFeatureAccess, RegistrarFeatureAccess updateOrCreate | View; settings saved |
| Admin workflow-qa | Workflow QA view | GET `/admin/workflow-qa` | — | WorkflowController::qa | QA view |

### 2.3 Registrar Module

| Feature | Purpose | Trigger | Input | Processing | Output |
|--------|---------|---------|--------|------------|--------|
| Manual registration | Add student and optionally assign block | POST `/registrar/registration/manual` | Full student profile fields, course, year_level, semester, shift, etc. | Validate; User::create (role=student); optionally BlockAssignmentService::assignStudentToBlock; profile_completed=true | Redirect with success |
| Manual import | Bulk import students | POST import; GET template | CSV | Parse; create users; optional block assign | Redirect; template download |
| Form builder | Create/edit/delete enrollment forms | GET/POST/GET/DELETE builder, get-form, save-form, delete-form | questions (JSON), title, description, assigned_year, assigned_semester, incoming_* | EnrollmentForm CRUD | Form list; form saved/deleted |
| Responses | List responses, folder view, approve/reject | GET responses, folder; PATCH approve/reject | — | FormResponse forSelectedSchoolYear; approveResponse: same logic as enrollApplication (BlockAssignmentService, Assessment) | List; response approved/rejected |
| Toggle global enrollment | Enable/disable enrollment site-wide | POST toggle-global | — | Cache::forever('global_enrollment_active', bool) | Redirect |
| Deploy form | Map form to year/semester and activate | POST deploy-form | form_id, assigned_year, assigned_semester, incoming_* | EnrollmentForm update | Redirect |
| Students list | List students with filters | GET `/registrar/students` | — | User::role=student with filters | View |
| Irregularities | List irregular students; create/edit irregular schedule | GET irregularities, schedule; DELETE remove subject | — | Users isIrregularType; StudentCorRecord for irregular; deploy/undeploy | View; schedule updated |
| Irregular COR archive | View COR by date and deployed-by | GET index, show | date, deployedBy | StudentCorRecord where cor_source = create_schedule | View |
| COR archive | View COR by program/year/semester | GET index, program, year, show; POST delete-block | programId, yearLevel, semester | StudentCorRecord; deleteBlockArchive | View |
| Blocks | List blocks; delete | GET blocks; DELETE block | — | RegistrarOperationsController | List |
| Block change requests | List, approve, reject | GET; PATCH approve/reject | — | RegistrarOperationsController | List; approved/rejected |
| Block explorer | Tree (program → year → blocks), students per block, transfer, rebalance, promotion, transfer log | GET tree, block-students, transfer-log; POST transfer, rebalance, promotion | Block ids, student ids, target block | BlockManagementController; services | Tree view; blocks/students updated |
| Assign/remove irregular | Assign irregular to block or remove | POST assign-irregular; DELETE remove-irregular | user_id, block_id, etc. | StudentBlockAssignment; BlockManagementController | Redirect |
| Print COR / print all COR / master list | Print single COR or block COR list | GET students/{user}/cor; blocks/{block}/print-all-cor, print-master-list | — | CorViewController, BlockManagementController | PDF/view |
| Settings | School years, semesters, year levels, blocks, subjects, fees, professors, rooms, staff-access, unifast-access | Various GET/POST/PATCH/DELETE | — | RegistrarSettingsController; StaffAccessController; UnifastAccessController | Settings views; data saved |
| COR scope templates | CRUD COR scopes (program, year, semester, major) | CRUD `/registrar/cor-scopes` | — | CorScopeController | List; scope saved |
| Program schedule | Save program schedule (scope schedule slots) | GET program-schedule; POST save | — | RegistrarScheduleController | View; slots saved |
| Schedule forms (templates) | CRUD schedule templates; deploy/undeploy; conflicts | GET/POST/PATCH/DELETE schedule/forms; deploy, undeploy, conflicts | — | RegistrarScheduleController; deploy creates StudentCorRecord | List; template deployed/undeployed |

### 2.4 Staff Module

Staff uses the **same controllers** as registrar for most features (RegistrarController, BlockManagementController, CorArchiveController, etc.) but with route prefix `/staff/`. Staff has:

- Dashboard, student-status, manual registration, form builder, responses, approve/reject, students-explorer, block-explorer, blocks, block-change-requests (including approve/reject), irregularities, irregular COR archive, COR archive, program-schedule, analytics, reports, fees settings, assessments (list, export, status update).  
- **No** separate “registrar-only” middleware that blocks staff from approve/reject; both roles share those routes. Feature visibility can be gated by `StaffFeatureAccess` / `StaffFeatureUserAccess` in the UI (settings in Admin/Registrar).

### 2.5 Student Module

| Feature | Purpose | Trigger | Input | Processing | Output |
|--------|---------|---------|--------|------------|--------|
| Student dashboard | Show enrollment status, application, blocks, block change request, schedule, assessment | GET `/student/dashboard` | — | Auth user; global_enrollment_active; latest FormResponse; available blocks (by year_level, semester, gender); pending BlockChangeRequest; hasSchedule; latest Assessment; availableForm by year_level/semester | View |
| Student profile | First-time profile completion | GET/POST profile | school_id, name, gender, DOB, address, family, course, year_level, semester, student_type, shift, etc. | Validate; profile_completed=true; status_color by student_type; email sync for students | Redirect to dashboard |
| Student profile edit | Edit profile (no school_id change in edit) | GET/POST profile/edit | Same fields except school_id | Validate; User update | Redirect |
| Enrollment form | Show form and submit | GET enrollment-form; POST submit-enrollment | form_id, preferred_block_id, answers | Check global_enrollment_active; form for user year_level/semester; if needs_correction allow resubmit; FormResponse create or update | View; redirect with success |
| Block change request | Request block change | POST block-change-request | reason, from_block_id, to_block_id, etc. | StudentServicesController::requestBlockChange; BlockChangeRequest create | Redirect |
| View COR | View own COR | GET `/student/cor` | — | StudentServicesController::cor; StudentCorRecord for user | View/print |

All student routes are protected by `ensure.student.profile`: only `student.profile` and `student.profile.update` are allowed when profile is not completed.

### 2.6 Dean Module

| Feature | Purpose | Trigger | Input | Processing | Output |
|--------|---------|---------|--------|------------|--------|
| Dean dashboard | Landing | GET `/dean/dashboard` | — | AdminDashboardController::index | View |
| Student status | List students (read-only for dean) | GET `/dean/student-status` | — | AdminAccountController::studentStatus | View |
| Scheduling | Slots, rooms, subjects | GET/POST scheduling, available-rooms, store, storeSubject, storeRoom | — | DeanSchedulingController | View; data saved |
| Schedule by scope | Department-scoped schedule slots; deploy/fetch COR | GET schedule; POST save slots, deploy-cor, fetch-cor | — | DeanScheduleByScopeController; CorDeploymentService | View; COR deployed |
| COR archive | View COR by program/year/semester | GET index, program, year, show; POST delete-block | — | CorArchiveController | View |
| Settings (professors, rooms) | Manage professors and rooms | GET/POST professors, rooms | — | RegistrarSettingsController | View; data saved |
| Manage professor | Teaching load, assignments, max-units, schedule-selection-limit | GET index, teaching-load, show; POST assignments; PATCH max-units, schedule-selection-limit | — | DeanManageProfessorController; ProfessorWorkloadService | View; updated |
| Room utilization | Room usage view | GET room-utilization | — | DeanSchedulingController | View |

Dean routes under schedule-by-scope and COR deploy are wrapped in `dean.department` middleware; if `department_id` is null, user is redirected to dean dashboard with an error.

### 2.7 UniFast Module

| Feature | Purpose | Trigger | Input | Processing | Output |
|--------|---------|---------|--------|------------|--------|
| Dashboard | Landing | GET `/unifast/dashboard` | — | AdminDashboardController | View |
| Student status | List students | GET `/unifast/student-status` | — | AdminAccountController::studentStatus | View |
| Assessments | List assessments; set eligibility; export | GET assessments, export; PATCH eligibility | — | FinanceMonitoringController | View; CSV; eligibility updated |
| Settings (fees) | Fee configuration (same Fee model as registrar) | GET/POST fees, table, copy-from-raw, toggle, destroy, raw-fees | — | RegistrarSettingsController | View; fees saved |
| Reports | Reports and export | GET reports, print, export | — | ReportController | View; export |

### 2.8 Shared / Cross-Role

| Feature | Purpose | Trigger | Input | Processing | Output |
|--------|---------|---------|--------|------------|--------|
| Set school year | Set session filter for staff roles | POST `/set-school-year` | school_year_id | SchoolYearSelectorController; AcademicCalendarService::setSelectedSchoolYearId | Redirect |
| Reports / Analytics | Role-prefixed routes (admin, registrar, staff, unifast, dean) | GET reports/analytics/print/export | — | ReportController, AnalyticsController | Views; PDF/CSV |

---

## 3. System Workflow and Process Flow

### 3.1 Enrollment Workflow (End-to-End)

1. **Setup (Registrar):** Create school year, semesters, year levels; create programs/blocks; build enrollment form (questions, assigned_year, assigned_semester, incoming_year_level, incoming_semester); deploy form; optionally set `global_enrollment_active` to true.
2. **Student:** Logs in at `/` → if profile incomplete, completes profile → dashboard shows “Enrollment Access” and available form if open and form matches year_level/semester → opens enrollment form → fills answers, may select preferred block → submits → `FormResponse` created (process_status = pending).
3. **Registrar/Admin:** Opens student status (or responses) → filters by process_status → for a response: **Approve** (enroll), **Reject**, or **Needs correction**.
4. **On Approve:**  
   - User's year_level, semester (and school_year in registrar flow) updated to form's incoming_* .  
   - `BlockAssignmentService::assignStudentToBlock()`: prefers preferred_block_id if has capacity; else oldest non-full block for same program/year/semester/shift/gender; else creates new block (code from config/Program).  
   - User's block_id, course, year_level, semester updated; block's current_size incremented; previous block's current_size decremented if any.  
   - FormResponse: approval_status=approved, process_status=approved, assigned_block_id set, reviewed_by, reviewed_at.  
   - Assessment::updateOrCreate (user_id, semester) with income_classification, assessment_status=pending.
5. **Needs correction:** Student can resubmit (same form_id); response updated with new answers and process_status=pending.
6. **Reject:** FormResponse marked rejected; no block assignment.

### 3.2 COR Deployment Flow

1. **Dean (department-scoped):** Builds schedule in Scope Schedule Slots (program, year level, block/shift, semester, school year); assigns professor, room, time per subject. Validates via `CorDeploymentService::validateForDeployment()` (professor and time required per subject). On “Deploy COR,” service fetches students for scope (User with block_id or StudentBlockAssignment), builds snapshot per subject from slots (professor name, room, days, time), creates `StudentCorRecord` rows (immutable) with cor_source = schedule_by_program.
2. **Registrar/Staff (schedule templates):** Create schedule template for program/year/semester (and block for irregular); add subjects; check conflicts; “Deploy” creates `StudentCorRecord` with cor_source = create_schedule (irregular) or similar. Archive views filter by program/year/semester or by date/deployed_by.

### 3.3 Block Management Flow

- **Transfer:** Move students from one block to another (same program/year/semester); BlockTransferLog created; block current_size updated.  
- **Rebalance:** Redistribute students across blocks (e.g. to fill blocks evenly).  
- **Promotion:** Promote block to next year level (e.g. 1st to 2nd); block and students’ year_level/semester updated.  
- **Block change request:** Student submits reason and target block; Admin/Registrar approve or reject; on approve, student's block_id (and related) updated.

### 3.4 Data Flow Summary

- **Session:** `selected_school_year_id` (staff roles), `role_switch` (admin impersonation).  
- **Cache:** `global_enrollment_active` (boolean).  
- **DB:** Users ↔ Blocks (block_id); Users ↔ FormResponses; FormResponses ↔ EnrollmentForm, Block (preferred/assigned); Blocks ↔ BlockTransferLog, StudentBlockAssignment; ScopeScheduleSlot → StudentCorRecord (deploy); Assessments by user/semester; feature access tables (registrar, staff, unifast) for toggles.

---

## 4. User Roles and Role Interactions

| Role | Portal | Can Do | Interacts With |
|------|--------|--------|-----------------|
| **admin** | Admin | Full account CRUD; student status (enroll/reject/needs-correction/delete); blocks list/delete; block change approve/reject; reports; analytics; workflow-qa; staff/registrar feature access settings; role switch | All roles (impersonate); Registrar/Staff for shared controllers |
| **registrar** | DCOMC | Everything staff can plus: full settings (school years, semesters, year levels, blocks, subjects, fees, professors, rooms, staff-access, unifast-access); COR scopes; schedule forms; full registration and responses | Students (approve/reject); Admin (shared student-status); Staff (same tools, different route prefix) |
| **staff** | DCOMC | Registration (manual, builder, responses, approve/reject); students-explorer; block-explorer; blocks; block-change-requests (approve/reject); irregularities; COR archives; program-schedule; analytics; reports; fees (if enabled); assessments | Same as registrar for most features; access can be limited by StaffFeatureAccess in UI |
| **dean** | DCOMC | Dashboard; student status (view); scheduling; schedule by scope (department); deploy/fetch COR; COR archive; professors/rooms settings; manage professor (load, assignments, max-units) | Department-scoped (department_id required); Registrar for shared settings (professors, rooms) |
| **unifast** | DCOMC | Dashboard; student status; assessments (list, export, set eligibility); fees settings; reports | Students (assessments); Registrar (fee model shared) |
| **student** | Student | Profile (complete/edit); view enrollment form and submit; view dashboard (status, blocks, schedule, assessment); block change request; view COR | Registrar/Admin (approval); system (blocks, forms, COR) |

**Role middleware:** Every role-prefixed route group uses `role:{role}`. `User::effectiveRole()` is used so that when admin is impersonating, the impersonated role is checked. Thus an admin with role_switch to “registrar” can access all registrar routes.

---

## 5. Database Structure and Data Relationships

### 5.1 Core Tables

| Table | Purpose | Key Fields / Relationships |
|-------|---------|-----------------------------|
| **users** | All users (students, admin, registrar, staff, dean, unifast) | role, department_id, block_id, school_id, year_level, semester, school_year, profile_completed, student_type, status_color, shift, course, major, faculty_type, max_units, schedule_selection_limit, assigned_units, accounting_access; belongsTo Block, Department; hasMany FormResponse, StudentBlockAssignment, Assessment, StudentCorRecord (as student_id), teachingSchedules, subjectAssignments, subjectCompletions |
| **blocks** | Sections/cohorts | code, program_id, program, major, year_level, semester, shift, gender_group, capacity, max_capacity, current_size, is_active, school_year_label; belongsTo Program; hasMany User (students), ClassSchedule, StudentBlockAssignment, BlockTransferLog (from/to) |
| **enrollment_forms** | Form definitions | school_year_id, title, questions (JSON), is_active, assigned_year, assigned_semester, incoming_year_level, incoming_semester; belongsTo SchoolYear; hasMany FormResponse |
| **form_responses** | Student applications | enrollment_form_id, user_id, preferred_block_id, assigned_block_id, answers (JSON), approval_status, process_status, process_notes, reviewed_by, reviewed_at, reviewed_by_role; belongsTo EnrollmentForm, User, Block (preferred, assigned), User (reviewer) |
| **school_years** | Academic years | start_year, end_year, label (e.g. 2024-2025) |
| **academic_semesters** | Semesters | name, is_active |
| **academic_year_levels** | Year levels | name, is_active |
| **programs** | Degree programs | program_name, code, department_id |
| **majors** | Majors per program | program, major, is_active |
| **departments** | Departments (e.g. for dean) | name, etc. |

### 5.2 Scheduling and COR

| Table | Purpose | Key Fields / Relationships |
|-------|---------|-----------------------------|
| **subjects** | Course subjects | code, title, program_id, year_level, etc. |
| **raw_subjects** | Raw subject catalog (linked to subjects) | — |
| **scope_schedule_slots** | Schedule slots by scope (program, year, block, shift, semester, school_year) | subject_id, professor_id, room_id, block_id, day_of_week, start_time, end_time, is_overload |
| **class_schedules** | Legacy or block-level schedule | block_id, subject_id, professor_id, room_id, etc. |
| **schedule_templates** | Schedule templates for deployment | program_id, academic_year_level_id, semester, school_year, etc. |
| **cor_scopes** | COR scope templates | program_id, academic_year_level_id, semester, school_year, major |
| **cor_scope_subjects** | Subjects per COR scope | cor_scope_id, subject_id |
| **cor_scope_fees** | Fees per COR scope | cor_scope_id, fee_id |
| **student_cor_records** | Immutable COR snapshot per student per subject | student_id, subject_id, professor_id, block_id, program_id, year_level, semester, school_year, snapshots (professor_name, room_name, days, start/end time), cor_source, deployed_by, deployed_at |

### 5.3 Blocks and Requests

| Table | Purpose | Key Fields / Relationships |
|-------|---------|-----------------------------|
| **block_transfer_logs** | History of student moves | from_block_id, to_block_id, user_id, etc. |
| **block_change_requests** | Student-initiated block change | student_id, from_block_id, to_block_id, reason, status (pending/approved/rejected) |
| **student_block_assignments** | Irregular students in multiple blocks | user_id, block_id, school_year, etc. |

### 5.4 Fees and Assessment

| Table | Purpose | Key Fields / Relationships |
|-------|---------|-----------------------------|
| **fee_categories** | Fee categories | — |
| **fees** | Fee definitions (program/year_level scope) | fee_category_id, program_id, year_level_id, amount, etc. |
| **assessments** | Per-student assessment (income, UniFast eligibility) | user_id, school_year, semester, income_classification, assessment_status, unifast_eligible, reviewed_by, reviewed_at |

### 5.5 Feature Access

| Table | Purpose |
|-------|---------|
| **registrar_feature_accesses** | Global feature toggles for registrar |
| **staff_feature_accesses** | Global feature toggles for staff |
| **staff_feature_user_accesses** | Per-user overrides for staff features |
| **unifast_feature_accesses** | Global feature toggles for unifast |
| **unifast_feature_user_accesses** | Per-user overrides for unifast |

### 5.6 Other

- **rooms**, **professor_subject_assignments**, **academic_calendar_settings**, **student_subject_completions** (for irregular validation), **password_reset_tokens**, **sessions**, **cache**, **jobs**, **failed_jobs**, **job_batches**, **cache_locks**.

Relationships are consistent with the application logic: FormResponse links to EnrollmentForm and User and Block; Block links to Program; StudentCorRecord links to User, Subject, Block, Program, Professor (User); block assignment and transfer logs support block operations and reporting.

---

## 6. Feature Integration Analysis

### 6.1 Correctly Connected Flows

- **Login → role → dashboard:** Portal and role are enforced; redirect to `/{role}/dashboard` is consistent.
- **Enrollment form → response → approval:** Form is selected by student’s year_level/semester and form’s assigned_* and incoming_*; response is created/updated; approve uses same BlockAssignmentService and Assessment update in both AdminAccountController::enrollApplication and RegistrarController::approveResponse.
- **Block assignment:** Preferred block is respected when it has capacity; otherwise oldest non-full block or new block; User and Block current_size are updated in one transaction in BlockAssignmentService.
- **COR deployment:** Dean and Registrar flows both use CorDeploymentService (or equivalent logic) to produce StudentCorRecord; archive and irregular archive read from the same table with different filters (program/year/semester vs date/deployed_by and cor_source).
- **Block explorer and operations:** Transfer, rebalance, promotion use shared BlockManagementController and services; transfer log is written; block change requests are approved/rejected and update student block_id.
- **School year filter:** FormResponse and EnrollmentForm scopes use `forSelectedSchoolYear()` which relies on session `selected_school_year_id`; EnsureSelectedSchoolYear sets it for staff roles; set-school-year allows changing it. Admin student-status does not scope by school year when enrolling by ID (see below).
- **Student profile:** EnsureStudentProfileCompleted allows only profile and profile.update until profile_completed; dashboard and enrollment form then use the same user attributes (year_level, semester, shift, gender) for form and block eligibility.

### 6.2 Inconsistencies or Gaps

- **Admin enrollApplication vs Registrar approveResponse:** Admin uses `FormResponse::findOrFail($id)` without `forSelectedSchoolYear()`. Registrar uses `FormResponse::query()->forSelectedSchoolYear()->...->findOrFail($id)`. So admin can enroll an application from any school year if they have the ID (e.g. from a direct link or list that is not filtered). Recommendation: apply the same school year scope to admin student-status list and to enrollApplication, or document that admin is “global” by design.
- **Feature access (staff/registrar/unifast):** Toggles are stored in RegistrarFeatureAccess, StaffFeatureAccess, UnifastFeatureAccess (and per-user tables). The routes for staff and registrar are not gated by middleware that checks these flags; visibility is likely controlled in views (menus). If a staff user has a feature disabled, they might still access the route if they know the URL. Recommendation: add middleware or policy checks that respect these feature flags for staff and unifast routes where appropriate.
- **Registrar approveResponse second-semester rule:** Registrar enforces “Enrollment to Second Semester requires that the student was enrolled in First Semester of the same school year, or has Student Type Transferee or Returnee.” AdminAccountController::enrollApplication does not enforce this. Recommendation: centralize this rule in a service and call it from both admin and registrar approval flows.

---

## 7. Input and Output Data Validation

### 7.1 Where Validation Exists

- **Login:** `portal_type` required, in: student,dcomc,admin; email required (email format for non-student); password required.
- **Admin accounts:** store/update validate name, email, role (in User::roles()), department_id exists, faculty_type, program_scope, max_units, accounting_access; password min 6 on create; unique email (ignore current user on update).
- **Student profile (complete):** school_id unique (ignore current user), required name/gender/DOB/address/course/year_level/semester/student_type, etc.; year_level/semester in active AcademicYearLevel/AcademicSemester.
- **Student profile (edit):** Same fields except school_id; student_type optional in: Student,Freshman,Regular,Shifter,Transferee,Returnee,Irregular.
- **Student submit-enrollment:** form_id required exists:enrollment_forms; preferred_block_id nullable exists:blocks; response_id nullable exists:form_responses; answers required array. Duplicate submission prevented by checking existing response and process_status !== needs_correction.
- **Manual registration:** Full set of required/optional fields; email unique; course, year_level, semester, shift, student_type in allowed values.
- **Update student status record:** first_name, last_name, email unique, course (in program list), year_level, semester, block_id exists, shift, student_type; major required for secondary education.
- **Block assignment (service):** Uses AcademicNormalizer for year_level/semester; program from User or block; shift default day; gender and gender_group respected for block eligibility; capacity checks before assign.

### 7.2 Gaps or Risks

- **Form response answers:** Validation is “answers required array”; there is no schema validation against the form’s `questions` (e.g. required questions, types). Invalid or missing answers could be stored. Recommendation: validate answers against form definition (required keys, types) in RegistrarController and in student submit-enrollment.
- **Enrollment form incoming_*:** When deploying or saving a form, incoming_year_level and incoming_semester are not validated against AcademicYearLevel and AcademicSemester (e.g. that they exist and are active). Recommendation: add exists or in:... validation for these fields.
- **Block capacity:** BlockAssignmentService uses config `blocks.strict_50_per_block` and capacity; manual assignment in updateStudentStatusRecord allows any block_id without checking capacity. Recommendation: when assigning block_id in updateStudentStatusRecord, either check capacity or document that registrar can over-assign intentionally.
- **COR deploy:** CorDeploymentService::validateForDeployment checks professor and time per subject; it does not validate that room_id is set (only that professor and times are present). Consider requiring room for each slot for a complete COR.

---

## 8. Potential Issues or Logical Problems

### 8.1 Bugs / Logic Risks

1. **Admin enrollApplication school year:** Admin can enroll by response ID without school year scope; registrar cannot (forSelectedSchoolYear). Inconsistent and may allow enrolling wrong term.
2. **Second-semester rule only in Registrar:** approveResponse enforces first-semester-or-transferee/returnee; enrollApplication does not. Risk of inconsistent policy.
3. **Debug logging in RoleMiddleware:** `RoleMiddleware` writes to `debug-c1abed.log` on role mismatch. Should be removed or guarded by app.debug for production.
4. **Student email sync on profile:** On profile completion, if role is student and school_id is set, email is updated to school_id. If school_id is used as login identifier, this is correct; ensure password reset and other flows use the same identifier.

### 8.2 Design / Consistency

5. **Duplicate approval logic:** enrollApplication and approveResponse duplicate the same flow (user update, BlockAssignmentService, FormResponse update, Assessment). Could be refactored to a single service method called by both controllers.
6. **Staff vs Registrar:** Staff has the same approve/reject and block-change approve/reject routes as registrar. If the intent is to restrict staff from approving, routes should be split and staff routes should not include approve/reject, or feature flags should be enforced in middleware.
7. **Block code uniqueness:** blocks.code is unique; BlockAssignmentService generates code via suggestNextBlockCode. If two requests create a block concurrently for the same program/year/semester, duplicate code could occur. Consider unique constraint on (program_id, year_level, semester, shift, code) or serialization when creating new blocks.

### 8.3 Missing Validations

8. **Answers vs form questions:** No server-side validation that submitted answers match form question keys and types.
9. **Incoming year/semester:** Not validated against academic reference tables when saving/deploying forms.
10. **Over-assign block:** updateStudentStatusRecord does not check block capacity when setting block_id.

---

## 9. Real-World Applicability (Philippine University/College)

### 9.1 Alignments

- **Daraga Community College (DCOMC) context:** README and docs reference a real college; structure (programs, blocks, year levels, semesters, school year) matches Philippine higher education.
- **Enrollment flow:** Open/close enrollment, form per year/semester, approval/rejection, needs correction, block assignment, and COR generation mirror common registrar workflows.
- **Student types:** Freshman, Regular, Shifter, Transferee, Returnee, Irregular with status colors (e.g. yellow irregular, blue returnee, green transferee) are standard.
- **UniFast:** Assessment and eligibility tracking support government scholarship (UniFAST) reporting.
- **Blocks and shifts:** Day/night shift and gender_group (mixed/male/female) support typical grouping.
- **Dean and department:** Department-scoped scheduling and professor load (permanent/COS/part-time, max units) align with college structure.
- **COR as immutable snapshot:** Once deployed, COR does not change when schedule is edited, which matches the need for a fixed registration record.

### 9.2 Gaps for Real-World Use

- **Payment:** No payment gateway or billing integration; only fee configuration and assessment. Real schools often need payment confirmation before finalizing enrollment.
- **Accounting role:** README mentions “Accounting Officer” as missing; no dedicated accounting role or payment posting.
- **Instructor role:** No separate “instructor” role; faculty are users (professors) managed by dean. Instructors do not have a dedicated portal to view their classes/load in the explored code.
- **Academic calendar:** AcademicCalendarSetting and school year exist; fine-grained calendar events (enrollment period start/end, deadline per level) are not fully explored in this analysis.
- **Notifications:** No in-app or email notifications for students (e.g. “Application approved,” “COR available”) or for staff (e.g. new application, block change request).
- **Audit trail:** Reviewed_by and reviewed_at are stored on FormResponse and Assessment; no full audit log for all sensitive actions (e.g. block transfer, COR delete-block).

Overall, the system accurately simulates a Philippine college enrollment and registration environment for the implemented features; payment, dedicated accounting/instructor roles, and notifications would strengthen real-world applicability.

---

## 10. Recommendations for Improvement

### 10.1 Architectural

- **Centralize enrollment approval:** Introduce `EnrollmentApprovalService` (or similar) that performs user update, block assignment, FormResponse update, and Assessment update. Call it from both `AdminAccountController::enrollApplication` and `RegistrarController::approveResponse` to avoid drift and duplicate logic.
- **Enforce feature flags:** For staff and unifast, add middleware (or policy) that checks `StaffFeatureAccess` / `UnifastFeatureAccess` (and per-user overrides) before allowing access to feature-specific routes. Apply the same pattern for registrar if certain registrar features should be toggleable.
- **School year scope for admin:** Apply `forSelectedSchoolYear()` (or equivalent) to admin student-status list and to enrollApplication/rejectApplication/markNeedsCorrection so admin operates in the same school year context as registrar, or document and enforce “admin is global” with clear UX.

### 10.2 Logical / Business Rules

- **Second-semester rule:** Move the “Second Semester enrollment requires First Semester enrollment or Transferee/Returnee” rule into a shared service and invoke it from both admin and registrar approval flows.
- **Form answers validation:** Validate submitted answers against the enrollment form’s `questions` (required, types) on both student submit and (if needed) registrar side.
- **Incoming year/semester:** Validate `incoming_year_level` and `incoming_semester` against `AcademicYearLevel` and `AcademicSemester` when creating/updating/deploying enrollment forms.
- **Block capacity:** When updating a student’s block_id in updateStudentStatusRecord, either enforce capacity (and optionally allow overrides with a flag) or document over-assign as intentional.

### 10.3 Data and Validation

- **COR deployment:** Consider requiring room for each slot in validateForDeployment so printed COR always has room information.
- **Block creation race:** When auto-creating blocks, use DB transaction and unique index (e.g. program_id, year_level, semester, shift, code) or lock to avoid duplicate block codes under concurrency.
- **Assessment duplicate key:** Assessment::updateOrCreate uses user_id + school_year (null) + semester; ensure this matches intended uniqueness (e.g. one assessment per student per semester when school_year is null).

### 10.4 Operational and Security

- **Remove or guard debug log:** Remove or wrap the `RoleMiddleware` file_put_contents to `debug-c1abed.log` so it does not run in production.
- **Audit logging:** Add optional audit log for sensitive actions (enrollment approval, reject, block transfer, COR delete-block, role switch) for accountability.
- **Notifications:** Add simple in-app or email notifications for students (application approved/rejected, COR ready) and for staff (new application, block change request) to improve real-world usability.

### 10.5 Documentation

- **README:** Update README to reflect current state: COR, blocks, scheduling, and assessment are implemented; gap list and roadmap can be updated to match FEATURE_OVERVIEW.md and this report.
- **API/flow docs:** Keep FEATURE_OVERVIEW.md and docs (e.g. SYSTEM_ARCHITECTURE_ANALYSIS.md, COR_DEPLOYMENT_AND_ARCHIVE.md) in sync with code so future changes preserve documented behavior.

---

## Summary

The DCOMC Enrollment & Registration System is a **Laravel 12 application** with a clear **three-portal auth**, **role-based access** (admin, registrar, staff, dean, unifast, student), and a **rich feature set**: enrollment forms and responses, block assignment and management, COR deployment and archive, scheduling (dean and registrar), assessments and UniFast, and reports/analytics. The **architecture** (MVC + services, middleware for role and profile) is sound; **data flow** from login → enrollment → approval → block assignment → COR is consistent and traceable. **Database** structure and relationships support these flows. Main **issues** are: inconsistent school year handling between admin and registrar approval, duplicate approval logic, missing validation of form answers and incoming year/semester, and optional hardening (feature-flag enforcement, second-semester rule in one place, block capacity and concurrency). The system **accurately simulates** a Philippine college enrollment environment for the implemented features; adding payment, notifications, and audit logging would align it further with real-world operations.

---

*End of report. All findings are evidence-based on the codebase under `c:\Users\JOHN ELMER\Desktop\DCOMC pt`.*

---

## 11. Applied Fixes (Post-Analysis)

The following improvements were implemented to address inconsistencies, gaps, and risks without changing flow or features:

- **EnrollmentApprovalService:** Centralized approval logic (second-semester rule, user update, block assignment, FormResponse update, Assessment) used by both `AdminAccountController::enrollApplication` and `RegistrarController::approveResponse`. Second-semester rule is enforced in one place for both admin and registrar.
- **Admin school year scope:** Admin student-status list now filters students to those with at least one `FormResponse` in the selected school year. Enroll, reject, needs-correction, and delete application use `FormResponse::forSelectedSchoolYear()->findOrFail($id)` so admin cannot act on applications outside the selected year.
- **Form answers validation:** `EnrollmentForm::validateAnswers(array $answers)` validates required question keys against the form’s `questions`; used on student submit-enrollment. Missing required answers return validation errors.
- **Incoming year/semester validation:** On deploy form, `incoming_year_level` and `incoming_semester` are validated against active `AcademicYearLevel` and `AcademicSemester` lists (saveForm already had Rule::in for these).
- **Block capacity check:** When updating a student’s `block_id` in `updateStudentStatusRecord`, the target block’s current count is checked against `effectiveMaxCapacity()`; if at or over capacity, a validation error is returned (unless the student is already in that block).
- **COR deploy room validation:** `CorDeploymentService::validateForDeployment` now requires `room_id` for each slot so COR always has room information.
- **RoleMiddleware:** Debug file logging (`debug-c1abed.log`) removed so production is not cluttered.
- **Block creation lock:** In `BlockAssignmentService::assignStudentToBlock`, before creating a new block, existing blocks for the scope are locked with `lockForUpdate()` and `oldestNonFullBlock` is re-run to avoid duplicate block codes under concurrency.
- **Feature-flag enforcement:** `CheckStaffFeatureAccess` and `CheckUnifastFeatureAccess` middleware added and registered. Staff routes for response approve/reject use `staff.feature:staff_admission_responses`; staff block-change-requests approve/reject use `staff.feature:staff_student_records_block_requests`. Unifast fees and reports routes use `unifast.feature:unifast_fees` and `unifast.feature:unifast_reports`. When a feature is disabled, users get 403.

**Additional fixes (innovation pass):**

- **Assessment uniqueness by school year:** `EnrollmentApprovalService` now uses `school_year` (enrollment school year) in the `Assessment::updateOrCreate` key, so the first parameter is `['user_id', 'school_year', 'semester']`. This ensures one assessment per student per school year per semester and avoids overwriting assessments from other years.
- **Student forgot password with School ID:** Password reset flow supports the student portal. When `portal_type=student` is sent (e.g. from the "Forgot password?" link on the student login page), the user can enter their Student ID / School ID. The controller looks up `User` where `role=student` and (`email` or `school_id` matches). The reset link is then sent to that user’s `email` column, so login and reset use the same identifier (students typically have `email` set to school_id or a school email).
