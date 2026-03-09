# Fee System: Multi-Tenant Isolation by Program + Year Level

## System goal

Fees are **exclusive** to:
- One **Program/Course** (via `program_id`)
- One **Year Level** within that program (via `academic_year_level_id`)

A fee cannot be used in a different program or in a different year level. Same strict isolation as Subjects.

---

## 1. Database

### `fees` table (additions)

| Column                   | Type    | Notes |
|--------------------------|---------|--------|
| `program_id`             | bigint FK nullable | → `programs.id` |
| `academic_year_level_id` | bigint FK nullable | → `academic_year_levels.id` |

- Existing columns `program` (string) and `year_level` (string) are kept for display/legacy; they are synced when saving by ID.
- Migration: `2026_02_28_100003_add_program_and_year_level_fk_to_fees.php` (adds columns, backfills from strings, adds FKs).

---

## 2. Backend validation

When **creating/updating a schedule template** and the form includes fee entries:

1. Resolve template scope to `program_id` and `academic_year_level_id`.
2. For each submitted `fee_id` in the fee entries:
   - Fee must satisfy `Fee.program_id == template.program_id` and `Fee.academic_year_level_id == template.academic_year_level_id`.
3. If any fee does not belong to that scope:
   - Return validation error: **"This fee does not belong to this program or year level."**
4. If program or year level is not set, fee entries are cleared (no fees saved for that scope).

Validation is done in `RegistrarScheduleController::update()` via `validateFeeIdsForScope()`.

---

## 3. API: filter fees by program and year

**Endpoint:** `GET /registrar/schedule/fees?program_id={id}&academic_year_level_id={id}`

- **program_id** (required): `programs.id`
- **academic_year_level_id** (required): `academic_year_levels.id`

Returns only fees where `program_id` and `academic_year_level_id` match (strict). Route name: `registrar.schedule.fees`.

---

## 4. Frontend (schedule form)

- **Fees section:** Only fees for the template’s program and year are loaded (server-side: `Fee::feesForScope($programId, $academicYearLevelId)`).
- **Program or Year Level change:** Subject list is reset (existing behavior), and all fee checkboxes are unchecked so no out-of-scope fees are submitted.
- **Save:** Backend re-validates all fee IDs; invalid IDs trigger the error above.

---

## 5. Fee Settings (Registrar)

- **Folder structure:** Program → Year → fee table (unchanged).
- **Loading:** For a chosen program + year, fees are loaded by `program_id` and `academic_year_level_id` (resolved from program name and year name).
- **Saving:** `updateFeeTable` and `storeFee` set and use `program_id` and `academic_year_level_id` so every fee is tied to one program and one year level.

---

## 6. COR (student view)

When displaying assessed fees on the COR, only fees that belong to the deployed template’s `program_id` and `academic_year_level_id` are shown. Fees that do not match the scope are excluded even if their IDs were stored earlier.

---

## 7. Access restriction summary

| Rule | Enforcement |
|------|-------------|
| Fee belongs to one program | `fees.program_id` FK; schedule validation checks match. |
| Fee belongs to one year level | `fees.academic_year_level_id` FK; schedule validation checks match. |
| Schedule form only allows fees in scope | Backend validates every fee_id with `Fee::forProgramAndYear($programId, $academicYearLevelId)`. |
| No cross-program / cross-year use | API and validation only allow fees whose program_id and academic_year_level_id match the template scope. |
| Program/year change clears fee choices | Frontend unchecks all fee checkboxes on program or year change. |
