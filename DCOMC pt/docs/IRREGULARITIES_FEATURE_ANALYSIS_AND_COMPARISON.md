# Irregularities Feature: Full Analysis & Comparison with Philippine HEI Systems

This document analyzes the **Irregularities** feature in the DCOMC system (code, flows, functionalities), summarizes how similar Philippine school systems handle irregular students, and compares whether this implementation is aligned, better, or more understandable.

---

## Executive Summary (Answer & Reports)

**Question:** How does the Irregularities feature work, and how does it compare to other Philippine school systems? Is it better or more understandable?

**Answer:**

1. **Analysis of the feature**
  - **Irregular** = students with `student_type` Irregular/Shifter or `status_color` yellow. The system uses **Irregularities** (two tabs: Irregular students list, Create Schedule) and **Create Schedule**: Deploy to students (Program, Year level, student search) at top; Schedule table (Program | Year level, subjects, slots from COR Archive) below; validation before deploy (curriculum, no retake, no duplicate); conflict display; per-student schedule view; Irregular COR Archive; Students Explorer assign/remove irregular to block.
  - **Validation:** `IrregularEnrollmentValidationService` enforces curriculum (Subject Settings), completion history (`student_subject_completions`), and same-term duplicate (`student_cor_records`). Curriculum is the single source from **Subject Settings → Arrange subjects**.
2. **Research on Philippine HEI systems**
  - Philippine HEIs (e.g. FEU, CSPC, UP, UPOU) define **irregular** the same way: does not follow straight year/semester sequence, same max load as full-time. They use **separate enrollment** for irregulars/returners/shifters, **COR** as official enrollment per term, **registrar-built/approved** irregular schedules, **curriculum-based** load/eligibility, and allow **cross-program** with approval when the course is in the degree. Many rely on **manual/process** checks; few publish detailed system logic.
3. **Does it work the same?**
  - **Yes.** DCOMC aligns with Philippine practice on: definition of irregular, COR as enrollment record, registrar-built schedules, curriculum limits, cross-program when subject is in curriculum, no retake of passed subjects, no same-term duplicate.
4. **Is it better or more understandable?**
  - **Yes, in several ways.** DCOMC is **clearer and stronger** where it: (a) enforces curriculum in one place and blocks invalid deploys with clear messages, (b) shows Program | Year level per row and filters slots by year, (c) uses a clear deploy workflow (Program + Year level → build → deploy), (d) shows validation errors with student name, subject code, and reason, (e) highlights conflicts and a Conflict modal, (f) keeps an Irregular COR Archive (who deployed what, when), (g) documents validation and test flow in-repo.
5. **Bottom line**
  - The Irregularities feature **works the same way** as typical Philippine HEI handling of irregulars and is **more understandable and robust** because curriculum, validation, and audit are built into the system with clear UI and messages. Possible future improvements: prerequisite checks, institutional overload rules, and optional “advancing course” approval if the school wants them.

---

## Part 1: DCOMC Irregularities Feature — Code & Functionality

### 1.1 What “Irregular” Means in This System

- **Identification:** Students are treated as irregular if:
  - `student_type` is **Irregular** or **Shifter**, or
  - `status_color` is **yellow** (visual indicator).
- **Profile fields used:** `course` (program name), `year_level`, `semester`, `school_year`, plus block assignments from deployment.

### 1.2 Main Entry Points & Routes


| Purpose                              | Route / Action                                                                      | Controller                                              |
| ------------------------------------ | ----------------------------------------------------------------------------------- | ------------------------------------------------------- |
| Irregularities page (tabs)           | `GET /registrar/irregularities`                                                     | `RegistrarController::irregularities()`                 |
| View one irregular’s schedule        | `GET /registrar/irregularities/{user}/schedule`                                     | `RegistrarController::irregularSchedule()`              |
| Remove subject from student schedule | `DELETE /registrar/irregularities/schedule/record/{record}`                         | `RegistrarController::removeIrregularScheduleSubject()` |
| Create Schedule workspace            | Loaded via tab; edit `GET /registrar/schedule/forms/{id}/edit`                      | `RegistrarScheduleController::edit()`                   |
| Save schedule table                  | `PATCH /registrar/schedule/forms/{id}`                                              | `RegistrarScheduleController::update()`                 |
| Deploy to students                   | `POST /registrar/schedule/forms/{id}/deploy`                                        | `RegistrarScheduleController::deploy()`                 |
| Search irregular students            | `GET /registrar/schedule/forms/students-search?q=&year_level=`                      | `RegistrarScheduleController::studentsSearch()`         |
| Subjects by program                  | `GET /registrar/schedule/forms/subjects-for-program?program_id=`                    | `RegistrarScheduleController::subjectsForProgram()`     |
| Subjects all programs                | `GET /registrar/schedule/forms/subjects-for-all-programs?semester=`                 | `RegistrarScheduleController::subjectsForAllPrograms()` |
| Slot options (COR Archive)           | `GET /registrar/schedule/forms/slots-for-scope?program_id=&subject_id=&year_level=` | `RegistrarScheduleController::slotsForScope()`          |
| Deploy conflicts (per student)       | `POST /registrar/schedule/forms/{id}/conflicts`                                     | `RegistrarScheduleController::conflicts()`              |
| Irregular COR Archive list/detail    | `GET /registrar/irregular-cor-archive`                                              | `IrregularCorArchiveController`                         |
| Assign/remove irregular to block     | Students Explorer: `POST/DELETE .../assign-irregular`                               | `BlockManagementController`                             |


