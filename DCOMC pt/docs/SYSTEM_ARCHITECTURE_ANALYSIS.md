# System Architecture Analysis: Diagram vs Implementation

This document analyzes the provided system architecture diagram against the actual DCOMC enrollment system codebase, documents all roles, database structure, feature flows, and simulates real-world use in a Philippines college setting.

---

## 1. Architecture Diagram vs Actual System

### 1.1 What the Diagram Shows

- **User Layer:** Student; Registrar/Administrator  
- **Client:** Web Browser; Campus LAN/Intranet; Local Data Security Restricted Access  
- **Application Layer:** User Authentication; Student Portal (Profile, Enrollment Application, Status Checking); Admin Dashboard (Application Review, Student Records, Enrollment Management)  
- **Processing Layer:** Enrollment Status Tracking; Enrollment Approval or Rejection; Course & Section Assignment; Campus Application Server  
- **Data:** Central Enrollment Database (MariaDB/MySQL)  
- **Outputs:** Enrollment Form, Certificate of Registration, Enrollment Statistics, Student Lists, Spreadsheet Records  

### 1.2 Match Assessment

| Diagram Component | Implementation | Match? |
|-------------------|----------------|--------|
| **Student** | `User::ROLE_STUDENT`; Student Portal (profile, enrollment form, status, COR, block-change request) | ✅ Yes |
| **Registrar/Administrator** | **Split in code:** `admin`, `registrar`, `staff`, `dean`, `unifast` — all use DCOMC login except admin (admin portal) | ⚠️ Partial: diagram merges roles; system has 5 distinct staff roles |
| **Web Browser** | Laravel Blade + Vite, Tailwind, Alpine.js; web routes | ✅ Yes |
| **User Authentication** | `AuthenticatedSessionController`; portal_type `student` / `dcomc` / `admin`; role-based redirect to `/{role}/dashboard` | ✅ Yes |
| **Student Portal** | Profile management, enrollment application (form + submit), status checking, COR view, block-change request | ✅ Yes |
| **Admin Dashboard** | Admin: accounts, student-status (enroll/reject/needs-correction), blocks, block-change-requests, reports, workflow-qa, staff/registrar access, role-switch | ✅ Yes (admin subset) |
| **Application Review / Enrollment Management** | Registrar & Staff: registration (manual, builder, responses, approve/reject); Admin: student-status (enroll/reject/needs-correction) | ✅ Yes |
| **Student Records** | Admin/Registrar/Staff: students list, students-explorer, block-explorer, student COR, block assignments | ✅ Yes |
| **Enrollment Status Tracking** | `form_responses.approval_status`, `process_status` (pending, approved, rejected, needs_correction, scheduled, completed); dashboard QA counts | ✅ Yes |
| **Enrollment Approval/Rejection** | `AdminAccountController::enrollApplication`, `rejectApplication`, `markNeedsCorrection`; `RegistrarController::approveResponse`, `rejectResponse` | ✅ Yes |
| **Course & Section Assignment** | **Blocks** = sections; `BlockAssignmentService::assignStudentToBlock()`; preferred_block_id from form or auto-assign; `users.block_id`, blocks.current_size | ✅ Yes |
| **Central Database** | Laravel + MySQL/MariaDB; migrations for users, enrollment_forms, form_responses, blocks, programs, etc. | ✅ Yes |
| **Enrollment Form (output)** | Generated/filled via enrollment form builder + form_responses; COR as output | ✅ Yes |
| **Certificate of Registration** | `student_cor_records`, `CorViewController`, COR archive, irregular COR archive, deploy from schedule | ✅ Yes |
| **Enrollment Statistics / Student Lists / Data Export** | Reports (index/export), Analytics (index/export), CSV exports | ✅ Yes |

### 1.3 Gaps / Additions in Implementation (Not in Diagram)

