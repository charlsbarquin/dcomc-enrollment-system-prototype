# Schedule by Program & COR — Spec Compliance

This document maps the **Professional System Development** specification to the codebase and notes any gaps.

---

## 1. Folder-Based Scope Logic ✅

**Spec:** The folder path (Schedule by Program / Program / Year Level / Semester) determines scope. Program, Year Level, and Semester must NOT be manually reselected in the form; they are derived from the folder being accessed.

**Implementation:**
- **Controller:** `DeanScheduleByScopeController::scheduleByScope()` — scope comes from query params `program`, `year`, `semester` (and `school_year`). No form fields for program/year/semester.
- **Views:** `resources/views/dashboards/dean-schedule-by-scope.blade.php` — folder navigation (programs → years → semesters) builds URLs; opening a semester shows the schedule form for that scope. Hidden inputs pass `program_id`, `academic_year_level_id`, `semester`, `school_year` to save/deploy.
- **COR Archive:** Same folder structure: COR Archive / Program / Year Level / Semester — Blocks. Implemented in `CorArchiveController` (index → program → year → show).

---

## 2. Schedule Form (Inside the Folder) ✅

**Spec:** Fields: Code, Title, Units, Day, Start, End, Room, Professor, School Year (dropdown), Block (dropdown — required only for deployment), Action.

**Implementation:**
- Schedule table shows rows per subject with: subject code/title (from `subjects`), units (from subject), Day, Start, End, Room (dropdown), Professor (dropdown), and actions. School Year is a dropdown above the table. Block is in a **separate “Deploy to a block”** section and is required only for the deploy form.
- **File:** `dean-schedule-by-scope.blade.php` (viewMode === 'table'): schedule form posts to `dean.schedule.slots.save`; deploy form posts to `dean.schedule.deploy-cor` with block_id required.

---

## 3. SAVE Function (Not Deployment) ✅

**Spec:** SAVE = database storage only. Not visible to students, not deployed, does not require block, does not appear in COR Archive. Draft/template inside Schedule by Program.

**Implementation:**
- **Save:** `DeanScheduleByScopeController::saveScopeScheduleSlots()` — stores/updates `scope_schedule_slots` (program_id, academic_year_level_id, semester, subject_id, day_of_week, start_time, end_time, room_id, professor_id, school_year). No block_id required. Students do not see this; it is not in COR Archive.
- **Table:** `scope_schedule_slots` = draft schedule (Schedule by Program only).

---

## 4. Block Selection ✅

**Spec:** After saving, the Dean selects a Block from a dropdown. Block is REQUIRED before deployment. Block and School Year must be selected for deploy.

**Implementation:**
- Block dropdown is in the “Deploy to a block” section with `required` on the select. School year is set via the page URL/query or dropdown and passed as hidden input in the deploy form.
- **File:** `dean-schedule-by-scope.blade.php` — deploy form includes `name="block_id"` (required) and `name="school_year"` (hidden).

---

## 5. DEPLOY Function ✅

**Spec:** Deploy is separate from Save. Takes saved schedule, attaches folder path metadata (Program / Year / Semester), selected Block, selected School Year; stores deployed schedule into COR Archive; resets the Schedule by Program form after successful deployment.

**Implementation:**
- **Controller:** `DeanScheduleByScopeController::deployCor()` — validates program_id, academic_year_level_id, block_id, semester, school_year; calls `CorDeploymentService::deploy()`.
- **Service:** `CorDeploymentService::deploy()` — reads slots from `scope_schedule_slots` for the scope; builds immutable records; deletes existing COR for same scope+block; inserts `student_cor_records`; deletes scope slots (resets form); returns success/failure.
- **Storage:** Deployed data is in `student_cor_records` (program_id, year_level, semester, block_id, school_year, schedule snapshots, deployed_by, deployed_at). COR Archive reads from this table.
- **Redirect:** After deploy, redirects to COR Archive with block id in the path.

---

## 6. COR Archive Storage Structure ✅ (with UI enhancement)

**Spec:** COR Archive / Program / Year Level / Semester — Blocks. Each deployed block appears as a **clickable box** (e.g. [ Block A – SY 2025-2026 ]). When clicked, the box **expands** and reveals the full deployed schedule. The schedule inside is **editable (like a template)**.

