# Shifter Process Guidance — DCOMC Enrollment System

This document explains how the system works for **shifters** (students who change program) and what you should do step-by-step, based on the system design and the provided DCOMC notes and scope.

---

## 1. How the System Treats Schedules

| Path | Purpose | Used for |
|------|---------|----------|
| **Schedule by Program** | One schedule per scope (Program → Year → Semester). Registrar edits time, day, room, professor per subject. | **Regular** students in standard blocks. Same curriculum for everyone in that scope. |
| **Schedule Forms (templates)** | Custom subject list + fees, tied to a **block**. When deployed, COR uses this template + that block’s **ClassSchedule** (time, room, professor). | **Shifters** and others with a **different** set of subjects/schedule from the standard path. |

- **COR (Certificate of Registration)** is built from:
  1. A **Schedule Template** that matches the student’s program, year, semester, school year, major, and **block**.
  2. **ClassSchedule** rows for that **block** (day, time, room, professor).

So: **schedule is block-based**. Everyone in the same block shares the same ClassSchedule. For shifters to have a different schedule, they must be in a **different block** that has its own template and its own ClassSchedule.

---

## 2. Student Type and Color Coding (from notes)

- **Student type** is stored on the user: `student_type` (e.g. `Freshman`, `Regular`, `Shifter`, `Transferee`, `Returnee`, `Irregular`).
- **Color coding** (e.g. in lists):
  - **Yellow** → Irregular (underload/overload)
  - **Blue** → Returnee
  - **Green** → Transferee  

Shifters are often treated as **Irregular** (yellow) or have their own type **Shifter**. The system supports `student_type = 'Shifter'`. You can set **status_color** when you mark someone as a shifter so they appear with the right indicator.

---

## 3. What You Should Do When Someone Shifts Programs

### 3.1 High-level flow

1. **Identify** the student as a shifter and confirm their **new program**, **year level**, and **semester**.
2. **Update** the student’s record: new program (course), year level, semester, and `student_type = Shifter` (and status_color if you use it).
3. **Assign** them to a block that has the **shifter schedule** (see below).
4. **Ensure** that block has a **deployed Schedule Form** and **ClassSchedule** rows so COR shows the right subjects, time, room, and professor.

Details follow.

---

### 3.2 Step 1: Update the student’s academic data

Before or during enrollment approval, the student’s **program (course)** must reflect the **new** program. The system uses `user.course` when assigning a block; if it still has the old program, they will be placed in a block of the old program.

**Where to do it**

- **Student Status** (Admin or Registrar): open the student → **Edit record** → set:
  - **Course** = new program (e.g. Bachelor of Elementary Education)
  - **Year level** = appropriate year in the new program (e.g. 2nd Year)
  - **Semester** = current semester
  - **Student type** = `Shifter`
  - **Status color** = e.g. `yellow` if you treat shifters like irregulars

If the enrollment form has a “New program” or “Course” question for shifters, you can use that to know what to set; the system does **not** yet auto-update `course` from form answers on approve, so this edit is currently **required** for shifters.

---

### 3.3 Step 2: Shifter block and schedule

Shifters usually have a **different** set of subjects (and often different times) than the standard block. To support that:

**Option A — Dedicated shifter block (recommended)**

1. **Create a block** for shifters in that program/year/semester, e.g.:
   - Name/Code: `BEED 2 - Shifter` or `BSED-ENG 2 - Shifter`
   - Same **program**, **year_level**, **semester**, **shift** (day/night), and **gender_group** rules as other blocks.
2. **Create a Schedule Form** (Schedule → Schedule Forms (Shifters)):
   - **Program** = new program
   - **Year level** = same as block
   - **Semester** = same as block
   - **Block** = this shifter block
   - **Subjects** = the list of subjects this batch of shifters will take (may be a custom list: credited + new subjects).
   - **Fees** = set as needed.
3. **Deploy** that schedule form so it is used for COR.
4. **Add ClassSchedule** rows for that block (day, time, room, professor for each subject). This can be done by the Dean/Registrar (whoever manages class schedules for that block). Without these, COR will show “TBA” for schedule.
5. **Assign the shifter** to this block (see Step 3).

**Option B — Reuse an existing “irregular” block**

