# Subject Settings: Strict Program & Year-Level Restrictions

## System goal

Subjects are **exclusive** to:
- One **Program/Course** (via `program_id`)
- One **Year Level** within that program (via `academic_year_level_id`)

A subject cannot be used in a different program or in a different year level. All assignment and schedule flows enforce this at the database and validation layers.

---

## 1. Database schema

### 1.1 `programs`

| Column         | Type         | Notes                    |
|----------------|--------------|--------------------------|
| `id`           | bigint PK    |                          |
| `program_name` | string       | Unique (e.g. "Bachelor of Elementary Education") |
| `created_at`   | timestamp    |                          |
| `updated_at`   | timestamp    |                          |

Defined in: `database/migrations/2026_02_28_100000_create_programs_table.php`

### 1.2 `subjects`

| Column                   | Type         | Notes                    |
|--------------------------|--------------|--------------------------|
| `id`                     | bigint PK    |                          |
| `code`                   | string       | Subject code (e.g. ENG 101) — maps to *subject_code* |
| `title`                  | string       | Subject name — maps to *subject_name* |
| `units`                  | unsigned int |                          |
| `program_id`             | bigint FK    | → `programs.id` (required for strict mode) |
| `academic_year_level_id` | bigint FK    | → `academic_year_levels.id` (represents *year level*) |
| `semester`               | string       | Optional (First Semester / Second Semester) |
| `major`                  | string nullable | Optional (e.g. for BSE majors) |
| `is_active`              | boolean      |                          |
| `created_at` / `updated_at` | timestamp |                          |

**Unique constraint:** `(program_id, academic_year_level_id, code)` so the same code can exist in different program/year combinations but not twice in the same program+year.

**Note:** Year level is stored as FK to `academic_year_levels` (e.g. "1st Year", "2nd Year") rather than as an integer 1–4. This keeps referential integrity and aligns with the rest of the app.

### 1.3 `academic_year_levels`

| Column       | Type     |
|-------------|----------|
| `id`        | bigint PK |
| `name`      | string   | e.g. "1st Year", "2nd Year" |
| `is_active` | boolean  |

### 1.4 `schedule_templates` (scope for COR / schedule form)

Templates store the scope for which subjects can be assigned:

| Column                   | Type    | Notes |
|--------------------------|---------|--------|
| `program`                | string  | Display/legacy; should match `programs.program_name` |
| `year_level`             | string  | Display/legacy; should match `academic_year_levels.name` |
| `program_id`             | bigint FK nullable | → `programs.id` (synced on save; used for validation) |
| `academic_year_level_id` | bigint FK nullable | → `academic_year_levels.id` (synced on save; used for validation) |

When `program_id` and `academic_year_level_id` are set, validation uses these IDs so subject scope is enforced by ID, not by string. Migration: `2026_02_28_000020_add_program_and_year_level_fk_to_schedule_templates.php`.

---

## 2. Backend validation rules

### 2.1 When creating/updating a schedule template or assigning subjects

On:
- **Schedule form update** (`RegistrarScheduleController::update`)
- **Subject list update** (`RegistrarScheduleController::updateSubjects`)

the system validates:

1. **Subject belongs to template scope**
   - Resolve template scope to IDs: `program_id`, `academic_year_level_id` (from `schedule_templates` or from `program`/`year_level` names).
   - For each submitted `subject_id`:
     - `Subject.program_id` must equal the template’s `program_id`.
     - `Subject.academic_year_level_id` must equal the template’s `academic_year_level_id`.
   - If any subject does not match:
     - Return validation error: **"This subject does not belong to this program or year level."**

2. **Scope must be set before subjects**
   - If program or year level is missing/empty and the user submits subject IDs, clear subject IDs or return an error (e.g. "Set Program and Year Level on this schedule form before adding subjects.").

### 2.2 Example validation (pseudocode)

