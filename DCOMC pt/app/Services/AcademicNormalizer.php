<?php

namespace App\Services;

use App\Models\AcademicSemester;
use App\Models\AcademicYearLevel;

/**
 * Single source for canonical year level and semester values.
 * Use this to normalize strings so blocks, users, and tree never duplicate folders.
 */
class AcademicNormalizer
{
    /** Fixed order: 1st–4th year, each with First and Second semester. */
    public static function canonicalYearSemesterOrder(): array
    {
        $order = [];
        foreach (AcademicYearLevel::CANONICAL as $yl) {
            foreach (AcademicSemester::CANONICAL as $sem) {
                $order[] = ['year_level' => $yl, 'semester' => $sem];
            }
        }
        return $order;
    }

    public static function normalizeYearLevel(?string $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $key = strtolower(trim($value));
        $map = self::yearLevelMap();
        return $map[$key] ?? null;
    }

    public static function normalizeSemester(?string $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $key = strtolower(trim($value));
        $map = self::semesterMap();
        return $map[$key] ?? null;
    }

    /** Sort key for (year_level, semester) to match canonical order (0–7). */
    public static function yearSemesterSortIndex(string $yearLevel, string $semester): int
    {
        $order = self::canonicalYearSemesterOrder();
        foreach ($order as $i => $pair) {
            if ($pair['year_level'] === $yearLevel && $pair['semester'] === $semester) {
                return $i;
            }
        }
        return 999;
    }

    private static function yearLevelMap(): array
    {
        $canonical = [];
        foreach (AcademicYearLevel::CANONICAL as $c) {
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

    private static function semesterMap(): array
    {
        $canonical = [];
        foreach (AcademicSemester::CANONICAL as $c) {
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
}
