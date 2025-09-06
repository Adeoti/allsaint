<?php

use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Schema;

if (!function_exists('getSchoolDetails')) {
    function getSchoolDetails(): array
    {
        // Default values (safe fallbacks)
        $defaults = [
            'school_name'         => 'My School',
            'school_address'      => '',
            'school_phone'        => '',
            'school_logo'         => '',
            'school_favicon'      => '',
            'meta_description'    => '',
            'meta_title'          => '',
            'meta_keywords'       => '',
            'principal_name'      => '',
            'principal_signature' => '',
        ];

        try {
            // Check if table exists first
            if (!Schema::hasTable('settings')) {
                return $defaults;
            }

            // Fetch first record safely
            $school = Setting::first();

            if (!$school) {
                return $defaults;
            }

            return [
                'school_name'         => $school->school_name ?? $defaults['school_name'],
                'school_address'      => $school->address ?? $defaults['school_address'],
                'school_phone'        => $school->contact ?? $defaults['school_phone'],
                'school_logo'         => $school->logo ?? $defaults['school_logo'],
                'school_favicon'      => $school->favicon ?? $defaults['school_favicon'],
                'meta_description'    => $school->meta_description ?? $defaults['meta_description'],
                'meta_title'          => $school->meta_title ?? $defaults['meta_title'],
                'meta_keywords'       => $school->meta_keywords ?? $defaults['meta_keywords'],
                'principal_name'      => $school->principal_name ?? $defaults['principal_name'],
                'principal_signature' => $school->principal_signature ?? $defaults['principal_signature'],
            ];
        } catch (\Throwable $e) {
            // In case migrations are not run or any DB error
            return $defaults;
        }
    }
}

if (!function_exists('getSchoolStats')) {
    function getSchoolStats(): array
    {
        try {
            return [
                'totalStudents' => number_format(Student::count()),
                'totalTeachers' => number_format(Teacher::count()),
                'totalClasses'  => number_format(SchoolClass::count()),
            ];
        } catch (\Throwable $e) {
            // In case tables are missing
            return [
                'totalStudents' => '0',
                'totalTeachers' => '0',
                'totalClasses'  => '0',
            ];
        }
    }
}
