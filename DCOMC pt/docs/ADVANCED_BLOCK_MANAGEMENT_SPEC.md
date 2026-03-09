# Advanced Student Block Management System — Technical Specification

**Version:** 1.0  
**Status:** Implementation Reference  
**Audience:** Enterprise system architect, backend/frontend developers

---

## 1. Database Schema

### 1.1 Programs (existing, aligned)

```sql
programs
  id                BIGINT UNSIGNED PK
  program_name      VARCHAR(255) UNIQUE NOT NULL
  created_at, updated_at
```

### 1.2 Blocks (extended)

```sql
blocks
  id                BIGINT UNSIGNED PK
  program_id        BIGINT UNSIGNED NULL FK(programs.id)  -- preferred; program string kept for compat
  program           VARCHAR(255) NULL                      -- denormalized for display/legacy
  year_level        VARCHAR(50) NOT NULL                   -- e.g. '1st Year'
  section_name      VARCHAR(50) NOT NULL                   -- e.g. 'BSIT-1A', '1A'
  semester          VARCHAR(50) NOT NULL                   -- e.g. 'First Semester'
  shift             ENUM('day','night') DEFAULT 'day'
  max_capacity      UNSIGNED INT DEFAULT 50
  current_size      UNSIGNED INT DEFAULT 0
  code              VARCHAR(100) UNIQUE                    -- display code e.g. BSIT-1A
  is_active         BOOLEAN DEFAULT true
  created_at, updated_at

UNIQUE(program_id, year_level, section_name, semester)  -- or (program, year_level, section_name, semester)
```

- **section_name**: Logical section identifier (e.g. 1A, 1B, 2A). With program + year_level + semester it uniquely identifies a block.
- **max_capacity**: Hard cap; default 50. **current_size** must never exceed it.

### 1.3 Students (users table — existing, aligned)

```sql
users (students when role = 'student')
  id, name, email, password, role, ...
  program_id        BIGINT UNSIGNED NULL FK(programs.id)  -- optional; course string used today
  course            VARCHAR(255) NULL                     -- program name (denormalized)
  block_id          BIGINT UNSIGNED NULL FK(blocks.id)
  year_level        VARCHAR(50) NULL
  semester          VARCHAR(50) NULL
  student_status    VARCHAR(50) NULL                       -- active, dropped, shifted, graduated
  ...
```

- Student must belong to **exactly one** block when active (enforced in application + optional DB check).

### 1.4 Block Transfer Audit Log (new)

```sql
block_transfer_logs
  id                BIGINT UNSIGNED PK
  student_id        BIGINT UNSIGNED NOT NULL FK(users.id)
  from_block_id     BIGINT UNSIGNED NULL FK(blocks.id)
  to_block_id       BIGINT UNSIGNED NOT NULL FK(blocks.id)
  transfer_type     ENUM('manual','auto_rebalance','promotion','shift_out','admin_correction')
  initiated_by      BIGINT UNSIGNED NULL FK(users.id)     -- registrar/admin for manual
  reason            TEXT NULL
  metadata          JSON NULL                             -- e.g. batch ids, promotion context
  created_at

INDEX(student_id), INDEX(from_block_id), INDEX(to_block_id), INDEX(transfer_type), INDEX(created_at)
```

---

## 2. Auto-Rebalancing Algorithm

**Trigger:** After any event that reduces a block’s enrollment (drop, shift, transfer out).  
**Condition:** `block.current_size < block.max_capacity`.

**Steps:**

1. **Identify blocks with vacancy**  
   - Same `program_id` (or program name), `year_level`, `semester`.  
   - `current_size < max_capacity`.  
   - Optionally exclude blocks marked “no auto-fill” if such a flag exists.

2. **For each such block (process in deterministic order, e.g. by section_name):**
   - `vacancy = max_capacity - current_size`.
   - **Source blocks:** Same program_id, year_level, semester; section_name **alphabetically greater** than current block (e.g. for 1A, pull from 1B, 1C, …).
   - Order source blocks by section_name ASC (so 1B before 1C).

