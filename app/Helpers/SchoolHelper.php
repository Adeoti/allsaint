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

if (!function_exists('abbreviate')) {
    function abbreviate($subjectName)
    {
        $abbreviations = [
            'mathematics' => 'MATH',
            'english' => 'ENG',
            'english language' => 'ENG',
            'agricultural science' => 'AGR',
            'agriculture' => 'AGR',
            'basic science' => 'BSC',
            'basic technology' => 'BTEC',
            'business studies' => 'BUS',
            'christian religious studies' => 'CRS',
            'civic education' => 'CIV',
            'computer studies' => 'COMP',
            'creative arts' => 'ART',
            'economics' => 'ECON',
            'french' => 'FREN',
            'geography' => 'GEO',
            'government' => 'GOV',
            'history' => 'HIST',
            'home economics' => 'HOME',
            'integrated science' => 'INTSC',
            'literature in english' => 'LIT',
            'physical education' => 'PE',
            'physics' => 'PHY',
            'chemistry' => 'CHEM',
            'biology' => 'BIO',
            'further mathematics' => 'FMATH',
            'technical drawing' => 'TD',
            'music' => 'MUS',
            'social studies' => 'SOC',
            'verbal reasoning' => 'VR',
            'quantitative reasoning' => 'QR',
            'phonics' => 'PHON',
            'handwriting' => 'WRIT',
            'spelling' => 'SPEL',
            'grammar' => 'GRAM',
            'reading' => 'READ',
            'writing' => 'WRIT',
            'numeracy' => 'NUM',
            'moral instruction' => 'MORAL',
            'health education' => 'HEALTH',
            'security education' => 'SEC',
            'yoruba' => 'YOR',
            'hausa' => 'HAU',
            'igbo' => 'IGB',
            'arabic' => 'ARAB',
            'spanish' => 'SPAN',
            'german' => 'GER',
            'chinese' => 'CHIN',
            'ict' => 'ICT',
            'information technology' => 'IT',
            'computer science' => 'COMPSC',
            'data processing' => 'DP',
            'typewriting' => 'TYPE',
            'shorthand' => 'SHORT',
            'office practice' => 'OFF',
            'book keeping' => 'BOOK',
            'financial accounting' => 'ACC',
            'commerce' => 'COM',
            'marketing' => 'MARK',
            'store management' => 'STORE',
            'auto mechanics' => 'AUTO',
            'electrical installation' => 'ELEC',
            'metalwork' => 'METAL',
            'woodwork' => 'WOOD',
            'building construction' => 'BUILD',
            'fine arts' => 'FINE',
            'clothing and textiles' => 'CLOTH',
            'food and nutrition' => 'FOOD',
            'home management' => 'HOME',
            'child development' => 'CHILD',
            'fishery' => 'FISH',
            'animal husbandry' => 'ANIM',
            'crop husbandry' => 'CROP',
            'islamic religious studies' => 'IRS',
            'general mathematics' => 'MATH',
            'general science' => 'SCI',
            'social science' => 'SS',
            'environmental studies' => 'ENV',
            'evs' => 'ENV',
            'life skills' => 'LS',
            'value education' => 'VAL',
            'library' => 'LIB',
            'guidance' => 'GUID',
            'counselling' => 'COUN',
            'entrepreneurship' => 'ENT',
            'project work' => 'PROJ',
        ];
        
        $subjectLower = strtolower(trim($subjectName));
        
        // Special case: "Agric" should map to "AGR"
        if ($subjectLower === 'agric') {
            return 'AGR';
        }
        
        if (array_key_exists($subjectLower, $abbreviations)) {
            return $abbreviations[$subjectLower];
        }
        
        // If not found in abbreviations, create custom abbreviation
        $words = explode(' ', $subjectLower);
        if (count($words) == 1) {
            return strtoupper(substr($subjectName, 0, 4));
        } else {
            // Take first letters of first 3 words
            $abbr = '';
            $wordCount = 0;
            foreach ($words as $word) {
                if ($wordCount >= 3) break;
                if (strlen($word) > 0) {
                    $abbr .= strtoupper($word[0]);
                    $wordCount++;
                }
            }
            return strlen($abbr) > 1 ? $abbr : strtoupper(substr($subjectName, 0, 3));
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
