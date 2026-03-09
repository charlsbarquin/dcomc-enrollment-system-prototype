# COR Scope Template System

## System concept

The system enforces **multi-tenant isolation** of:
- **Subjects**
- **Assessed fees**

by **program_id**, **year level** (academic_year_level_id), **semester**, **school_year**, and optionally **major**.

A **COR Scope Template** is defined first by the Registrar. Scheduling and COR generation depend on that scope.

---

## 1. Database schema

### cor_scopes

| Column                   | Type    | Notes |
|--------------------------|---------|--------|
| id                       | bigint PK | |
| program_id               | FK → programs | Required |
| academic_year_level_id   | FK → academic_year_levels | Required |
| semester                 | string  | e.g. First Semester, Second Semester |
| school_year              | string  | e.g. 2024-2025 |
| major                    | string nullable | For BSE-style programs |
| created_by               | bigint nullable | Registrar user id |
| created_at, updated_at   | timestamps | |

**Unique:** (program_id, academic_year_level_id, semester, school_year, major)

### cor_scope_subjects

| Column       | Type  | Notes |
|--------------|-------|--------|
| id           | bigint PK | |
| cor_scope_id | FK → cor_scopes (cascade delete) | |
| subject_id   | FK → subjects (cascade delete) | |
| created_at, updated_at | timestamps | |

**Unique:** (cor_scope_id, subject_id)

### cor_scope_fees

| Column       | Type  | Notes |
|--------------|-------|--------|
| id           | bigint PK | |
| cor_scope_id | FK → cor_scopes (cascade delete) | |
| fee_id       | FK → fees (cascade delete) | |
| created_at, updated_at | timestamps | |

**Unique:** (cor_scope_id, fee_id)

---

## 2. Step 1: COR Scope Template (Registrar only)

- **Routes:** Settings → COR Scope Templates (list, create, edit, delete).
- **Create/Edit:** Registrar selects:
  - program_id
  - academic_year_level_id
  - semester
  - school_year
  - major (optional)
- **Default subjects:** Only subjects where `subject.program_id` and `subject.academic_year_level_id` match the scope are listed. Registrar selects which to include.
- **Default fees:** Only fees where `fee.program_id` and `fee.academic_year_level_id` match the scope are listed. Registrar selects which to include.
- **Validation:** Every selected subject_id and fee_id is validated server-side to belong to that program and year level. If not: *"One or more subjects/fees do not belong to this program and year level."*
- **Uniqueness:** One COR Scope per (program_id, academic_year_level_id, semester, school_year, major).

---

## 3. Step 2: Scheduling based on COR Scope

When **editing** a schedule template:

1. User sets **Program, Year Level, Semester, School Year** (and optional Major).
2. **Auto-load:** If the template’s subject list and fee list are **empty**, the system looks up a COR Scope for that combination. If found, it **pre-fills** the template’s subject_ids and fee entries from the COR Scope (one-time auto-load on load).
3. **Dropdowns:** Subject and fee dropdowns only show items for the selected program and year (existing API: `GET /registrar/schedule/subjects?program_id=...&academic_year_level_id=...` and `GET /registrar/schedule/fees?program_id=...&academic_year_level_id=...`).
4. User can **add, remove, or change** subjects and fees within that pool.

---

## 4. Auto-assign logic

On **schedule edit** (when opening the form):

1. If template has `program_id`, `academic_year_level_id`, `semester`, `school_year` set.
2. Query `cor_scopes` WHERE program_id, academic_year_level_id, semester, school_year (and major if applicable) match.
3. If found and the template’s subject_ids and fee entries are **empty**, update the template’s JSON with the COR Scope’s default subject_ids and default fee entries (so the form shows them pre-filled).
4. If **no COR Scope** exists for the selected configuration and the user tries to **save** the schedule, the backend returns: *"No COR Scope Template defined for this configuration. Define one in Settings → COR Scope Templates."*

---

## 5. Editing behavior

- Pre-loaded subjects and fees are shown as selected (controlled by template data).
- User can add (from restricted dropdown), remove, or change subjects and fees.
- Subject and fee dropdowns are filtered at query level by program_id and academic_year_level_id only.

---

## 6. Validation rules (critical)

On **schedule save** (update):

1. **COR Scope required:** If program_id, academic_year_level_id, semester, and school_year are set, a COR Scope must exist for that combination; otherwise the request is rejected with the message above.
2. **Subject isolation:** For every subject_id in the template, the backend checks `subject.program_id == schedule.program_id` and `subject.academic_year_level_id == schedule.academic_year_level_id`. If any fail: *"This subject does not belong to this program or year level."*
3. **Fee isolation:** For every fee_id in the template, the backend checks `fee.program_id == schedule.program_id` and `fee.academic_year_level_id == schedule.academic_year_level_id`. If any fail: *"This fee does not belong to this program or year level."*

Validation is **always** done on the backend. Frontend filtering does not replace it.

---

## 7. API endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | /registrar/schedule/subjects?program_id=&academic_year_level_id= | List subjects for scope (for dropdowns) |
| GET | /registrar/schedule/fees?program_id=&academic_year_level_id= | List fees for scope (for dropdowns) |

Subject and fee lists are filtered by program_id and academic_year_level_id at query level.

---

## 8. Isolation enforcement

- **Database:** FKs and unique constraints on cor_scopes and pivot tables.
- **COR Scope CRUD:** Only subjects/fees for the chosen program and year can be attached; backend validates every ID.
- **Schedule save:** COR Scope must exist for the configuration; every subject_id and fee_id is re-validated against the schedule’s program_id and academic_year_level_id.
- **Manual API tampering:** Backend ignores any subject_id or fee_id that does not match the scope and returns a validation error.

No cross-program or cross-year usage is allowed.