### 1.3 Feature Breakdown

#### A. Irregularities Page (Two Tabs)

1. **Irregular students tab**
  - Lists students who are irregular/shifter or have yellow status.
  - Shows: School ID, Name, Email, Student Type, Current Program, Block assignments, Action (View schedule, View in Explorer).
  - Search by name, email, or school ID.
  - **View schedule** opens that student’s deployed Create Schedule (COR records with `cor_source = create_schedule`).
2. **Create Schedule tab**
  - **Deploy to students (top):**
    - **Program** dropdown: sets curriculum for the schedule table (subjects limited to that program when “Limit subjects by program” is on).
    - **Year level** dropdown: filters which irregular students appear in the search (e.g. only 3rd year irregulars).
    - **Search student:** by name/email/school ID (optional year filter).
    - Add students to the deploy table; **Deploy** creates/overwrites COR records and block assignments for those students.
  - **Schedule table (below):**
    - Columns: **Program | Year level**, Code, Title, Units, **Schedule slot** (Day, Time, Room, Professor, Block), Action.
    - Program and Year level per row drive which COR Archive slots are shown (filtered by program + year level).
    - Add row: optional “Limit subjects by program”; subject search (one program or all programs); slot options from COR Archive (optionally filtered by year level).
    - Save stores template (slots with `program_id`, `year_level`, `subject_id`, `slot_data`).
  - **Validation before deploy:**
    - **Curriculum:** Each (student, subject) must be in the student’s curriculum (program + year level + semester from Subject Settings). Uses `raw_subject_id` so cross-program offerings (e.g. BEED taking a subject offered in BCAED) are allowed if the subject is in BEED’s curriculum.
    - **Completion:** No retaking passed/credited subjects (`student_subject_completions`).
    - **Duplicate:** No same-term duplicate enrollment (existing `student_cor_records`).
  - **Conflict detection:** Per-student list of subjects that are already completed or already enrolled this term (red highlight, “Conflict” button/modal).

#### B. Irregular Schedule (Per-Student View)

- Shows all COR records for that student with `cor_source = create_schedule` (and optional school year filter).
- Table: Subject, Day·Time, Room·Professor, Block, Action (Remove).
- **Remove subject:** Deletes that COR record; if it was the only record for that block, removes the student from that block in Students Explorer.

#### C. Irregular COR Archive

- **Index:** Batches of deployments (grouped by deploy date + deployer), with record/student counts, filtered by selected school year.
- **Show:** One batch = all COR records (student, subject, slot) for that deploy.

#### D. Students Explorer — Irregulars

- **Assign irregular to block:** Allowed only if the block does not contain subjects the student has already passed/credited (same `IrregularEnrollmentValidationService`).
- **Remove from block:** Unassigns the student from that block.

#### E. Student View (View COR)

- Students see their COR; Create Schedule deployments appear (e.g. with yellow indicator) and are sourced from the same `student_cor_records` with `cor_source = create_schedule`.

### 1.4 Key Services & Data

- **IrregularEnrollmentValidationService**
  - `canEnrollInSubject()` — already completed (passed/credited) or same-term duplicate.
  - `validateDeployForIrregulars()` — bulk check for deploy.
  - `validateDeployCurriculum()` — subject must be in student’s program + year level + semester (Subject Settings).
  - `validateBlockAssignmentForIrregular()` — block must not contain subjects already completed.
  - `formatDeployErrorsForMessage()` — user-facing error text.
- **Data:** `schedule_templates` (template with slots array), `student_cor_records` (cor_source, deployed_by, deployed_at), `student_subject_completions`, `student_block_assignments`, blocks, programs, subjects (with `raw_subject_id`, program, year level, semester).

### 1.5 Curriculum Source

- **Subject Settings → Arrange subjects:** Program → Year → Semester → table of subjects.
- Subjects are stored with `program_id`, `academic_year_level_id`, `semester`. This is the single source for “in curriculum” checks and for filtering slot/subject options by program and year level.

---

## Part 2: How Philippine HEI Systems Typically Handle Irregulars

### 2.1 Definitions (Aligned with CHED/Common Practice)