3. **Select students to move:**
   - From first source block that has students: take up to `vacancy` students.
   - Order students by last_name, first_name (or student_id) for determinism.
   - If still vacancy, repeat from next source block until vacancy = 0 or no more source students.

4. **Execute moves (in transaction):**
   - For each moved student:
     - Update `user.block_id` = target block, keep year_level/semester/program.
     - Decrement source `block.current_size`.
     - Increment target `block.current_size`.
     - Insert `block_transfer_logs` with `transfer_type = 'auto_rebalance'`, `initiated_by = NULL`, reason/metadata as needed.

5. **Edge case:** No eligible students to refill → leave block under capacity; no error.

**Idempotency:** Run rebalance per block once per “event” (e.g. after a single drop or after a batch); avoid recursive rebalance in same run if not required.

---

## 3. Promotion Logic Algorithm

**Trigger:** End of academic year (scheduled job or registrar “Run promotion” action).

**Rules:**

- **year_level:** Advance by one step using configured order (e.g. 1st Year → 2nd Year → 3rd Year → 4th Year).
- **semester:** Reset to “First Semester” (or first semester in config).
- **Block continuity:** Same section letter/number where possible.  
  - Example: BSIT-1A → BSIT-2A, BSIT-2A → BSIT-3A.  
  - Implementation: For each (program_id, year_level_old, section_name, semester_old), resolve (program_id, year_level_new, section_name, semester_new). If target block does not exist, create it (with same section_name, new year_level, semester = First Semester).

**Steps:**

1. Resolve **new year_level** and **new semester** (e.g. map 1st→2nd, 2nd→3rd, 3rd→4th; semester = First Semester).
2. For each active student in a block:
   - Skip if already at max year (e.g. 4th Year) or non-promotable status.
   - Compute target block: same program_id, `year_level_new`, same `section_name`, `semester_new`.
   - If target block missing: create block (program_id, year_level_new, section_name, semester_new, max_capacity=50, current_size=0).
   - Update student: `block_id` = target, `year_level` = year_level_new, `semester` = semester_new.
   - Update old block `current_size` -= 1; new block `current_size` += 1.
   - Insert `block_transfer_logs` with `transfer_type = 'promotion'`, metadata = { from_year, to_year, from_semester, to_semester }.
3. Run in a single transaction or per-student transactions with careful deadlock avoidance (e.g. lock blocks in consistent order).

---

## 4. Backend Validation Rules

All validations must be enforced in backend (controllers/services); never rely only on UI.

| Rule | Check | HTTP/Response |
|------|--------|----------------|
| Student in exactly one block when active | On save: if role=student and status=active, block_id must be non-null and exist. | 422 |
| Block capacity | On transfer: `target_block.current_size + count(students_to_move) <= target_block.max_capacity`. | 422 |
| No cross-program transfer | `source_block.program_id === target_block.program_id` (or same program name). | 422 |
| No cross-year transfer | `source_block.year_level === target_block.year_level` and same semester. | 422 |
| Registrar-only manual transfer | Only role registrar (or admin) can call manual transfer API. | 403 |
| Block exists and active | from_block_id and to_block_id must exist and is_active = true. | 404/422 |
| Student belongs to source block | For each student, `user.block_id === from_block_id`. | 422 |
| No duplicate students in request | Unique student_ids in transfer request. | 422 |

**Promotion:** Only allow if student is in “promotable” state (e.g. active, not graduated); validate year_level mapping exists.

---

## 5. Drag-and-Drop Logic Outline (Frontend)

- **Data model:**  
  - Tree: Programs → Year levels → Blocks.  
  - Each block has a list of students (id, name, block_id, year_level, semester, action).

- **Drag source:** Row(s) in the student table (single or multi-selection).  
- **Drop target:** Block folder (or block row in tree).  
  - Validate on client for UX: same program + same year_level (and semester) for target block; show error toast if invalid.

