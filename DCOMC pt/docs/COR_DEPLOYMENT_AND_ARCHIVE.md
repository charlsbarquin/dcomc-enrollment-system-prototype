# COR Deployment and Archive — Schema, Logic, and Access

## 1. Database schema

### scope_schedule_slots (extended)
- `id`, `program_id`, `academic_year_level_id`, `semester`, `subject_id`, `day_of_week`, `start_time`, `end_time`, `room_id`, `professor_id`, `school_year`, `timestamps`
- **Added:** `block_id` (nullable FK to blocks), `shift` (nullable string) for Schedule by Program filters (Program → Year → Block → Shift → Semester).

### student_cor_records (immutable snapshot)
- `id` — primary key  
- `student_id` — FK users (student)  
- `subject_id` — FK subjects  
- `professor_name_snapshot` — professor name at deploy time  
- `room_name_snapshot` — room name/code at deploy time  
- `days_snapshot` — e.g. `"Mon, Wed"`  
- `start_time_snapshot`, `end_time_snapshot` — time at deploy  
- `program_id`, `year_level`, `block_id`, `shift`, `semester`, `school_year` — scope of deployment  
- `deployed_by` — FK users (dean who deployed)  
- `deployed_at` — timestamp  
- `timestamps`  
- Indexes: `stucor_stu_prog_sem_sy` (student_id, program_id, semester, school_year), `stucor_scope_idx` (program_id, year_level, block_id, shift, semester, school_year).

Data in this table must not be updated when schedule, professor, or room change later.

---

## 2. Deployment algorithm

1. **Validate scope**  
   All required: Program, Year Level, Semester, School Year, **Block**. Shift is taken from the selected block when not provided.

2. **Validate schedule complete**  
   For each subject in scope, every slot must have:
   - `professor_id` set  
   - `start_time`, `end_time` set  

   If any subject/slot is missing these → reject deployment with a clear message.

3. **Fetch students**  
   - If `block_id` is set: students with `block_id` = selected block and block matches program, year level, semester, school year, shift.  
   - If `block_id` is null: blocks matching program, year level, semester, school year, shift; students with `block_id` in those blocks.  
   - Filter: `role = 'student'` and status considered active (e.g. `student_status` null or `'active'`).

4. **Build snapshots**  
   From `scope_schedule_slots` for the scope (and optional block/shift):  
   - Group by `subject_id`.  
   - Per subject: one snapshot row with professor name, room name, days (e.g. "Mon, Wed"), start time, end time (e.g. from first slot).

5. **Replace and insert**  
   - Delete existing `student_cor_records` for the same scope (same program_id, year_level, block_id, shift, semester, school_year).  
   - Insert one `student_cor_records` row per (student, subject) with snapshot and scope fields, `deployed_by`, `deployed_at`.

6. **Response**  
   Return success with counts (e.g. students_count, records_count) or error message.

---

## 3. Snapshot logic implementation

- **Service:** `App\Services\CorDeploymentService`.  
- **buildSnapshotFromSlots(Collection $slots):**  
  - Group slots by `subject_id`.  
  - For each subject: take first slot (by day) for professor and room names; collect all days (e.g. 1,3 → "Mon, Wed"); use first slot’s start_time/end_time.  
  - Return array keyed by subject_id: `professor_name_snapshot`, `room_name_snapshot`, `days_snapshot`, `start_time_snapshot`, `end_time_snapshot`.  
- **deploy()** uses these snapshots when building rows for `StudentCorRecord::insert()`.  
- No updates to `student_cor_records` after insert; later schedule/professor/room changes do not modify the archive.

---

## 4. Folder generation logic (COR Archive)

- **Path:** Program → Year Level → Semester → COR.  
- **Index:** List programs (Dean: department; Registrar: all).  
- **Program:** List distinct (year_level, semester) from `student_cor_records` where `program_id` = selected program.  
- **COR folder (show):** Program + Year Level + Semester fixed.  
  - **School year selector:** Dropdown from `school_years` (e.g. ordered by `start_year` desc).  
  - **Content:** For selected school year, list blocks that have at least one `student_cor_records` row for that program, year_level, semester, school_year.  
  - **Per block:** Show read-only schedule (subject, professor_name_snapshot, room_name_snapshot, days_snapshot, start/end_time_snapshot) grouped by block.  
