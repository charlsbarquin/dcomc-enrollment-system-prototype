# Irregular Student Enrollment Validation

This document explains the **subject completion history** and **validation service** that prevent irregular students from retaking passed/credited subjects and from duplicate same-term enrollment. It fits into the existing enrollment flow (student record → subject history → block assignment → enrollment validation).

---

## 1. What Was Added

### Database: `student_subject_completions`

- **Purpose:** One row per (student, subject, school_year, semester) recording how the subject was completed (passed, failed, dropped, credited, withdrawn).
- **Location:** Migration `2026_03_05_100000_create_student_subject_completions_table.php`.
- **Key columns:** `student_id`, `subject_id`, `school_year`, `semester`, `status`, `grade`, `credited_from`, `remarks`.
- **Unique key:** `(student_id, subject_id, school_year, semester)` — one completion record per attempt per term.
- **Status values:** `passed`, `failed`, `dropped`, `credited`, `withdrawn`. Only **passed** and **credited** count as "completed" (student must not re-enroll).

### Model: `StudentSubjectCompletion`

- **Location:** `app/Models/StudentSubjectCompletion.php`.
- **Relations:** `student()`, `subject()`.
- **Helper:** `StudentSubjectCompletion::completedStatuses()` returns `['passed', 'credited']`.
- **User relation:** `User::subjectCompletions()` for a student’s completion history.

### Service: `IrregularEnrollmentValidationService`

- **Location:** `app/Services/IrregularEnrollmentValidationService.php`.
- **Responsibilities:**
  1. **canEnrollInSubject(studentId, subjectId, schoolYear, semester)**  
     Returns `[allowed, reason]`. Disallows if the student has a **passed** or **credited** completion for that subject, or if they already have a COR record for that (student, subject, term).
  2. **validateDeployForIrregulars(studentIds, subjectIds, schoolYear, semester)**  
     Runs the above check for every (student, subject) in a deploy. Returns `[valid, errors]`; errors list items with `student_id`, `subject_id`, `code` (e.g. `ALREADY_COMPLETED`, `DUPLICATE_THIS_TERM`).
  3. **validateBlockAssignmentForIrregular(studentId, blockId)**  
     Uses the block’s `school_year_label` and `semester`, gets subject IDs from `ClassSchedule` for that block/term, and ensures the student has not already completed any of those subjects. If any are completed, returns a user-friendly message listing those subjects.
  4. **formatDeployErrorsForMessage(errors)**  
     Turns the deploy error list into a single string (student names, subject codes, and reason) for the Create Schedule deploy error flash.

---

## 2. Where It Is Wired

### Create Schedule deploy (Irregular)

- **Controller:** `RegistrarScheduleController::deploy()`.
- **When:** After building the deploy slots and before inserting `student_cor_records` and block assignments.
- **What:** Calls `validateDeployForIrregulars()` with the deploy’s student IDs, subject IDs (from slots), school year, and semester. If invalid, returns `back()->withErrors(['deploy' => formatted message])->withInput()` so the registrar sees which students/subjects are blocked and why (already completed or already enrolled this term).

### Block assignment (Assign irregular to block)

- **Controller:** `BlockManagementController::assignIrregularToBlock()`.
- **When:** After confirming the user is irregular and not already assigned to the block, before creating `StudentBlockAssignment`.
- **What:** Calls `validateBlockAssignmentForIrregular(userId, blockId)`. If the block’s subjects (from `ClassSchedule` for that block’s term) include any the student has already passed/credited, returns `422` JSON `{ success: false, message: "..." }`. The Students Explorer UI shows this message in an `alert()` so the registrar knows why the assignment was rejected.

---

## 3. Flow Summary

1. **Subject completion history** is stored in `student_subject_completions` (populated when grades are finalized or when transfer/credit is recorded — see below).
2. **Before Create Schedule deploy:** The validation service checks every (student, subject) in the deploy. If any pair is already completed or already enrolled that term, deploy is aborted and the registrar sees a clear error message.
3. **Before assigning an irregular to a block:** The validation service checks the block’s subjects for that term. If any are already completed by the student, the assignment is rejected and the UI shows the message.
4. **Same-term duplicate** is enforced by the service using existing `student_cor_records` (any source); no second enrollment for the same (student, subject, school_year, semester) is allowed.

---

## 4. Populating Completion History

The table starts empty. Validation only blocks enrollment when a row exists with `status` in `('passed', 'credited')`. So:

- **Initially:** No completions → no one is blocked by "already completed"; only "already enrolled this term" (duplicate) can block.
- **Going forward:** When you have grade encoding or finalization, add logic to insert/update `student_subject_completions` (one row per student, subject, term) with `status` = `passed`, `failed`, `dropped`, or `credited` as appropriate. Transfer credit can be entered with `status = credited` and optional `credited_from`.
- **Manual/backfill:** You can insert rows directly into `student_subject_completions` (e.g. from past COR or grades) so that existing graduates or retakers are correctly blocked from retaking.

---

## 5. Edge Cases Handled

- **Failed subjects:** No row with `passed`/`credited` → student is allowed to re-enroll (failed does not count as completed).
- **Dropped/withdrawn:** Same; only passed/credited block re-enrollment.
- **Credited (e.g. transfer):** Treated as completed; student cannot enroll again in that subject.
- **Same term, same subject in two blocks:** The service disallows a second enrollment for the same (student, subject, school_year, semester); deploy or assignment will fail with the duplicate reason.
- **Block with no ClassSchedule rows:** `validateBlockAssignmentForIrregular` allows the assignment (no subjects to conflict with).

---

## 6. Fit With the Rest of the System

- Uses the same **school year / semester** as the rest of the app (template and block’s `school_year_label` / `semester`; selected school year from session where needed).
- **Create Schedule** and **Block assignment** are the two entry points for irregular enrollment; both now use the same validation service and the same completion table.
- **student_cor_records** remains the COR snapshot (what was deployed); **student_subject_completions** is the single source of truth for “completed” (passed/credited) and is the only place that drives “cannot retake” and block-assignment checks.
- Errors are shown in the existing UI: deploy errors on the Create Schedule page, block-assignment errors in an alert in Students Explorer when assigning an irregular to a block.

This keeps irregular handling consistent, scalable, and aligned with the existing database and enrollment flow.
