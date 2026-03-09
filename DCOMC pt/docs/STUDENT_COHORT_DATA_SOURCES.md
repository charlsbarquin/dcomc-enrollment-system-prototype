# Why Maria and Juan Can Have Different Data (Multiple Tables)

Cohort-related data (program, year level, semester, block) is stored in **several places**. COR matching can behave differently for two students if their data is not in sync across these sources.

## Tables that store similar cohort data

| Table / model      | Fields used for cohort                    | When they are set |
|--------------------|-------------------------------------------|--------------------|
| **users**          | `course`, `year_level`, `semester`, `block_id`, `major` | Registration, enrollment form answers, **manual edit** (Admin → Student Status), or when **enrolling an application** (year_level, semester only; course is not updated there). |
| **blocks**         | `program`, `year_level`, `semester`, `school_year_label` | When a block is created (e.g. by BlockAssignmentService) or edited in Settings → Blocks. |
| **enrollment_forms**| `assigned_year`, `assigned_semester`, `incoming_year_level`, `incoming_semester` | Form configuration (which cohort the form is for and where they are promoted). |
| **form_responses** | `preferred_block_id`, `assigned_block_id` | When a student submits a form (preference) and when an application is enrolled (assigned block). |
| **schedule_templates** | `program`, `year_level`, `semester`, `school_year`, `block_id`, `major` | When the registrar saves the COR scope on a schedule form. |

## How COR matching works (after the fix)

- COR uses the student’s **block** as the main source for program, year level, and semester when the student has a block assigned.
- It falls back to the **users** table only when the block does not have those fields.
- So: **Block** = single source of truth for “which cohort this student is in” for COR. If Juan’s **block** (e.g. BEED 1) has program=BEED, year_level=3rd Year, semester=First Semester, he will match the deployed schedule for that cohort even if his **user** row has different or empty values.

## Why Maria and Juan can differ

1. **Different ways they were assigned**
   - **Maria** might have been enrolled via the application workflow: her `year_level` and `semester` were set from the enrollment form, then she was assigned to a block. So her **user** row matches the block and the COR scope.
   - **Juan** might have been assigned to the same block (BEED 1) manually (e.g. only `block_id` set in Admin → Student Status) without his **user** `course` / `year_level` / `semester` being updated, or his user row might have different wording (e.g. “Third Year” vs “3rd Year”).

2. **Multiple tables, not always synced**
   - **users**: Updated by manual edits and (partially) by enrollment; **course** is not updated when enrolling an application.
   - **blocks**: Set when blocks are created/edited; not automatically copied back to every user in that block.
   - So the same logical cohort can be represented in **users** in one way and in **blocks** in another. COR now prefers the **block** so both Maria and Juan get consistent behavior when their block matches the deployed schedule.

## Recommendation

- When assigning or changing a student’s block, also set their **course**, **year_level**, and **semester** to match the block (or the same values used in the COR scope) so that **users** and **blocks** stay in sync for reports and other features that read from **users**.

---

## Canonical academic data (clean DB)

The app uses **one set of canonical values** for year level and semester:

- **Year levels:** `1st Year`, `2nd Year`, `3rd Year`, `4th Year` (table: `academic_year_levels`).
- **Semesters:** `First Semester`, `Second Semester` (table: `academic_semesters`).

The migration `2026_02_28_000016_normalize_academic_data_to_canonical` ensures these rows exist, normalizes all existing data in users, blocks, schedule_templates, enrollment_forms, assessments, class_schedules, and subjects to these values, and removes non-canonical rows from the reference tables. Settings only allow adding these canonical values so current and future students stay consistent.

---

## Clean data connections (display and sync)

- **Resolved cohort:** The `User` model exposes `resolved_program`, `resolved_year_level`, and `resolved_semester` (block’s values when the user has a block, otherwise the user’s own). Use these for display so COR, Student Status, reports, and analytics show accurate cohort data.
- **Sync on assign:** When a student is assigned to a block (enrollment flow, Admin/Registrar student edit, or block-change approval), the user’s `course`, `year_level`, and `semester` are set from the block so `users` and `blocks` stay in sync.
- **One-time sync:** Run `php artisan students:sync-cohort-from-blocks` to copy each student’s block program/year_level/semester into their user record. Use after data fixes or to clean existing records.
