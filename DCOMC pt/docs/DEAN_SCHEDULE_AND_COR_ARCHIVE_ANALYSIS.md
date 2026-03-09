# Dean: Schedule by Program & COR Archive — System Analysis

## 1. Main Functions (Dean)

### Schedule by Program
| What | Route | Controller method | Purpose |
|------|--------|-------------------|---------|
| **Main entry / folder navigation** | `GET /dean/schedule` | `DeanScheduleByScopeController::scheduleByScope()` | Shows Program list → Year levels → Semesters, or the **schedule form** when program + year + semester are in query. Scope comes only from URL (program, year, semester, school_year). |
| **Save schedule** | `POST /dean/schedule/slots` | `DeanScheduleByScopeController::saveScopeScheduleSlots()` | Saves the form data (subjects, day, start, end, room, professor, school year) to `scope_schedule_slots`. **Does not require block.** Draft only; not visible to students; not in COR Archive. |
| **Deploy and Archive** | `POST /dean/schedule/deploy-cor` | `DeanScheduleByScopeController::deployCor()` | Requires **block** + school year. Reads saved data from `scope_schedule_slots`, creates `student_cor_records` for students in that block, **clears** `scope_schedule_slots` for that scope (form reset), redirects to COR Archive. |

### COR Archive
| What | Route | Controller method | Purpose |
|------|--------|-------------------|---------|
| **Archive index** | `GET /dean/cor-archive` | `CorArchiveController::index()` | Lists programs (dean’s department). |
| **Program folder** | `GET /dean/cor-archive/program/{programId}` | `CorArchiveController::program()` | Year levels + school year filter. |
| **Year folder** | `GET /dean/cor-archive/program/{programId}/year/{yearLevel}` | `CorArchiveController::year()` | Semesters. |
| **Blocks (deployed COR)** | `GET /dean/cor-archive/{programId}/{yearLevel}/{semester}/{deployedBlock?}` | `CorArchiveController::show()` | Lists blocks; each block shows deployed schedule from `student_cor_records` (or “No COR deployed”). Same folder path as Schedule by Program. |

---

## 2. Save vs Deploy (Connection)

- **Save schedule**
  - **Does not** require a block.
  - **Stores** in `scope_schedule_slots`: program_id, academic_year_level_id, semester, school_year, subject_id, day_of_week, start_time, end_time, room_id, professor_id.
  - So when the dean later clicks **Deploy and Archive**, the data is already in the DB (from dropdowns and inputs). Deploy **reads** from `scope_schedule_slots` and **does not** use the form POST body for slot data.

- **Deploy and Archive**
  - **Requires** block (and school year). Block is chosen in the deploy section.
  - **Reads** saved slots from `scope_schedule_slots` for the same scope (program, year level, semester, school year).
  - **Writes** one row per student per subject into `student_cor_records` (snapshots: professor, room, days, time, program_id, year_level, block_id, semester, school_year, deployed_by, deployed_at).
  - **Deletes** those rows from `scope_schedule_slots` for that scope → **form is reset** so the dean can build a new schedule for another block.
  - **Redirects** to COR Archive (with block in path) so the dean sees the deployed schedule there. Students in that block see the same schedule in **Student → View COR** (printable).

---

## 3. Key Tables & Relationships

| Table | Role |
|-------|------|
| **scope_schedule_slots** | Draft schedule for a scope (program, year level, semester, school year). No block. Filled by **Save schedule**; read and then cleared by **Deploy and Archive**. |
| **student_cor_records** | Deployed COR: one row per student per subject, with snapshot fields (professor_name_snapshot, room_name_snapshot, days_snapshot, start/end_time_snapshot), plus program_id, year_level, block_id, semester, school_year, deployed_by, deployed_at. **COR Archive** and **Student View COR** read from this. |
| **blocks** | Block list for a program/year/semester (and optional school year). Deploy form dropdown and COR Archive block list come from here. |
| **users** | Students (receive COR); dean (deployed_by). |
| **subjects**, **programs**, **academic_year_levels**, **rooms** | Reference data for schedule form and dropdowns. |

---

## 4. Buttons & Dropdowns (Dean Schedule by Program)

- **School year** — Dropdown; value is in URL and in hidden input for both Save and Deploy. Save and Deploy both use this for scope.
- **Schedule form** — Code, title, units from subject; day, start, end, room, professor from inputs/dropdowns. **Save schedule** submits this form only (no block).
- **Block** — Dropdown in the “Deploy to a block” section. **Required only for Deploy and Archive.** Disabled until at least one slot has been saved (`hasScheduleSlots`).
- **Save schedule** — Submits to `dean.schedule.slots.save`; saves/overwrites slots for current scope.
- **Deploy and Archive** — Submits to `dean.schedule.deploy-cor` with program_id, academic_year_level_id, semester, school_year, **block_id**. Disabled until schedule has slots. On success: redirect to COR Archive; form is reset.

---

## 5. Data Flow Summary

1. Dean opens Schedule by Program → Program → Year → Semester (scope fixed by folder).
2. Dean fills day, time, room, professor (and school year). Clicks **Save schedule** → data stored in `scope_schedule_slots`.
3. Dean selects **Block** in the deploy section. Clicks **Deploy and Archive** → service reads `scope_schedule_slots`, creates `student_cor_records` for students in that block, deletes scope slots, redirects to COR Archive.
4. COR Archive shows the same path (Program / Year / Semester); the deployed block appears with its schedule; students in that block see it in View COR.
5. Schedule by Program form is empty (slots deleted); dean can assign subjects to another block.

All buttons and dropdowns are wired to these flows; Save does not use block; Deploy requires block and uses saved slot data.