- **Roles:** Diagram shows only “Registrar/Administrator.” The system has **admin**, **registrar**, **staff**, **dean**, **unifast** — each with distinct routes and feature access. **Dean** handles department-scoped scheduling and COR deploy; **Unifast** handles assessments (UniFAST eligibility), fees, and reports; **Staff** has feature toggles and a subset of registrar functions.
- **Unifast:** Not shown in the diagram. Implemented as full role with dashboard, assessments (eligibility, export), fee settings (shared backend), and reports, gated by `unifast_feature_accesses` / `unifast_feature_user_accesses`.
- **Staff role:** Distinct from registrar; access controlled by `staff_feature_accesses` and per-user overrides.
- **Dean role:** Department-scoped scheduling, room utilization, COR deploy/fetch, professor workload — not represented in the diagram.
- **Irregular students:** Irregular enrollment, irregular COR archive, `student_block_assignments`, create schedule for irregulars — beyond “standard” course/section assignment in the diagram.
- **Block management:** Block explorer, transfer, rebalance, promotion, block-change requests — more than “course & section assignment” in the diagram.
- **Academic setup:** School years, semesters, year levels, subjects, fees, professors, rooms, programs, departments — all in DB and settings (registrar/dean); diagram does not detail these.
- **Payment:** Diagram does not show payment processing; the system has fee configuration and assessment (e.g. income classification, UniFAST eligibility) but no payment gateway in the explored code.

**Conclusion:** The diagram is **correct at a high level** and matches the core flow (students apply → auth → application review → approval/rejection → block assignment → COR/reports). It does **not** show the full role set (registrar, admin, staff, dean, unifast), Unifast integration, Dean scheduling, or irregular/block-management features. For a “simple” architecture picture it is accurate; for a full capability view it is incomplete.

---

## 2. Roles, Flow, and Function (All Features)

### 2.1 Role Definitions (`User` model, `users.role`)

| Role    | Constant              | Portal  | Description |
|---------|------------------------|---------|-------------|
| student | `ROLE_STUDENT`         | Student | Enrolls, profile, status, COR, block-change request |
| admin   | `ROLE_ADMIN`           | Admin   | Accounts, student-status, blocks, reports, workflow-qa, staff/registrar access, role-switch |
| registrar | `ROLE_REGISTRAR`     | DCOMC   | Full registration, settings (school years, blocks, subjects, fees, professors, rooms, staff/unifast access), COR archive, irregularities, block management |
| staff   | `ROLE_STAFF`           | DCOMC   | Subset of registrar (registration, students, blocks, reports, assessments); gated by `staff_feature_accesses` |
| dean    | `ROLE_DEAN`            | DCOMC   | Department-scoped scheduling, room utilization, COR deploy, professor workload, professors/rooms settings |
| unifast | `ROLE_UNIFAST`         | DCOMC   | Assessments (list, export, eligibility), fees (if enabled), reports (if enabled); gated by `unifast_feature_accesses` |

- **Auth:** `AuthenticatedSessionController`: `portal_type` `admin` → only `role === 'admin'`; `dcomc` → `registrar`, `staff`, `dean`, `unifast`; `student` → only `student`. Redirect after login: `/{role}/dashboard`.
- **Effective role:** Admins can use “role switch” (session) to act as another role; `User::effectiveRole()` returns session role when switch is active.

### 2.2 Student Flow (IRL Simulation — Philippines College)

1. Student opens **Student Portal** (e.g. `https://enrollment.school.edu.ph` or campus LAN).
2. Logs in with email/password (portal_type = student).
3. If profile not complete: **Profile Management** (first name, last name, address, family/income, etc.).
4. **Enrollment Application:** Selects active enrollment form (by school year/semester), fills questions, may choose preferred block → submits → `FormResponse` created with `process_status = pending`.
5. **Status Checking:** Dashboard shows status (pending / approved / rejected / needs_correction). If needs_correction, can resubmit.
6. When approved: Registrar/Admin assigns block via `BlockAssignmentService`; student gets `block_id`, year_level, semester; may get **Certificate of Registration** (COR) after schedule is deployed.
7. Optional: **Block change request** (reason, target block) → pending until admin/registrar approves/rejects.

Data: `users` (profile, block_id, year_level, semester, student_status), `form_responses` (answers, preferred_block_id, assigned_block_id, approval_status, process_status), `student_cor_records` (COR snapshot).

