# DCOMC Feature & Function Overview

This document lists each major function/feature and whether it is **wired and intended to work** based on routes, controllers, models, and data flow. Run the application and test each item to confirm in your environment.

---

## Authentication & Portals

| Feature | Status | Notes |
|--------|--------|--------|
| Student login | Wired | `/` → login-student view |
| DCOMC staff login | Wired | `/dcomc-login` |
| Admin login | Wired | `/admin-login` |
| Logout | Wired | Form POST to `logout` |

---

## Admin

| Feature | Status | Notes |
|--------|--------|--------|
| Admin Dashboard | Wired | QA counts, needs correction / approved / scheduled / completed |
| Admin Accounts | Wired | CRUD; program_scope for faculty |
| Admin Student Status | Wired | Same controller as registrar; filter by course, year, semester; enroll/reject/needs correction |
| Admin Blocks | Wired | RegistrarOperationsController; list/delete blocks |
| Admin Block Change Requests | Wired | Approve/reject |
| Admin Reports | Wired | ReportController index + export |
| Admin Analytics | Wired | AnalyticsController; course filter, export |
| Role switch | Wired | Admin can impersonate registrar/staff/etc. |

---

## Registrar – Admission / Registration

| Feature | Status | Notes |
|--------|--------|--------|
| **Admission** (section) | Wired | Sidebar dropdown |
| **Manual Register** | Wired | Manual registration page; saved forms, global toggle, program/course from Major::majorsByProgram() + Program |
| **Form Builder** | Wired | Create/edit/delete forms; get-form, save-form, delete-form APIs |
| **Responses** | Wired | List responses, folder view, approve/reject; process_status flow |
| Toggle global enrollment | Wired | Cache `global_enrollment_active` |
| Deploy form | Wired | Maps form to semester/year |
| Approve / Reject response | Wired | PATCH responses/{id}/approve, reject; process_status updated |

---

## Registrar – Student Records

| Feature | Status | Notes |
|--------|--------|--------|
| Student Status | Wired | Same as admin; filters, enroll, reject, needs correction |
| Blocks | Wired | List blocks; program list from Program model |
| **Block Explorer** | Wired | Tree (Programs → Year → Blocks); expandable program/year folders; student list per block; transfer, rebalance, promotion, transfer log |
| Block Requests | Wired | List, approve, reject block change requests |

---

## Registrar – Schedule & COR

| Feature | Status | Notes |
|--------|--------|--------|
| Schedule (menu) | Wired | Schedule templates index |
| Schedule forms (templates) | Wired | CRUD templates; program/year/semester from Program, AcademicYearLevel, AcademicSemester; deploy/undeploy |
| Schedule subjects per scope | Wired | subjectsForScope API |
| Schedule fees per scope | Wired | feesForScope API |
| COR Scope Templates | Wired | CorScopeController CRUD; program/year/semester/major scope |

---

## Registrar – Reports & Analytics

| Feature | Status | Notes |
|--------|--------|--------|
| Reports | Wired | ReportController; course filter; export CSV |
| Analytics | Wired | AnalyticsController; course filter; export |

---

## Registrar – Settings

| Feature | Status | Notes |
|--------|--------|--------|
| School Year | Wired | Generate, clear; SchoolYear model |
| Semesters | Wired | Store, toggle; AcademicSemester |
| Year Levels | Wired | Store, toggle; AcademicYearLevel |
| Blocks | Wired | Store block (program_id + program from Program), toggle |
| Subjects | Wired | Store, toggle; program_id from Program |
| Fees | Wired | Store, table update, toggle; program/year_level from Program & AcademicYearLevel |
| COR Scope Templates | Wired | Under Schedule section |

---

## Student

| Feature | Status | Notes |
|--------|--------|--------|
| Student Dashboard | Wired | Profile check, enrollment status, latest application, blocks, block change request, schedule, assessment |
| Student Profile | Wired | profile_completed; profile edit |
| Student Enrollment Form | Wired | When global open + form for year/semester; FormResponse; block preference; course from Program/majors |
| Block change request (student) | Wired | Submit request; list pending |

---

## Block & Data Flow (post-cleanup)

- **Programs**: Single source for program name (`program_name`) and abbreviation (`code`, e.g. BEED, BSED). Block naming uses `Program->code` when present.
- **Blocks**: `program_id` (FK to programs) and denormalized `program` (name) kept in sync; new blocks get `program_id` and `max_capacity`.
- **Users (students)**: `course` stores full program name; normalized from code (e.g. BEED → Bachelor of Elementary Education) via migration where applicable.
- **Capacity**: Block creation sets both `capacity` and `max_capacity`; reads use `effectiveMaxCapacity()` (prefers `max_capacity`).

---

## Redundancy Fixes Applied

1. **Program name vs code**: Added `programs.code`; block prefix comes from `Program->code` (fallback to config/abbreviate). No duplicate “BEED” vs “Bachelor of Elementary Education” as separate concepts—one program row has both.
2. **Block model**: Removed unused `course_id` from fillable.
3. **Block create (registrar settings)**: Sets `program_id` and `max_capacity`; program list for dropdown from `Program::orderBy('program_name')`.
4. **Block tree**: Label from `Program->program_name` when `program_id` set; blocks synced to programs via migrations.
5. **Users.course**: Migration normalizes stored code to full program name where it matches `programs.code`.

---

*Last updated: after redundancy cleanup and program code migration.*
