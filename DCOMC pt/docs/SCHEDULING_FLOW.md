# DCOMC Official Scheduling Flow

## 1. System flow overview

1. **Registrar phase**  
   - Assigns subjects to Program + Year Level (Subject Settings, already segregated).  
   - Defines academic load for each group.

2. **Dean phase**  
   - Sees only subjects (and programs) under their department.  
   - Assigns Professor, Room, Days, Start time, End time (Schedule by Program or Scheduling).

3. **COR deployment**  
   - When scheduling is complete, Registrar deploys schedule (e.g. Schedule Form “Deploy” or scope schedule in use).  
   - Students get COR with block subjects, assigned professor, room, time & days.  
   - Students cannot edit schedule.

---

## 2. Database schema (scope & audit)

**users (professors)**  
- `department_scope`: `"all"` | `"education"` | `"entrepreneurship"`  
- `created_by_role`, `created_by_user_id` (audit when created via Settings)

**rooms**  
- `department_scope`: `"all"` | `"education"` | `"entrepreneurship"`  
- `created_by_role`, `created_by_user_id` (audit when created via Settings)

**Conflict detection (existing)**  
- `DeanSchedulingController::hasTimeConflict()`: same room, same professor, or same block at overlapping time → reject.

---

## 3. Role-based permission logic

**Registrar**  
- Can create Professor with scope: `all`, `education`, `entrepreneurship`.  
- Can create Room with scope: `all`, `education`, `entrepreneurship`.  
- Sees all professors and all rooms.

**Dean – Education**  
- Can create Professor with scope: `all`, `education` (cannot create `entrepreneurship`-only).  
- Can create Room with scope: `all`, `education`.  
- Sees professors where `department_scope` IN (`education`, `all`) or null.  
- Sees rooms where `department_scope` IN (`education`, `all`) or null.

**Dean – Entrepreneurship**  
- Can create Professor with scope: `all`, `entrepreneurship` (cannot create `education`-only).  
- Can create Room with scope: `all`, `entrepreneurship`.  
- Sees professors/rooms where `department_scope` IN (`entrepreneurship`, `all`) or null.

---

## 4. Scope filtering (query examples)

**Professors visible to current user**  
- Service: `SchedulingScopeService::scopeProfessorsForViewer($query, $user)`.  
- Logic: `WHERE (department_scope IN (<visible_scopes>) OR department_scope IS NULL)` and `faculty_type IS NOT NULL`.

**Rooms visible to current user**  
- Service: `SchedulingScopeService::scopeRoomsForViewer($query, $user)`.  
- Same pattern for `rooms.department_scope`.

**On schedule save (dean)**  
- `SchedulingScopeService::professorScopeCompatibleWithDean($professor, $dean)`: professor’s scope is `all` or matches dean’s department.  
- `SchedulingScopeService::roomScopeCompatibleWithDean($room, $dean)`: same for room.

---

## 5. Conflict detection

- **Room conflict**: same `room_id`, same `day_of_week`, overlapping `start_time`–`end_time`.  
- **Professor conflict**: same `professor_id`, same `day_of_week`, overlapping time.  
- **Block conflict**: same `block_id`, same `day_of_week`, overlapping time.  
- Implemented in `DeanSchedulingController::store()` and `hasTimeConflict()`.

---

## 6. COR deployment flow

- Students get COR from deployed **Schedule Template** (Schedule Forms) for their block, or from scope-level schedule (Schedule by Program).  
- COR content: subjects, professor, room, time, days from `ClassSchedule` / `ScopeScheduleSlot`.  
- Registrar “deploys” by deploying the relevant Schedule Form for that scope, or by having scope schedule data filled; students then see COR when they open it (no separate “lock” step beyond deployment).

---

## 7. Settings (Professors & Rooms)

- **Registrar**: Settings → Professors, Settings → Rooms (add with any allowed scope).  
- **Dean**: Settings → Professors, Settings → Rooms (add with scope limited by department; list filtered by visible scope).  
- Backend: `SchedulingScopeService::allowedScopesForCreator()` and `visibleScopesForViewer()` enforce permissions.