```php
// Resolve template scope to IDs
$programId = $template->program_id ?? Program::where('program_name', $template->program)->value('id');
$yearLevelId = $template->academic_year_level_id ?? AcademicYearLevel::where('name', $template->year_level)->value('id');

if (!$programId || !$yearLevelId) {
    return 'Invalid program or year level.';
}

$validIds = Subject::query()
    ->forProgramAndYear($programId, $yearLevelId)
    ->whereIn('id', $subjectIds)
    ->pluck('id')
    ->all();

$invalid = array_diff($subjectIds, $validIds);
if (!empty($invalid)) {
    return 'This subject does not belong to this program or year level.';
}
```

Validation is applied **server-side** on every update; it does not rely on the frontend.

---

## 3. API: filter subjects by program and year

Subjects are never returned globally; they are filtered by scope.

### 3.1 Endpoint

```
GET /registrar/schedule/subjects?program_id={id}&academic_year_level_id={id}
```

- **program_id** (required): `programs.id`
- **academic_year_level_id** (required): `academic_year_levels.id`

### 3.2 Behavior

- Query: `Subject::forProgramAndYear($programId, $yearLevelId)->where('is_active', true)->orderBy('semester')->orderBy('code')`.
- Response: JSON list of subjects for that program and year only (e.g. `id`, `label`, `units`).
- Do **not** load all subjects; filtering is done in the database.

### 3.3 Example response

```json
{
  "subjects": [
    { "id": "1", "label": "ENG 101 - Basic English", "units": 3 },
    { "id": "2", "label": "MATH 101 - College Algebra", "units": 3 }
  ]
}
```

Route name: `registrar.schedule.subjects`  
Controller: `RegistrarScheduleController::subjectsForScope`.

---

## 4. Frontend: schedule form (create/edit)

### 4.1 Flow

1. User selects **Program** (from `programs` table).
2. User selects **Year Level** (from `academic_year_levels`).
3. **Subject dropdown** is populated only via the API with:
   - `program_id` = selected program’s ID
   - `academic_year_level_id` = selected year level’s ID

Subjects are **not** loaded until both program and year are selected. The dropdown only shows subjects for that program and year.

### 4.2 Edge cases

| Case | Behavior |
|------|----------|
| Program changes | Reset selected subjects (clear `subject_ids`), refetch subject list for new program + current year (if both set). |
| Year level changes | Reset selected subjects, refetch subject list for current program + new year (if both set). |
| Program or year empty | Subject list empty; do not call API; show message e.g. "Select Program and Year Level to load subjects." |
| Form submit | Backend re-validates all subject IDs against template’s program_id and academic_year_level_id (or resolved names). No reliance on frontend-only filtering. |

### 4.3 Implementation details

- Program and year selects expose `data-program-id` and `data-year-level-id` so the frontend can call the API with IDs.
- On change of program or year: clear `subject_ids_json`, dispatch a custom event (e.g. `schedule-scope-change`) with `programId` and `academicYearLevelId`. The subject-picker component listens and calls `GET /registrar/schedule/subjects?program_id=...&academic_year_level_id=...`, then replaces the dropdown options and resets the selected subjects.
- On initial load, the server passes the already-filtered subject list for the template’s program and year so the dropdown is correct without an extra request when scope is already set.

---

## 5. Access restriction summary

| Rule | Enforcement |
|------|-------------|
| Subject belongs to one program | `subjects.program_id` FK; unique (program_id, academic_year_level_id, code). |
| Subject belongs to one year level | `subjects.academic_year_level_id` FK. |
| Schedule form can only assign subjects in scope | Backend validates every submitted subject_id with `Subject::forProgramAndYear($programId, $yearLevelId)`. |
| No cross-program use | API and validation only allow subjects where `program_id` and `academic_year_level_id` match the template scope. |
| No cross-year use | Same as above; year is part of the scope. |
| API tampering | Backend ignores any subject_id that does not belong to the resolved program_id and academic_year_level_id; returns validation error. |

---

## 6. Subject Settings UI

- Subject Settings are organized by **Program** (folder) → **Year Level** (folder) → list of subjects for that program and year.
- When creating a subject, user chooses **Program** (from `programs`) and **Year Level** (from `academic_year_levels`). Stored as `program_id` and `academic_year_level_id`.
- Only subjects for the selected program and year are shown in that folder; the list is filtered by `program_id` and `academic_year_level_id` at query time.

This keeps the same strict program/year segregation in both Settings and Schedule/COR flows.