- **On drop:**
  1. Resolve target block_id from dropped folder.
  2. Collect selected student_ids (from selection state).
  3. Call API `POST /api/registrar/blocks/transfer` with `{ from_block_id, to_block_id, student_ids[] }`.
  4. On 200: refresh block student lists and selection; update transfer log view.  
  5. On 4xx: show message from response body.

- **Prevent drop** when target block is same as source block or when target is in another program/year (disable drop target or show invalid cursor).

---

## 6. Multi-Select Handling Logic

- **Click:** Select single row; clear previous selection (or add to selection if Ctrl held).
- **Ctrl + Click:** Toggle row in selection (add if not selected, remove if selected).
- **Shift + Click:** Range select from last selected index to current index (in current table).
- **Selection state:** Store array of student ids (or row ids) in component state.  
- **Visual:** Highlight selected rows (e.g. background color).  
- **Cut (Ctrl+X):** Copy selected student_ids to “clipboard” state; mark as “cut” (optional visual).  
- **Paste (Ctrl+V):** If “clipboard” has student_ids and a block is focused/selected, call transfer API with from_block_id = current block (or from first selected student’s block), to_block_id = focused block.  
- **Bulk actions:** “Transfer selected” button: open modal to pick target block, then call transfer API.

---

## 7. API Endpoint Structure

Base: `/api` or `/registrar` (depending on existing pattern). All require auth; registrar or admin role for write.

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/registrar/block-explorer/tree` | Tree: programs → year_levels → blocks (id, name, section_name, current_size, max_capacity). |
| GET | `/registrar/block-explorer/blocks/{blockId}/students` | Paginated students in block (id, name, school_id, year_level, semester). |
| POST | `/registrar/blocks/transfer` | Body: `from_block_id`, `to_block_id`, `student_ids[]`. Validates and executes manual transfer; logs to block_transfer_logs. |
| POST | `/registrar/blocks/rebalance` | Optional: trigger auto-rebalance for a block_id or for entire program/year/semester. Returns count moved. |
| POST | `/registrar/blocks/promotion` | Trigger promotion (e.g. body: school_year or “current”). Returns summary. |
| GET | `/registrar/blocks/transfer-log` | List transfer logs (filters: student_id, block_id, transfer_type, date range). Paginated. |
| GET | `/registrar/blocks/{blockId}` | Block detail (capacity, current_size, program, year_level, section_name). |
| Validation | Same validations as in §4; return 422 with message key for UI. |

---

## 8. Transfer Audit Logging System

- **When to log:**  
  - Manual transfer (registrar): `transfer_type = 'manual'`, `initiated_by = auth()->id()`.  
  - Auto-rebalance: `transfer_type = 'auto_rebalance'`, `initiated_by = null`, optional reason.  
  - Promotion: `transfer_type = 'promotion'`, `initiated_by = null`, metadata with year/semester.  
  - Shift/transfer out: `transfer_type = 'shift_out'` when student leaves block due to program change.  
  - Admin correction: `transfer_type = 'admin_correction'`, `initiated_by = auth()->id()`.

- **Fields to store:** student_id, from_block_id, to_block_id, transfer_type, initiated_by, reason, metadata (JSON), created_at.

- **Retention:** Keep indefinitely for audit; optional archival by date.

- **Queries:** By student (history), by block (in/out), by date range, by type. Export CSV for compliance.

---

## Implementation Checklist

- [ ] Migration: blocks add program_id, section_name; ensure max_capacity/code.
- [ ] Migration: block_transfer_logs table.
- [ ] Model BlockTransferLog; Block model relationship.
- [ ] BlockRebalancingService (auto-rebalance).
- [ ] PromotionService or Console command.
- [ ] BlockManagementService (validation + transfer + log).
- [ ] API routes and BlockManagementController (or RegistrarBlockController).
- [ ] Block Explorer UI: tree (Programs → Year → Blocks), student table, multi-select, drag-drop, transfer log view.