If you already have a block for irregulars/shifters for that program/year/semester:

- Ensure that block has a **deployed Schedule Form** with the correct subject list and **ClassSchedule** rows.
- Assign the shifter to that block and keep their **course**, **year_level**, **semester**, and **student_type** correct.

---

### 3.4 Step 3: Assigning the shifter to the block

**If they enrolled via the enrollment form**

- Before you click **Enroll** (approve):
  - Set their **course** (and year_level/semester if needed) and **student_type** as in Step 1.
- When you click **Enroll**, the system will:
  - Set year_level/semester from the form’s **incoming_year_level** / **incoming_semester**.
  - Call **BlockAssignmentService**, which uses **student’s course** to pick a block. So the block will be in the **new** program only if you already updated **course**.
- **Important:** The service fills **existing non-full blocks** first (by program, year, semester, shift). So:
  - If you want shifters in a **dedicated shifter block**, either:
    - Create that block and assign the shifter to it **manually** (Edit record → Block = shifter block), and **do not** use “Enroll” in a way that would overwrite block; or
    - Ensure the shifter block is the one that gets chosen (e.g. only shifter block exists for that program/year/semester, or you add logic so shifters are assigned to a designated block).

So in practice, for shifters you will often:

1. Update student: **course**, **year_level**, **semester**, **student_type** (and optionally block).
2. If you have a dedicated shifter block: in **Edit record**, set **Block** to that shifter block (and leave “Enroll” for only updating status/assessment), **or** implement a separate “Enroll shifter” flow that assigns the shifter block.

**If they are processed manually (no form)**

- Use **Student Status** → **Edit record** and set:
  - **Course**, **Year level**, **Semester**, **Student type**
  - **Block** = the shifter (or irregular) block that has the correct deployed Schedule Form and ClassSchedule.

---

### 3.5 Step 4: COR and schedule forms

- When the student opens **COR**, the system finds a **Schedule Template** that matches:
  - Program, year level, semester, school year, major, and **block_id**.
- It then uses **ClassSchedule** for that **block** for time/room/professor.
- So: **deploy the Schedule Form** for the shifter block and **add ClassSchedule** for that block; then COR will show the right subjects and schedule.

---

## 4. Summary Checklist When Someone Shifts

- [ ] Student’s **course** = new program (required for correct block assignment).
- [ ] **Year level** and **semester** = correct for the new program.
- [ ] **Student type** = `Shifter` (and status_color if used).
- [ ] A **block** exists for shifters (or irregulars) for that program/year/semester with:
  - [ ] A **Schedule Form** with the right subject list (and fees), linked to that block.
  - [ ] Form **deployed**.
  - [ ] **ClassSchedule** rows for that block (day, time, room, professor).
- [ ] Student is **assigned** to that block (via Edit record or a dedicated enroll flow).

---

## 5. Alignment with DCOMC Notes and Scope

- **Registrar can edit block** and change student’s block (with replacement and valid reason for **block change**; shift is a **program change**, so we update course and assign the right block).
- **Schedule Forms** are used for **shifters** because they have different schedules; **Schedule by Program** is for the standard path.
- **Blocking**: Max 50 per block; you can use a dedicated shifter block so regular blocks are not mixed with different curricula.
- **COR** is generated from the deployed template + block’s ClassSchedule; shifters get COR from their shifter block’s template and schedule.
- **Day/night shift** and **gender** rules still apply to blocks; create shifter blocks with the correct shift and gender_group.

---

## 6. Possible System Enhancements

1. **Enrollment approval:** When approving, if the form answers contain “course” or “new program” (e.g. for shifters), update `user.course` (and optionally year_level/semester) from those answers **before** calling block assignment, so the correct program block is chosen without a separate manual edit.
2. **Shifter-specific assignment:** A dedicated “Enroll as shifter” action that:
   - Sets course/year/semester from form or dialog.
   - Sets student_type = Shifter.
   - Assigns the student to a **designated shifter block** (e.g. from settings or from the same program/year/semester) instead of the default fill-old-blocks-first logic.
3. **List of shifters:** Filter in Student Status (and reports) by `student_type = Shifter` for easier tracking.

Using the steps above, you can handle shifters consistently with the current design; the enhancements would reduce manual steps and the risk of wrong block assignment.
