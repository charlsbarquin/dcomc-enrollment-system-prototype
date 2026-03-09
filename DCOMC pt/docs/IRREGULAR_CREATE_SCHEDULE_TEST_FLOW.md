# Step-by-Step Process Flow: Testing Irregular Student Schedule (Create Schedule)

This document walks through the **Create Schedule** flow for irregular students and explains **what the added validation feature does** at each step. Use it to test the feature end-to-end.

---

## Overview

**Path:** Registrar → **Irregularities** → **Create Schedule** tab.

**Added feature:** Before deploying a schedule to irregular students, the system now checks (1) **subject completion history** so students cannot retake subjects they already **passed** or **credited**, and (2) **same-term duplicate** so a student is not enrolled in the same subject twice in the same term. If any check fails, deploy is blocked and the registrar sees a clear error message.

---

## Step-by-Step Process Flow

### Step 1: Log in as Registrar

- Log in with a user that has the **registrar** role.
- Ensure the **school year** selector (e.g. in the sidebar) is set to the term you will use for testing (e.g. 2025–2026).

**What the feature does here:** Nothing yet. The selected school year will later be used by the validation service to match the deploy term and to check for same-term duplicates.

---

### Step 2: Open Irregularities

- In the sidebar, go to **Student Records** → **Irregularities** (or the equivalent menu).
- The Irregularities page opens with two tabs: **Irregular students** and **Create Schedule**.

**What the feature does here:** Nothing yet. You are only navigating to the area where irregular schedules are built and deployed.

---

### Step 3: Open the Create Schedule tab

- Click the **Create Schedule** tab.
- You see the Create Schedule workspace: a schedule table (subjects, time, day, room, professor, block/slot) and a section to add students and deploy to them.

**What the feature does here:** Nothing yet. The validation runs only when you click **Deploy** (Step 7).

---

### Step 4: Build the schedule (add subjects and slots)

- Add subjects to the schedule table (from the COR Archive / slot options).
- For each subject, set **day**, **start time**, **end time**, **room**, **professor**, and the **block/slot** (from COR Archive).
- Save or ensure the template has at least one valid slot with all required data.

**What the feature does here:** Nothing yet. The list of **subject IDs** in this template will later be passed to the validation service together with the list of students you select for deploy.

---

### Step 5: Add irregular students to the deploy list

- In the “Add students” or “Students for deploy” section, search for and add one or more **irregular** students (e.g. by name or school ID).
- Confirm the students appear in the table/list that will be included in the deploy.

**What the feature does here:** Nothing yet. The **student IDs** in this list will later be validated against the template’s subjects and the current term (school year + semester from the template).

---

### Step 6: (Optional) Prepare subject completion data for testing

To test the **“already completed”** block:

- Insert a row in **student_subject_completions** for one of the students and one of the subjects in your template, with `status = 'passed'` (or `'credited'`).
  - Example (adjust IDs and term to match your data):
    - `student_id` = ID of the irregular student you added  
    - `subject_id` = ID of a subject in the schedule table  
    - `school_year` = same as template (e.g. `2025-2026`)  
    - `semester` = same as template (e.g. `1st Semester`)  
    - `status` = `passed`
- Then try to deploy (Step 7). You should see a validation error mentioning that student and subject (already completed).

**What the feature does here:** This row is what the validation service reads. The service only blocks deploy when such a **passed** or **credited** record exists for a (student, subject) pair.

---

### Step 7: Click Deploy (Deploy to selected students)

- Click the **Deploy** (or “Deploy to selected students”) button.
- The server runs the **Create Schedule deploy** logic.

**What the feature does here (this is where validation runs):**

1. The system collects:
   - **Student IDs** from the deploy list.
   - **Subject IDs** from the schedule table slots.
   - **School year** and **semester** from the template.
2. It calls **IrregularEnrollmentValidationService::validateDeployForIrregulars()** with these four inputs.
3. For **each (student, subject)** pair, the service:
   - **Completion check:** Looks in **student_subject_completions** for that student and subject with `status` in `('passed', 'credited')`. If found → adds an error with code **ALREADY_COMPLETED**.
   - **Duplicate check:** Looks in **student_cor_records** for that student, subject, school year, and semester. If found → adds an error with code **DUPLICATE_THIS_TERM**.
