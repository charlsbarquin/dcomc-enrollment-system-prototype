# Deployment Simulation Summary

**Date:** March 6, 2025

## What Was Done

### 1. Full system check (routes, roles, flows)

- **Routes:** All role prefixes verified: `admin.*`, `registrar.*`, `staff.*`, `dean.*`, `unifast.*`, `student.*`. Each role has its own dashboard and feature routes.
- **Migrations:** Ran `php artisan migrate --force`; one new migration applied successfully.
- **Caches:** Cleared config, route, and view caches.

### 2. Bugs found and fixed

| Issue | Fix |
|-------|-----|
| **Staff had no Block Explorer page** | Staff had sub-routes (`/staff/block-explorer/tree`, `.../blocks/{block}/students`, etc.) but no GET `/staff/block-explorer`. Added `Route::get('/staff/block-explorer', [BlockManagementController::class, 'blockExplorer'])->name('staff.block-explorer');` so the Block Explorer view is reachable under staff. |
| **Block Explorer / Students Explorer JS base used role instead of route** | In `block-explorer.blade.php` and `students-explorer.blade.php`, the JavaScript `base` URL was set from `auth()->user()->role === 'staff'`. Changed to route-based: `request()->routeIs("staff.*")` (or `$isStaff` from the same check) so sidebar and AJAX URLs stay correct when URL is `/staff/...` or `/registrar/...`. |
| **Debug logging left in controller** | Removed the temporary `#region agent log` block from `BlockManagementController::blockExplorer()` that wrote to `debug-c1abed.log`. |

### 3. Role-by-role flow (code-level)

- **Admin:** Dashboard, accounts, student status (enroll/reject/needs-correction/delete), blocks, block-change-requests, reports, analytics, workflow-qa, staff/registrar access settings, role-switch. All use `admin.*` routes and admin sidebar (via `role-sidebar`).
- **Registrar:** Dashboard, manual registration, form builder, responses (approve/reject), students, irregularities, COR archive (regular + irregular), block explorer, blocks, block-change-requests, program schedule, schedule forms, settings. All use `registrar.*` routes.
- **Staff:** Same controllers as registrar for shared pages; routes under `staff.*`. Feature flags gate approve/reject and block-requests. Block Explorer page and tree/students/transfer/rebalance/promotion/transfer-log now work under `/staff/block-explorer` and correct `base` in JS.
- **Dean:** Dashboard, student-status, reports, scheduling, schedule-by-scope, COR deploy/fetch, COR archive, professors/rooms settings, manage-professor. Uses `dean.*` and `cor.archive.*` routes; `dean.department` middleware where required.
- **UniFast:** Dashboard, student-status, assessments (list, export, eligibility), fees settings (feature-flagged), reports (feature-flagged). Uses `unifast.*` routes.
- **Student:** Dashboard, profile (complete/edit), enrollment form submit, block-change-request, view COR. Uses `student.*` routes and `ensure.student.profile` middleware.

### 4. Automated tests

- **Result:** 24 tests failed, 2 passed.
- **Cause:** All failures are **environment**: `could not find driver (Connection: sqlite, Database: :memory:)`. PHP on this machine does not have the SQLite extension enabled; Laravel’s default test setup uses in-memory SQLite.
- **Conclusion:** No application code fix for this. To run tests: enable `pdo_sqlite` (and `sqlite`) in `php.ini`, or switch the test database in `phpunit.xml` / `.env.testing` to MySQL if available.

## Ideal flow (unchanged)

- **Student:** Login (student portal) → complete profile (if needed) → dashboard → submit enrollment when open → wait for approval → (optional) block-change request → view COR.
- **Registrar/Staff:** Login (DCOMC) → set school year (if needed) → dashboard → admission (manual/builder/responses) and/or student records (explorers, blocks, irregularities, COR archive) → approve/reject applications and block requests (staff only if feature enabled).
- **Admin:** Login (admin portal) → dashboard → accounts, student status, blocks, block-change-requests, reports, analytics, workflow-qa, staff/registrar access; optional role-switch to test other roles.
- **Dean:** Login (DCOMC) → dashboard → scheduling, schedule by scope, COR deploy/fetch, COR archive, professors/rooms, manage-professor.
- **UniFast:** Login (DCOMC) → dashboard → student status, assessments (eligibility), fees/reports if enabled.

## Files changed

- `routes/web.php` — added GET `/staff/block-explorer` (staff.block-explorer).
- `resources/views/dashboards/block-explorer.blade.php` — JS base from `request()->routeIs("staff.*")`; Transfer Log link already uses `base`.
- `resources/views/dashboards/students-explorer.blade.php` — JS base from `$isStaff` (already route-based in PHP).
- `app/Http/Controllers/BlockManagementController.php` — removed debug logging in `blockExplorer()`.

## Deployment readiness

- **Code:** Routes and role-based UI (role-sidebar) are consistent; staff Block Explorer and JS URLs fixed. No feature logic was changed.
- **Tests:** Require SQLite (or another test DB) to be configured and available in PHP.
- **Production:** Run `php artisan config:cache`, `route:cache`, `view:cache` (optional), ensure `.env` and DB driver are correct, then deploy as usual.
