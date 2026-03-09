# DCOMC — Daraga Community College Enrollment & Registration System

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A web-based **enrollment, registration, and student information system** for **Daraga Community College (DCOMC)**. It supports multiple portals (Student, Staff/Registrar/Dean/UniFast, and Admin), dynamic enrollment forms, block management, scheduling, Certificate of Registration (COR) generation, analytics, and role-based dashboards.

---

## For GitHub

When creating the repository, you can use:

| Field | Value |
|-------|--------|
| **Repository name** | `dcomc-enrollment-system-prototype` |
| **Description** | Web-based enrollment, registration & student information system for Daraga Community College. Laravel 12, multi-portal (Student / Registrar / Admin / Dean / UniFast), COR, blocks, scheduling & analytics. |
| **Topics (optional)** | `laravel`, `enrollment`, `student-information-system`, `php`, `blade`, `tailwindcss`, `education` |

---

## Features

### Multi-portal authentication

- **Student portal** (`/`) — Students sign in with Student ID / School ID and password.
- **DCOMC / Staff portal** (`/dcomc-login`) — Registrar, Staff, Dean, and UniFast personnel.
- **Admin portal** (`/admin-login`) — System administrators only.

Role and portal are validated on login; each role has its own dashboard and permissions.

### By role

| Role | Highlights |
|------|------------|
| **Student** | Profile (personal, academic, family, address), view application status, submit enrollment forms, request block changes, view/print COR. |
| **Registrar** | Enrollment form builder & deployment, manual registration (including import), form responses & approval, student listing, block management (explorer, transfer, rebalance, promotion), COR archive, irregular schedules, program schedule, academic settings (school years, semesters, year levels, blocks, subjects, fees, professors, rooms), staff/UniFast access settings, COR scopes. |
| **Staff** | Subset of registrar features (admission, student records, blocks, COR archive, reports, analytics) with configurable permissions. |
| **Dean** | Scheduling by scope, room utilization, professor management & teaching load, COR archive, reports, department-scoped settings. |
| **UniFast** | Assessments, UniFast eligibility, fee settings (if permitted), reports. |
| **Admin** | Full dashboard, account management (create student/DCOMC accounts), student status & enrollment actions, blocks & block-change requests, reports, audit/activity logs, staff/registrar/UniFast access settings, academic settings (mirroring registrar), system (backup, logs, maintenance, code editor), role-switch (impersonate other roles for testing). |

### Core modules

- **Enrollment** — Global enrollment toggle, configurable forms per year/semester, student applications, approve/reject/needs-correction workflow.
- **Blocks** — Block creation, assignment, transfer, rebalance, promotion, block-change requests, master list and COR print by block.
- **Scheduling** — Program schedule, dean scheduling, room utilization, conflict checks, deploy/fetch COR.
- **COR (Certificate of Registration)** — View/print per student, by block, archive by program/year/semester, irregular COR archive.
- **Academic settings** — School years, semesters, year levels, blocks, subjects, fees, professors, rooms, COR scopes.
- **Reports & analytics** — Filterable reports and analytics with print and export (e.g. CSV).
- **Finance / UniFast** — Assessments, eligibility tagging, fee configuration.

---

## Tech stack

- **Backend:** PHP 8.2+, Laravel 12
- **Frontend:** Blade, Tailwind CSS, Alpine.js (on some pages), Vite
- **Auth:** Laravel Breeze-style session auth, single `web` guard, role-based middleware
- **Database:** MySQL/PostgreSQL/SQLite (via Laravel migrations)

---

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm (for Vite and frontend assets)
- Database supported by Laravel (e.g. MySQL, MariaDB, PostgreSQL, SQLite)

---

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/dcomc-enrollment-system.git
   cd dcomc-enrollment-system
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Edit `.env` and set `APP_NAME`, `APP_URL`, `DB_*`, and any other required variables.

4. **Database**
   ```bash
   php artisan migrate
   # Optional: php artisan db:seed
   ```

5. **Frontend**
   ```bash
   npm install
   npm run build
   # Or for development: npm run dev
   ```

6. **Run the app**
   ```bash
   php artisan serve
   ```
   Then open the app (e.g. `http://localhost:8000`). Use the Student, DCOMC, or Admin login URLs as needed.

---

## Login URLs

| Portal | URL |
|--------|-----|
| Student | `/` |
| DCOMC (Staff / Registrar / Dean / UniFast) | `/dcomc-login` |
| Admin | `/admin-login` |

---

## Project structure (high level)

- `app/Http/Controllers/` — Admin, Registrar, Dean, UniFast, Staff, Student, Auth, Reports, COR, Block management, etc.
- `app/Models/` — User, EnrollmentForm, FormResponse, Block, SchoolYear, AcademicSemester, and other domain models.
- `resources/views/auth/` — Login views (student, admin, dcomc) and shared layout.
- `resources/views/dashboards/` — Role-specific dashboards and feature views.
- `routes/web.php` — All web routes grouped by middleware (auth, role).

---

## License

This project is open-sourced under the [MIT License](LICENSE).

---

## Daraga Community College

This system was developed for **Daraga Community College (DCOMC)** to manage enrollment, registration, blocks, scheduling, and student records in one place across Student, Registrar, Staff, Dean, UniFast, and Admin roles.