4. **If there are any errors:**
   - Deploy **does not** run (no COR records or block assignments are created).
   - The controller returns with an error message.
   - The message is built by **formatDeployErrorsForMessage()** (student names, subject codes, and reason: “already completed” or “already enrolled this term”).
   - You see this message on the Create Schedule page (e.g. in the error flash area).
5. **If there are no errors:**
   - Deploy proceeds as before: existing create_schedule COR records for those students are replaced, new COR records are inserted, block assignments are updated, and you see a success message.

---

### Step 8: Observe the result

**Case A – Validation passed (no errors)**

- You see a success message (e.g. “Schedule deployed to N student(s). They will see it in View COR.”).
- In **Students Explorer**, those students show the new subjects in their block(s); in **View COR** (student or staff/registrar), the COR shows the deployed subjects.

**Case B – Validation failed (already completed)**

- You see an error message such as:  
  “Cannot deploy: [Student Name] – [Subject Code] (already completed); …”
- No COR records or block assignments are created for this deploy.
- Fix: Remove that student from the deploy list, or remove that subject from the schedule, or (if it was test data) remove the completion row and try again.

**Case C – Validation failed (duplicate this term)**

- You see an error message such as:  
  “Cannot deploy: [Student Name] – [Subject Code] (already enrolled this term); …”
- This means that student already has a COR record for that subject in the same school year and semester (e.g. from a previous deploy or from Schedule by Program).
- Fix: Do not deploy that subject again for that student in the same term, or adjust the deploy list/schedule.

---

### Step 9: (Optional) Test block assignment validation

The **same completion logic** is used when assigning an irregular student to a block from **Students Explorer**:

1. Go to **Students Explorer**.
2. Open an irregular student (e.g. **View / Edit**).
3. In the block assignments section, choose a block and click **Add** (or “Assign to block”).
4. **What the feature does:**
   - The server calls **validateBlockAssignmentForIrregular(studentId, blockId)**.
   - It loads the block’s **school_year_label** and **semester**, then loads **subject IDs** from **ClassSchedule** for that block and term.
   - For each subject, it checks whether the student has a **passed** or **credited** completion. If any are completed, the assignment is **rejected** with a 422 response and a message like: “This block includes subject(s) the student has already completed: [Subject names]. Assign to a different block or …”
   - The frontend shows this message in an **alert**.
5. If the block has no subjects the student has completed (or has no ClassSchedule rows for that term), the assignment succeeds as before.

---

## Summary Table

| Step | Registrar action           | What the added feature does |
|------|----------------------------|-----------------------------|
| 1    | Log in as registrar        | —                           |
| 2    | Open Irregularities        | —                           |
| 3    | Open Create Schedule tab   | —                           |
| 4    | Build schedule (subjects/slots) | —                     |
| 5    | Add irregular students    | —                           |
| 6    | (Optional) Add completion row for testing | Feeds completion check in Step 7 |
| 7    | Click Deploy               | **Runs validateDeployForIrregulars**: blocks deploy if any (student, subject) is already completed or already enrolled this term; shows which student/subject and why. |
| 8    | See success or error       | Success = no validation errors; Error = message from formatDeployErrorsForMessage. |
| 9    | (Optional) Assign irregular to block in Students Explorer | **Runs validateBlockAssignmentForIrregular**: blocks assignment if block’s subjects include any the student already completed; shows message in alert. |

---

## Quick Test Checklist

- [ ] Deploy with **no** completion records → deploy succeeds (only duplicate check can block).
- [ ] Add a **passed** completion for (Student A, Subject X), then deploy with Student A and Subject X in the template → deploy fails with “already completed” for that student/subject.
- [ ] Deploy the same students/subjects **twice** in the same term → second deploy can fail with “already enrolled this term” for those pairs.
- [ ] In Students Explorer, assign an irregular to a block that has a subject they have **passed** → assignment fails with message listing those subjects.
- [ ] Assign an irregular to a block with no completed subjects (or empty schedule) → assignment succeeds.

This flow covers the full path **Registrar → Irregularities → Create Schedule** and where the new validation runs and what it does at each step.
