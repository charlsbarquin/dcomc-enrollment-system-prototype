<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Canonical values - single source of truth. */
    private const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    private const SEMESTERS = ['First Semester', 'Second Semester'];

    /** Maps common variants (lowercase key) to canonical value. */
    private function yearLevelMap(): array
    {
        $canonical = [];
        foreach (self::YEAR_LEVELS as $c) {
            $canonical[strtolower(trim($c))] = $c;
        }
        $variants = [
            'first year' => '1st Year',
            'second year' => '2nd Year',
            'third year' => '3rd Year',
            'fourth year' => '4th Year',
            '1st year' => '1st Year',
            '2nd year' => '2nd Year',
            '3rd year' => '3rd Year',
            '4th year' => '4th Year',
            'year 1' => '1st Year',
            'year 2' => '2nd Year',
            'year 3' => '3rd Year',
            'year 4' => '4th Year',
        ];
        return array_merge($canonical, $variants);
    }

    private function semesterMap(): array
    {
        $canonical = [];
        foreach (self::SEMESTERS as $c) {
            $canonical[strtolower(trim($c))] = $c;
        }
        $variants = [
            'first sem' => 'First Semester',
            'second sem' => 'Second Semester',
            '1st semester' => 'First Semester',
            '2nd semester' => 'Second Semester',
            '1st sem' => 'First Semester',
            '2nd sem' => 'Second Semester',
            'sem 1' => 'First Semester',
            'sem 2' => 'Second Semester',
        ];
        return array_merge($canonical, $variants);
    }

    private function normalizeYearLevel(?string $value, array $map): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $key = strtolower(trim($value));
        return $map[$key] ?? null;
    }

    private function normalizeSemester(?string $value, array $map): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $key = strtolower(trim($value));
        return $map[$key] ?? null;
    }

    public function up(): void
    {
        $yearMap = $this->yearLevelMap();
        $semMap = $this->semesterMap();

        // 1) Ensure canonical rows exist in reference tables
        foreach (self::YEAR_LEVELS as $name) {
            DB::table('academic_year_levels')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
        foreach (self::SEMESTERS as $name) {
            DB::table('academic_semesters')->updateOrInsert(
                ['name' => $name],
                ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // 2) Normalize users (set to canonical or null)
        if (Schema::hasTable('users')) {
            $users = DB::table('users')->get(['id', 'year_level', 'semester']);
            foreach ($users as $u) {
                $yl = $this->normalizeYearLevel($u->year_level, $yearMap);
                $sm = $this->normalizeSemester($u->semester, $semMap);
                $up = [];
                if ($u->year_level !== null && $u->year_level !== '') {
                    $up['year_level'] = $yl;
                }
                if ($u->semester !== null && $u->semester !== '') {
                    $up['semester'] = $sm;
                }
                if (! empty($up)) {
                    DB::table('users')->where('id', $u->id)->update($up);
                }
            }
        }

        // 3) Normalize blocks
        if (Schema::hasTable('blocks')) {
            $blocks = DB::table('blocks')->get(['id', 'year_level', 'semester']);
            foreach ($blocks as $b) {
                $yl = $this->normalizeYearLevel($b->year_level, $yearMap);
                $sm = $this->normalizeSemester($b->semester, $semMap);
                $up = [];
                if ($b->year_level !== null && $b->year_level !== '') {
                    $up['year_level'] = $yl;
                }
                if ($b->semester !== null && $b->semester !== '') {
                    $up['semester'] = $sm;
                }
                if (! empty($up)) {
                    DB::table('blocks')->where('id', $b->id)->update($up);
                }
            }
        }

        // 4) Normalize schedule_templates
        if (Schema::hasTable('schedule_templates')) {
            $templates = DB::table('schedule_templates')->get(['id', 'year_level', 'semester']);
            foreach ($templates as $t) {
                $up = [];
                if ($t->year_level !== null && $t->year_level !== '') {
                    $up['year_level'] = $this->normalizeYearLevel($t->year_level, $yearMap);
                }
                if ($t->semester !== null && $t->semester !== '') {
                    $up['semester'] = $this->normalizeSemester($t->semester, $semMap);
                }
                if (! empty($up)) {
                    DB::table('schedule_templates')->where('id', $t->id)->update($up);
                }
            }
        }

        // 5) Normalize enrollment_forms
        if (Schema::hasTable('enrollment_forms')) {
            $forms = DB::table('enrollment_forms')->get(['id', 'assigned_semester', 'incoming_year_level', 'incoming_semester']);
            foreach ($forms as $f) {
                $up = [];
                if ($f->assigned_semester !== null && $f->assigned_semester !== '') {
                    $up['assigned_semester'] = $this->normalizeSemester($f->assigned_semester, $semMap);
                }
                if ($f->incoming_year_level !== null && $f->incoming_year_level !== '') {
                    $up['incoming_year_level'] = $this->normalizeYearLevel($f->incoming_year_level, $yearMap);
                }
                if ($f->incoming_semester !== null && $f->incoming_semester !== '') {
                    $up['incoming_semester'] = $this->normalizeSemester($f->incoming_semester, $semMap);
                }
                if (! empty($up)) {
                    DB::table('enrollment_forms')->where('id', $f->id)->update($up);
                }
            }
        }

        // 6) Normalize assessments
        if (Schema::hasTable('assessments')) {
            $rows = DB::table('assessments')->get(['id', 'semester']);
            foreach ($rows as $r) {
                if ($r->semester !== null && $r->semester !== '') {
                    $sm = $this->normalizeSemester($r->semester, $semMap);
                    DB::table('assessments')->where('id', $r->id)->update(['semester' => $sm]);
                }
            }
        }

        // 7) Normalize class_schedules
        if (Schema::hasTable('class_schedules')) {
            $rows = DB::table('class_schedules')->get(['id', 'semester']);
            foreach ($rows as $r) {
                if ($r->semester !== null && $r->semester !== '') {
                    $sm = $this->normalizeSemester($r->semester, $semMap);
                    DB::table('class_schedules')->where('id', $r->id)->update(['semester' => $sm]);
                }
            }
        }

        // 8) Normalize subjects
        if (Schema::hasTable('subjects')) {
            $rows = DB::table('subjects')->get(['id', 'year_level', 'semester']);
            foreach ($rows as $r) {
                $up = [];
                if ($r->year_level !== null && $r->year_level !== '') {
                    $up['year_level'] = $this->normalizeYearLevel($r->year_level, $yearMap);
                }
                if ($r->semester !== null && $r->semester !== '') {
                    $up['semester'] = $this->normalizeSemester($r->semester, $semMap);
                }
                if (! empty($up)) {
                    DB::table('subjects')->where('id', $r->id)->update($up);
                }
            }
        }

        // 9) Keep only canonical rows in reference tables (remove duplicates/variants)
        DB::table('academic_year_levels')->whereNotIn('name', self::YEAR_LEVELS)->delete();
        DB::table('academic_semesters')->whereNotIn('name', self::SEMESTERS)->delete();
    }

    public function down(): void
    {
        // Cannot safely reverse data normalization.
    }
};