- **Controller:** `CorArchiveController` (index, program, show).  
- **Routes:** Dean: `/dean/cor-archive`, `/dean/cor-archive/program/{programId}`, `/dean/cor-archive/{programId}/{yearLevel}/{semester}`. Registrar: same under `/registrar/cor-archive`.

---

## 5. School year auto-increment logic

- **Storage:** `school_years` table: `id`, `start_year`, `end_year`, `label` (e.g. "2025-2026").  
- **COR dropdown:** Uses `SchoolYear::orderByDesc('start_year')->pluck('label')`.  
- **Auto-add new year:**  
  - Implement in settings or a scheduled command: when current school year “ends” (e.g. by date or admin action), create a new row (e.g. `start_year = previous.end_year + 1`, `end_year = start_year + 1`, `label = "YYYY-YYYY"`).  
  - No edit/delete of existing `student_cor_records`; archive remains tied to the school year it was deployed with.

---

## 6. Access control rules

- **Deploy COR:** Only Dean. Validated in `DeanScheduleByScopeController@deployCor` (role and department). Program must belong to dean’s department.  
- **View archive:**  
  - **Dean:** COR Archive limited to programs in own department (`CorArchiveController`: filter programs by `department_id`).  
  - **Registrar:** Can view all (no department filter).  
  - **Student:** Can only view own COR (e.g. `StudentServicesController@cor` filtered by `student_id = auth()->id()`).  
- **Archive data:** Read-only; no update/delete of `student_cor_records` from archive UI. Deploy only inserts (and replaces scope for same scope before insert).

---

## 7. Archive retrieval query example

```php
// Read-only records for a scope (e.g. for archive view or student COR)
$records = StudentCorRecord::query()
    ->where('program_id', $programId)
    ->where('year_level', $yearLevel)
    ->where('semester', $semester)
    ->where('school_year', $schoolYear)
    ->when($blockId !== null, fn ($q) => $q->where('block_id', $blockId))
    ->when($studentId !== null, fn ($q) => $q->where('student_id', $studentId))
    ->with('subject')
    ->orderBy('subject_id')
    ->get();
```

Static helper used in codebase: `CorArchiveController::archiveRetrievalQuery($programId, $yearLevel, $semester, $schoolYear, $blockId, $studentId)`.

---

## Summary

- **Schedule by Program:** Dean configures schedule (subjects from Registrar COR scope; professor from Manage Professor; optional block_id/shift on slots).  
- **Deploy COR:** Dean clicks “Deploy COR”; system validates, fetches students, builds snapshots, (re)creates `student_cor_records` for the scope.  
- **Archive:** Program → Year Level → Semester → COR, with school year dropdown and blocks; data is read-only and immutable.  
- **School year:** Stored in `school_years`; dropdown populated from it; new year can be added via settings or automation.  
- **Security:** Deploy = Dean only; view archive = Dean (department) or Registrar (all); students see only their own COR.

---

## 8. Primary vs legacy schedule/COR path

- **Primary (Schedule by Program):** Dean edits schedule in **scope_schedule_slots** (per program, year level, semester, school year). No block on slots; one template per scope. Dean selects a **block** when clicking **Deploy COR**. The system writes immutable **student_cor_records** for students in that block. Students see COR from **student_cor_records** (certificate layout, snapshot schedule, enabled fees).
- **Legacy (Schedule Forms / shifters):** **schedule_templates** and **class_schedules** (per block) are still used for the old flow. Student COR uses **student_cor_records** first; if none exist, it falls back to **schedule_templates** + **class_schedules** for schedule text and template fee entries. Do not remove these tables without migrating data and updating all references.
- **Single source of scope:** Program name comes from **programs**; year level/semester from **academic_year_levels** / **academic_semesters**. **blocks** store `program_id` (FK) and denormalized `program`, `year_level`, `semester`, `school_year_label` for display and filtering. Block creation enforces unique **code** and sets **school_year_label** from current school year.