### 2.3 Registrar Flow

- **Dashboard:** QA counts (needs_correction, approved, scheduled, completed).
- **Registration:** Manual registration; Form Builder (create/edit forms, deploy per school year); Responses (list/folder, approve/reject).
- **Students / Block Explorer / Irregularities:** Student list, block-tree, irregular schedule create/edit, COR view.
- **Blocks:** List, transfer, rebalance, promotion, block-change-requests.
- **Program schedule / Templates / Deploy:** Schedule by program, templates, deploy COR to `student_cor_records`.
- **COR Archive / Irregular COR Archive:** View/print past CORs by program/year/semester or by date/deployed-by.
- **Settings:** School years, semesters, year levels, blocks, subjects, fees, professors, rooms, staff-access, unifast-access.
- **Reports / Analytics:** Index and export.

Session: `selected_school_year_id` (EnsureSelectedSchoolYear) for filtering.

### 2.4 Admin Flow

- **Dashboard:** Same QA counts as registrar.
- **Accounts:** CRUD users (any role).
- **Student Status:** List applications; enroll, reject, mark needs_correction, delete.
- **Blocks / Block-change-requests:** View, approve/reject changes.
- **Reports / Workflow QA:** Reports and workflow checks.
- **Settings:** Staff access, Registrar access (feature toggles).
- **Role switch:** Temporarily act as another role (session); must switch back before logout.

### 2.5 Staff Flow

- Same DCOMC login; dashboard and a **subset** of registrar: registration (manual, builder, responses, approve/reject), students-explorer, block-explorer, blocks, block-change-requests, irregularities, COR archives, program-schedule, analytics, reports, fees (if allowed), student-status patch, **assessments** (index, export, status update).
- Access controlled by `staff_feature_accesses` and per-user overrides.

### 2.6 Dean Flow

- **Dashboard;** **Scheduling** (scope-based); **Room utilization;** **Schedule by scope** (department-scoped); **Deploy/Fetch COR;** **COR archive;** **Settings:** professors, rooms; **Manage professor:** teaching load, assignments, max-units, schedule-selection-limit.
- Middleware `dean.department` ensures department scope.

### 2.7 Unifast Flow

- **Dashboard;** **Assessments:** list, export CSV, set `unifast_eligible` per assessment; **Settings/Fees** (same Fee model as registrar, Unifast-specific routes); **Reports:** index, export.
- Features `unifast_fees`, `unifast_reports` gated by `UnifastFeatureAccess::isEnabledForUser($user, feature)`.

---

## 3. Database Structure (Values, Variables, Connections, Relationships)

### 3.1 Core Tables (Enrollment & Workflow)

- **users**  
  id, name, email, password, role, department_id, (department_scope, created_by_*), school_id, year_level, semester, school_year, block_id, shift, profile fields (first_name, last_name, gender, date_of_birth, address, family/income, etc.), student_status, student_type, status_color, profile_completed, faculty_type, program_scope, max_units, schedule_selection_limit, assigned_units, accounting_access, …  
  - **Relations:** block_id → blocks; department_id → departments; hasMany formResponses, assessments, blockAssignments (StudentBlockAssignment), subjectCompletions.

- **enrollment_forms**  
  id, title, description, questions (JSON), is_active, assigned_year, assigned_semester, incoming_year_level, incoming_semester, school_year_id  
  - **Relations:** school_year_id → school_years; hasMany form_responses.

- **form_responses**  
  id, enrollment_form_id, user_id, answers (JSON), preferred_block_id, assigned_block_id, approval_status, process_status, process_notes, reviewed_by, reviewed_at, reviewed_by_role  
  - **Relations:** enrollment_form_id → enrollment_forms; user_id → users; preferred_block_id, assigned_block_id → blocks.

- **blocks**  
  id, code, program, major, year_level, semester, shift, gender_group, capacity, current_size, is_active, program_id, school_year_label, …  
  - **Relations:** program_id → programs; hasMany users (users.block_id).