- **Irregular student:** One whose program of study does not follow the straight year/semester sequence of the curriculum but whose maximum load is the same as a full-time regular student. The program may or may not be finished in the prescribed semesters/years.  
*(Source: FEU Registrar policies; similar at CSPC, UP, etc.)*
- **Shifter:** Student who changed program; often grouped with irregulars and returners for enrollment schedules.
- **Separate enrollment schedules** for irregulars, returners, shifters (and sometimes second coursers) are common (e.g. CSPC).

### 2.2 Certificate of Registration (COR)

- COR is the official document showing enrolled subjects after payment/registration.
- Issued per term; students may get it from the registrar or from an online portal after payment is posted.
- Philippine systems treat COR as the official enrollment record for the term — same idea as DCOMC’s `student_cor_records` (with snapshot of day, time, room, professor, etc.).

### 2.3 How Schedules Are Built for Irregulars

- **Common pattern:** Registrar/office builds or approves schedules for irregulars because they do not follow the block curriculum.
- **Curriculum rules:**  
  - Maximum load often tied to “prescribed units of the curriculum for the year and semester level” (FEU).  
  - Prerequisites must be completed before requisite courses.  
  - “Advancing” courses (taking ahead of sequence) may require Dean/Registrar approval (FEU).
- **Cross-enrollment / cross-program:**  
  - Some schools allow taking courses from another program or unit if needed for the degree and with approval (e.g. UPOU, UP).  
  - Often limited (e.g. max 50% of units) and with permit/approval.  
  - DCOMC’s “subject in student’s curriculum but slot from another program (e.g. BCAED)” is in line with this: same course, different section/program offering.

### 2.4 What Many Systems Do *Not* Expose in Public Docs

- Detailed system design (e.g. “registrar builds a template then deploys to a list of students”) is rarely described in public policies.
- Automated checks (no retake of passed subjects, no same-term duplicate, curriculum-only enrollment) are often enforced by process and manual checks rather than documented as system rules.
- Dedicated “Irregular COR Archive” as a separate audit trail of registrar deployments is a design choice; many schools just have “COR” and possibly batch reports.

---

## Part 3: Comparison — Does It Work the Same? Is It Better or More Understandable?

### 3.1 Alignment with Philippine Practice


| Aspect                      | Philippine norm (from research)                               | DCOMC implementation                                                                                       | Match? |
| --------------------------- | ------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- | ------ |
| Definition of irregular     | Does not follow straight sequence; same max load as full-time | student_type Irregular/Shifter or status_color yellow                                                      | Yes    |
| Separate handling           | Separate enrollment schedules / processes for irregulars      | Dedicated Irregularities page and Create Schedule flow                                                     | Yes    |
| COR as enrollment record    | COR = official enrollment per term                            | student_cor_records with snapshot (day, time, room, prof, block)                                           | Yes    |
| Registrar builds schedule   | Registrar/office builds or approves irregular schedules       | Registrar builds schedule table and deploys to selected students                                           | Yes    |
| Curriculum bounds           | Load/eligibility tied to curriculum; prerequisites            | Only subjects in student’s program+year+semester (Subject Settings); completion/duplicate checks           | Yes    |
| Cross-program / cross-block | Allowed with approval when in curriculum / needed             | Allowed when subject is in student’s curriculum (raw_subject_id); slot can be from another program’s block | Yes    |
| No retaking passed subjects | Standard academic rule                                        | Enforced via student_subject_completions (passed/credited)                                                 | Yes    |
| No duplicate same term      | Standard                                                      | Enforced via student_cor_records                                                                           | Yes    |


So functionally, DCOMC’s Irregularities feature **works in line with** how Philippine HEIs define and handle irregulars: definitions, COR, registrar-built schedules, curriculum limits, cross-program where appropriate, and no retake/duplicate.

### 3.2 Where DCOMC Is Clearer or Stronger

1. **Explicit curriculum enforcement**
  - Curriculum is defined in one place (Subject Settings → Arrange subjects). Deploy and slot/subject options are tied to program + year level + semester. Many schools enforce this by policy and manual checks; DCOMC encodes it in the system and blocks invalid deploys with clear messages.
2. **Program + year level on the schedule**
  - “Program | Year level” per row and filtering slots by year level make it explicit which curriculum block each subject belongs to and which sections (blocks) are shown. That makes it easier to assign irregulars to the right sections (e.g. 1st year BCAED) and avoid mixing years.
3. **Deploy-to-students workflow**
  - Program and year level filters at the top (curriculum + who to search), then build schedule, then deploy to a selected list. This is a clear, step-by-step workflow that matches how many registrars actually work (set context → build → assign to students).
4. **Validation and messages**
  - Before deploy: curriculum, completion, and duplicate checks with **formatted error messages** (student name, subject code, reason). Before assigning to block: check that the block does not contain already-completed subjects, with a clear message. That makes the system **more understandable** for the registrar and reduces reliance on memory or external checklists.