**Implementation:**
- **Folder structure:** `CorArchiveController` — index (programs), program (year levels), year (semesters), show (blocks list). Same path as Schedule by Program.
- **Blocks as boxes:** Blocks are listed with name/code and shift; each shows either the deployed schedule table or “No COR deployed”. **Enhancement:** Blocks are implemented as **expandable/collapsible boxes** (click to expand/collapse).
- **Editable template:** Currently the schedule inside the archive is **read-only** (snapshots from `student_cor_records`). The spec calls for “editable (like a template)” — a future enhancement could allow Dean/Registrar to edit the deployed snapshot (e.g. change professor/room/time for that block’s COR) via a separate edit flow or inline edit.

---

## 7. Student Access Logic (Automatic Scoping) ✅

**Spec:** After deployment, COR is automatically assigned to students who match: Program, Year Level, Semester, Block, School Year. Students who do not match must NOT see the COR. Student Panel → View COR shows accurate deployed data.

**Implementation:**
- **Deploy:** `CorDeploymentService::deploy()` uses `fetchStudentsForScope(programId, academicYearLevelId, blockId, shift, semester, schoolYear)` and creates one `student_cor_records` row per student per subject. Only students in the selected block (and matching program/year/semester/school year) receive records.
- **Student view:** `StudentServicesController::cor()` — queries `StudentCorRecord` where `student_id` = current user, `block_id` = student’s block, and optionally `program_id`, `semester`, `school_year` to match. Read-only; only deployed and matched COR is shown.
- **File:** `resources/views/dashboards/student-cor.blade.php` — displays COR subjects and schedule text from `student_cor_records`.

---

## 8. Important Rules Summary

| Rule | Status |
|------|--------|
| Save ≠ Deploy | ✅ Separate endpoints and logic |
| Save does NOT require block | ✅ Block only in deploy form |
| Deploy REQUIRES block | ✅ Required in deploy form |
| Deploy requires school year | ✅ Passed in deploy form |
| Deploy moves data into COR Archive | ✅ student_cor_records = archive storage |
| After deploy, form resets | ✅ Slots deleted for scope in deploy transaction |
| Archive entries as clickable expandable boxes | ✅ Implemented (expand/collapse per block) |
| Students only see deployed COR in correct scope | ✅ Query by student, block, program, semester, school_year |
| Folder path determines scope automatically | ✅ No manual reselection of Program/Year/Semester in form |

---

## 9. Database Design

| Concept | Table(s) | Notes |
|--------|----------|--------|
| Schedules (drafts) | `scope_schedule_slots` | program_id, academic_year_level_id, semester, subject_id, day_of_week, start_time, end_time, room_id, professor_id, school_year |
| Deployed COR | `student_cor_records` | student_id, subject_id, program_id, year_level, block_id, semester, school_year, professor_name_snapshot, room_name_snapshot, days_snapshot, start_time_snapshot, end_time_snapshot, deployed_by, deployed_at |
| Students | `users` (role=student) + block assignment | Block links student to program, year_level, semester |
| Blocks | `blocks` | program_id, year_level, semester, code, name, shift, school_year_label |

Student COR view query (conceptually):
`WHERE student_id = :student AND block_id = :student.block_id AND program_id = :student.program AND semester = :student.semester AND school_year = :selected_school_year`

---

## 10. UI Behavior

| Requirement | Status |
|-------------|--------|
| Schedule by Program: form remains editable after save | ✅ |
| Deploy button disabled until schedule saved, block selected, school year selected | ✅ Block and school year required in form; optional: disable Deploy until at least one slot exists (UX) |
| COR Archive: folder-based navigation | ✅ |
| COR Archive: block as clickable expandable box | ✅ |
| COR Archive: expand reveals schedule (editable template = future) | ⚠️ Read-only for now; editable can be added later |
| Student View COR: read-only, only matched deployed COR | ✅ |

---

## Files Reference

- **Schedule by Program (Dean):** `DeanScheduleByScopeController`, `resources/views/dashboards/dean-schedule-by-scope.blade.php`
- **Save slots:** `POST dean.schedule.slots.save` → `saveScopeScheduleSlots()`
- **Deploy:** `POST dean.schedule.deploy-cor` → `deployCor()` → `CorDeploymentService::deploy()`
- **COR Archive:** `CorArchiveController`, `resources/views/dashboards/cor-archive-*.blade.php`
- **Student View COR:** `StudentServicesController::cor()`, `resources/views/dashboards/student-cor.blade.php`
- **Models:** `ScopeScheduleSlot`, `StudentCorRecord`, `Block`, `Program`, `User`