- **block_change_requests**  
  id, student_id, from_block_id, to_block_id, reason, status (e.g. pending)  
  - **Relations:** student_id → users; from_block_id, to_block_id → blocks.

- **student_block_assignments**  
  For irregulars: user_id, block_id, … (multiple block assignments per student).

- **student_cor_records**  
  Immutable COR: student_id, subject_id, block_id, program_id, year_level, semester, school_year, professor/room/days/time snapshots, deployed_by, deployed_at, cor_source.

- **assessments**  
  user_id, school_year, semester, income_classification, assessment_status, unifast_eligible, reviewed_by, reviewed_at  
  - **Relations:** user_id → users.

### 3.2 Academic & Reference

- **school_years**, **academic_semesters**, **academic_year_levels**, **programs**, **departments**, **subjects**, **raw_subjects**, **rooms**, **fees**, **fee_categories**, **schedule_templates**, **class_schedules**, **scope_schedule_slots**, **professor_subject_assignments**, **student_subject_completions**, **cor_scopes**, **academic_calendar_settings**, **block_transfer_logs**, etc.

### 3.3 Feature Access (Role Toggles)

- **registrar_feature_accesses** (global) + per-user overrides (registrar).
- **staff_feature_accesses** (global) + **staff_feature_user_accesses** (per user).
- **unifast_feature_accesses** (global) + **unifast_feature_user_accesses** (per user).

### 3.4 Key Relationships Summary

- User ↔ Block (many-to-one for regular; one-to-many via student_block_assignments for irregulars).
- User ↔ FormResponse (one-to-many); FormResponse ↔ EnrollmentForm (many-to-one); FormResponse ↔ Block (preferred/assigned).
- Block ↔ Program; Block ↔ Users (section “members”).
- Enrollment status and workflow live in form_responses (approval_status, process_status) and user (student_status, block_id, year_level, semester).
- COR data: class_schedules / scope_schedule_slots → deploy → student_cor_records (and COR archive views).

---

## 4. End-to-End Flow (Alignment with Diagram)

1. **User layer:** Student or Staff (registrar/admin/staff/dean/unifast) → **Web browser**, optionally via **Campus LAN** (implementation does not enforce LAN; security is auth + role).
2. **Application layer:** **User Authentication** (portal + role) → **Student Portal** or **Admin/DCOMC Dashboard** (depending on role).
3. **Processing layer:**  
   - **Enrollment status tracking:** form_responses.process_status, dashboard QA counts.  
   - **Enrollment approval/rejection:** AdminAccountController / RegistrarController (enroll, reject, needs_correction).  
   - **Course & section assignment:** BlockAssignmentService.assignStudentToBlock (blocks = sections); updates users.block_id, blocks.current_size.
4. **Data layer:** All persistent state in **Central Enrollment Database** (MySQL/MariaDB via Laravel migrations).
5. **Outputs:** Enrollment form (builder + form_responses), **Certificate of Registration** (student_cor_records, CorViewController, archives), **Enrollment statistics / Student lists / Data export** (reports, analytics, CSV).

So: the **flow** in the diagram (student applies → review → approve/reject → assign section → COR and reports) **matches** the implementation. The **structure** (three tiers: application, processing, database) also matches. The main difference is **granularity of roles** and **extra features** (Unifast, Dean, Staff, irregulars, block management) not drawn in the diagram.

---

## 5. Summary: Is the Diagram Correct?

- **Yes**, for the intended “simple” system architecture: it correctly shows students and administrators, web interface, authentication, student portal, admin dashboard, enrollment status and approval/rejection, course & section (block) assignment, central database, and main outputs (enrollment form, COR, statistics, lists, export).
- **Incomplete** relative to the full system: it does not show **registrar vs admin vs staff vs dean vs unifast**, **Unifast** (assessments, eligibility, fees, reports), **Dean** (department scheduling, COR deploy), **irregular** enrollment/COR, or **block management** (transfer, rebalance, promotion, block-change requests). It also does not show **payment** (system has fees/assessments but no payment gateway in the code reviewed).

For documentation, you can keep the diagram as the high-level picture and reference this document (and role-specific docs) for the full capability and all roles.