5. **Conflict visibility**
  - Real-time (or on-demand) conflict highlighting and a “Conflict” modal listing problematic subjects per student. This improves clarity compared to discovering issues only after submission or from a separate report.
6. **Irregular COR Archive**
  - Separate list of deployment batches (by date and deployer) and the ability to open a batch to see all records. This gives an audit trail and a clear place to see “what was deployed when and by whom,” which many schools do only on paper or in ad-hoc reports.
7. **Documentation**
  - In-repo docs (e.g. IRREGULAR_ENROLLMENT_VALIDATION.md, IRREGULAR_CREATE_SCHEDULE_TEST_FLOW.md) describe validation and test steps. That makes the feature easier to onboard and to compare with policy.

### 3.3 Possible Gaps or Differences (Not necessarily worse)

- **Prerequisites:** DCOMC does not appear to enforce subject prerequisites in the Create Schedule deploy; many Philippine schools do (e.g. FEU). Adding prerequisite checks would bring it closer to strict FEU-style rules.
- **Overload rules:** FEU (and others) have explicit overload rules (e.g. max units, graduating status). DCOMC has an overload flag on slots but not a single place that enforces institutional overload policy; that could be added if needed.
- **Advancing courses:** FEU requires approval to “advance” courses. DCOMC allows any subject in the curriculum for that program+year+semester; if the school wants to restrict “advancing,” that would need an extra rule or approval step.

---

## Part 4: Summary

- **Functionality:** The Irregularities feature matches Philippine practice: irregular/shifter definition, COR as enrollment record, registrar-built schedules, curriculum-based limits, cross-program sections when the subject is in the student’s curriculum, and rules against retaking passed subjects and duplicate enrollment in the same term.
- **Code structure:** Clear separation of concerns (validation service, controllers, COR archive, block assignment), use of template + deploy, and curriculum sourced from Subject Settings make the behavior traceable and maintainable.
- **Usability and clarity:** Program and year level in the UI and in validation, deploy-time checks with clear error messages, conflict display, and the Irregular COR Archive make the process **more understandable** and auditable than many manual or partially automated setups.
- **Compared to other systems:** Public information from Philippine HEIs rarely describes system logic in detail. Where policies are stated (e.g. FEU), DCOMC aligns with them; in several areas (curriculum enforcement, deploy workflow, validation messages, conflict visibility, COR archive) DCOMC is **more explicit and systematic**, which can be considered **better** for consistency and training.

Overall, the Irregularities feature is **aligned with** how Philippine similar school systems define and handle irregular students and is **more understandable and robust** where it encodes curriculum, validation, and audit trail in the system with clear UI and messages.

---

## Answer & Reports (Consolidated)

### Report 1: Feature coverage


| Area                | What the system does                                                                                                                                                                                   |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Who is irregular    | `student_type` Irregular/Shifter or `status_color` yellow; profile: course, year_level, semester, block assignments                                                                                    |
| Irregularities page | Tab 1: list irregulars, View schedule, View in Explorer. Tab 2: Create Schedule (Deploy on top, Schedule table below)                                                                                  |
| Create Schedule     | Program + Year level (top); student search (optional year filter); schedule table with Program | Year level per row; slots from COR Archive (filtered by program + year); Save; Deploy with validation |
| Validation          | Curriculum (Subject Settings), no retake (student_subject_completions), no duplicate (student_cor_records); clear error messages; conflict list per student                                            |
| Other               | Per-student schedule view (remove subject); Irregular COR Archive (batches); Students Explorer assign/remove irregular to block (with completion check)                                                |


### Report 2: Philippine comparison


| Question                                           | Answer                                                                                                                                                                |
| -------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Does it work the same as other Philippine systems? | Yes. Same definition of irregular, COR as enrollment record, registrar-built schedules, curriculum limits, cross-program when in curriculum, no retake, no duplicate. |
| Is it better?                                      | Yes where it encodes curriculum, validation, and audit in the system and shows clear messages and conflicts.                                                          |
| Is it more understandable?                         | Yes: Program | Year level, deploy workflow, validation messages, conflict modal, and COR archive make the process explicit and auditable.                             |
| Gaps to consider                                   | Prerequisites not enforced in deploy; overload policy not centralized; no explicit “advancing course” approval step.                                                  |


### Report 3: References

- **In-repo docs:** `IRREGULAR_ENROLLMENT_VALIDATION.md`, `IRREGULAR_CREATE_SCHEDULE_TEST_FLOW.md`
- **External (research):** FEU Registrar – Policies on Enrolment; CSPC – enrolment schedule for irregulars/returners/shifters; UP/UPOU – cross-enrollment; general CHED-aligned definitions of irregular students.

