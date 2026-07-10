<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $record->name }} - Broadsheet</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            
            #printableArea,
            #printableArea * {
                visibility: visible;
            }
            
            #printableArea {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            
            button, a, .no-print {
                display: none !important;
            }
            
            @page {
                margin: 0.5in;
                size: A4 landscape;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 0.875rem;
        }
        
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px 4px;
            text-align: center;
            vertical-align: middle;
        }
        
        th {
            background-color: #f3f4f6;
            font-weight: 600;
            color: #111827;
            position: sticky;
            top: 0;
            z-index: 20;
        }
        
        .subject-header {
            min-width: 60px; /* Reduced from 100px */
            max-width: 80px;
            background-color: #e5e7eb;
            position: relative;
        }
        
        .sticky-left {
            position: sticky;
            left: 0;
            background: white;
            z-index: 10;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        
        .sticky-serial {
            position: sticky;
            left: 0;
            background: #f9fafb;
            z-index: 11;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
        }
        
        .header-gradient {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 3px solid #dc2626;
        }
        
        .performance-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 4px;
        }
        
        .grade-a { background-color: #dcfce7; color: #166534; }
        .grade-b { background-color: #dbeafe; color: #1e40af; }
        .grade-c { background-color: #fef3c7; color: #92400e; }
        .grade-d { background-color: #fef9c3; color: #854d0e; }
        .grade-e { background-color: #fee2e2; color: #991b1b; }
        .grade-f { background-color: #fca5a5; color: #7f1d1d; }
        
        .hover-row:hover {
            background-color: #f0f9ff;
            transition: background-color 0.2s;
        }
        
        .summary-highlight {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: white;
            font-weight: 600;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.03;
            font-size: 120px;
            font-weight: bold;
            color: #000;
            pointer-events: none;
            z-index: 0;
        }
        
        .scroll-container {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: auto;
        }
        
        .signature-line {
            border-bottom: 1px solid #374151;
            width: 100%;
            margin-bottom: 4px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid #dc2626;
        }
        
        .subject-tooltip {
            position: relative;
            cursor: help;
        }
        
        .subject-tooltip:hover::after {
            content: attr(data-fullname);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 100;
            margin-bottom: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .subject-tooltip:hover::before {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-top-color: #1f2937;
            margin-bottom: -5px;
            z-index: 101;
        }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <!-- Watermark -->
    <div class="watermark no-print">{{ $schoolDetails['school_name'] ?? 'SCHOOL' }}</div>
    
    <div class="max-w-7xl mx-auto">
        <!-- Print and Download Buttons -->
        <div class="mb-6 flex gap-4 no-print">
            <button onclick="printBroadsheet()"
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-200 active:bg-green-600 disabled:opacity-25 transition">
                <i class="fas fa-print mr-2"></i>
                Print Broadsheet
            </button>
            
            {{-- <button onclick="exportToExcel()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-600 disabled:opacity-25 transition">
                <i class="fas fa-file-excel mr-2"></i>
                Export Excel
            </button> --}}
            
            <div class="flex-1"></div>
            
            <a href="{{ url('/admin/broadsheets') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 focus:outline-none focus:border-gray-700 focus:ring focus:ring-gray-200 active:bg-gray-600 disabled:opacity-25 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to List
            </a>
        </div>

        <!-- Statistics Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 no-print">
            <div class="stat-card">
                <div class="text-sm text-gray-600">Total Students</div>
                <div class="text-2xl font-bold text-gray-800">{{ $broadsheetData['total_students'] ?? 0 }}</div>
            </div>
            <div class="stat-card">
                <div class="text-sm text-gray-600">Class Average</div>
                <div class="text-2xl font-bold text-green-600">
                    @php
                        $classAverage = 0;
                        if (!empty($broadsheetData['students'])) {
                            $classTotal = array_sum(array_column($broadsheetData['students'], 'total'));
                            $classAverage = count($broadsheetData['students']) > 0 
                                ? round($classTotal / count($broadsheetData['students']), 2) 
                                : 0;
                        }
                    @endphp
                    {{ $classAverage }}%
                </div>
            </div>
            <div class="stat-card">
                <div class="text-sm text-gray-600">Total Subjects</div>
                <div class="text-2xl font-bold text-blue-600">{{ count($broadsheetData['subjects'] ?? []) }}</div>
            </div>
            <div class="stat-card">
                <div class="text-sm text-gray-600">Academic Term</div>
                <div class="text-2xl font-bold text-purple-600">{{ $record->term ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Broadsheet Content -->
        <div id="printableArea" class="bg-white rounded-lg shadow-lg border border-gray-200">
            <!-- Header Section -->
            <div class="p-6 header-gradient">
                <div class="flex items-start">
                    <!-- Logo on Left -->
                    <div class="w-1/6 flex justify-start items-start">
                        @if (isset($schoolDetails['school_logo']) && $schoolDetails['school_logo'])
                            <img src="{{ Storage::url($schoolDetails['school_logo']) }}" 
                                 alt="School Logo" 
                                 class="h-20 w-20 object-contain">
                        @else
                            <div class="h-20 w-20 bg-red-100 rounded-full flex items-center justify-center border-2 border-red-200">
                                <i class="fas fa-school text-3xl text-red-400"></i>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Centered School Details -->
                    <div class="w-4/6 text-center">
                        <h1 class="text-3xl font-bold text-red-700 mb-1">{{ $schoolDetails['school_name'] ?? 'School Name' }}</h1>
                        <div class="text-gray-600 mb-1">{{ $schoolDetails['school_address'] ?? 'School Address' }}</div>
                        <div class="text-gray-600 mb-3">{{ $schoolDetails['school_phone'] ?? 'Phone: N/A' }} | {{ $schoolDetails['school_email'] ?? 'Email: info@'.config('app.url') }}</div>
                        
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $record->name }}</h2>
                        
                        <div class="flex justify-center items-center gap-6 text-sm text-gray-700">
                            <div><i class="fas fa-graduation-cap mr-1"></i> Class: {{ $classInfo->name ?? 'N/A' }}</div>
                            <div><i class="fas fa-calendar-alt mr-1"></i> Term: {{ $record->term ?? 'N/A' }}</div>
                            {{-- <div><i class="fas fa-clock mr-1"></i> Session: {{ $record->session ?? 'N/A' }}</div> --}}
                            <div><i class="fas fa-users mr-1"></i> Students: {{ $broadsheetData['total_students'] ?? 0 }}</div>
                        </div>
                    </div>
                    
                    <!-- Empty right column for balance -->
                    <div class="w-1/6"></div>
                </div>
                
                <div class="mt-4 text-center text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Generated on {{ \Carbon\Carbon::parse($broadsheetData['generated_at'] ?? now())->format('F j, Y \a\t h:i A') }}
                </div>
            </div>

            <!-- Broadsheet Table -->
            <div class="scroll-container">
                <table>
                    <thead>
                        <tr>
                            <th class="sticky-serial bg-gray-100" style="min-width: 50px;">
                                S/No
                            </th>
                            <th class="sticky-left bg-gray-100" style="min-width: 180px;">
                                Student Name
                            </th>
                            {{-- <th class="bg-gray-100" style="min-width: 80px;">
                                Roll No.
                            </th> --}}
                            
                            @foreach ($broadsheetData['subjects'] ?? [] as $subjectId => $subjectName)
                                @php
                                  
                                    
                                    $subjectAbbr =  abbreviate($subjectName);
                                @endphp
                                <th class="subject-header subject-tooltip" data-fullname="{{ $subjectName }}" title="{{ $subjectName }}">
                                    <div class="font-bold text-sm">{{ $subjectAbbr }}</div>
                                    <div class="text-xs text-gray-600 font-normal"><span title="Score">Sc</span> | <span title="Grade">Gr</span></div>
                                </th>
                            @endforeach
                            
                            <th class="bg-gray-200" style="min-width: 70px;">
                                <div class="font-bold text-sm">TOTAL</div>
                                <div class="text-xs text-gray-600 font-normal">
                                    @php
                                        $totalScore = ($broadsheetData['total_subject_score'] ?? 100) * count($broadsheetData['subjects'] ?? []);
                                        // Format large numbers with K for thousands
                                        if ($totalScore >= 1000) {
                                            echo round($totalScore/1000, 1) . 'K';
                                        } else {
                                            echo $totalScore;
                                        }
                                    @endphp
                                </div>
                            </th>
                            <th class="bg-gray-200" style="min-width: 60px;">
                                <div class="font-bold text-sm">AVG %</div>
                            </th>
                            <th class="bg-gray-200" style="min-width: 70px;">
                                <div class="font-bold text-sm">POS</div>
                            </th>
                            <th class="bg-gray-200" style="min-width: 90px;">
                                <div class="font-bold text-sm">REMARKS</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($broadsheetData['students'] ?? [] as $student)
                            <tr class="hover-row">
                                <td class="sticky-serial bg-gray-50 font-semibold">
                                    {{ $student['sno'] }}
                                </td>
                                <td class="sticky-left bg-white font-medium text-gray-900">
                                    {{ $student['name'] }}
                                </td>
                                {{-- <td class="font-mono text-sm">
                                    {{ $student['roll_number'] }}
                                </td> --}}
                                
                                @foreach ($broadsheetData['subjects'] ?? [] as $subjectId => $subjectName)
                                    <td>
                                        @php
                                            $subjectScore = $student['subjects'][$subjectId]['score'] ?? 0;
                                            $subjectGrade = $student['subjects'][$subjectId]['grade'] ?? 'N/A';
                                            $gradeClass = '';
                                            if (strtoupper($subjectGrade) == 'A') $gradeClass = 'grade-a';
                                            elseif (strtoupper($subjectGrade) == 'B') $gradeClass = 'grade-b';
                                            elseif (strtoupper($subjectGrade) == 'C') $gradeClass = 'grade-c';
                                            elseif (strtoupper($subjectGrade) == 'D') $gradeClass = 'grade-d';
                                            elseif (strtoupper($subjectGrade) == 'E') $gradeClass = 'grade-e';
                                            elseif (strtoupper($subjectGrade) == 'F') $gradeClass = 'grade-f';
                                        @endphp
                                        <div class="flex flex-col items-center">
                                            <span class="font-bold">{{ $subjectScore }}</span>
                                            <span class="performance-badge {{ $gradeClass }} text-xs">{{ $subjectGrade }}</span>
                                        </div>
                                    </td>
                                @endforeach
                                
                                <td class="font-bold bg-gray-50">
                                    {{ $student['total'] }}
                                </td>
                                <td class="font-bold {{ $student['average'] >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $student['average'] ?? 'N/A' }}
                                </td>
                                <td class="font-extrabold text-blue-700">
                                    {{ $student['position'] }}
                                </td>
                                <td class="text-sm {{ $student['average'] >= 70 ? 'text-green-700' : ($student['average'] >= 50 ? 'text-yellow-700' : 'text-red-700') }}">
                                    {{ $student['average'] >= 70 ? 'Excellent' : ($student['average'] >= 50 ? 'Good' : 'Improve') }}
                                </td>
                            </tr>
                        @endforeach
                        
                        <!-- Summary Row -->
                        @if (!empty($broadsheetData['students']))
                            @php
                                $subjectAverages = [];
                                $subjectHighest = [];
                                $subjectLowest = [];
                                
                                foreach ($broadsheetData['subjects'] ?? [] as $subjectId => $subjectName) {
                                    $subjectScores = [];
                                    foreach ($broadsheetData['students'] as $student) {
                                        if (isset($student['subjects'][$subjectId]['score'])) {
                                            $subjectScores[] = $student['subjects'][$subjectId]['score'];
                                        }
                                    }
                                    $subjectAverages[$subjectId] = count($subjectScores) > 0 
                                        ? round(array_sum($subjectScores) / count($subjectScores), 1) 
                                        : 0;
                                    $subjectHighest[$subjectId] = count($subjectScores) > 0 ? max($subjectScores) : 0;
                                    $subjectLowest[$subjectId] = count($subjectScores) > 0 ? min($subjectScores) : 0;
                                }
                                
                                $classTotal = array_sum(array_column($broadsheetData['students'], 'total'));
                                $classAverage = count($broadsheetData['students']) > 0 
                                    ? round($classTotal / count($broadsheetData['students']), 1) 
                                    : 0;
                                $highestTotal = max(array_column($broadsheetData['students'], 'total'));
                                $lowestTotal = min(array_column($broadsheetData['students'], 'total'));
                            @endphp
                            <tr class="summary-highlight">
                                <td colspan="2" class="font-bold text-left pl-4">
                                    CLASS SUMMARY
                                </td>
                                
                                @foreach ($broadsheetData['subjects'] ?? [] as $subjectId => $subjectName)
                                    <td>
                                        <div class="text-sm">{{ $subjectAverages[$subjectId] ?? 0 }}</div>
                                        <div class="text-xs opacity-90">H:{{ $subjectHighest[$subjectId] ?? 0 }} L:{{ $subjectLowest[$subjectId] ?? 0 }}</div>
                                    </td>
                                @endforeach
                                
                                <td>
                                    <div class="text-sm">{{ $classTotal }}</div>
                                    <div class="text-xs opacity-90">H:{{ $highestTotal }} L:{{ $lowestTotal }}</div>
                                </td>
                                <td>
                                    {{ $classAverage }}
                                </td>
                                <td colspan="2" class="text-left pl-4">
                                    Avg: {{ $classAverage >= 70 ? 'Exc' : ($classAverage >= 50 ? 'Good' : 'Low') }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Footer Section -->
            <div class="p-6 border-t border-gray-300">
                <!-- Subject Key -->
                <div class="mb-6">
                    <h4 class="font-bold text-lg text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-book mr-2 text-blue-600"></i>
                        Subject Key (Abbreviations)
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        @foreach ($broadsheetData['subjects'] ?? [] as $subjectId => $subjectName)
                            @php
                                $subjectAbbr = abbreviate($subjectName);
                            @endphp
                            <div class="flex items-center bg-gray-50 p-2 rounded border">
                                <span class="font-bold text-blue-700 mr-2 min-w-12">{{ $subjectAbbr }}</span>
                                <span class="text-sm text-gray-700 truncate" title="{{ $subjectName }}">{{ $subjectName }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Performance Distribution -->
                <div class="mb-8">
                    <h4 class="font-bold text-lg text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-bar mr-2 text-red-600"></i>
                        Performance Distribution
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        @php
                            $gradeDistribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0];
                            foreach ($broadsheetData['students'] ?? [] as $student) {
                                foreach ($student['subjects'] ?? [] as $subject) {
                                    $grade = strtoupper($subject['grade'] ?? '');
                                    if (isset($gradeDistribution[$grade])) {
                                        $gradeDistribution[$grade]++;
                                    }
                                }
                            }
                            $totalGrades = array_sum($gradeDistribution);
                            
                            // Calculate percentages and ensure they sum to exactly 100%
                            $percentages = [];
                            $remainingPercentage = 100;
                            $remainingGrades = count($gradeDistribution);
                            
                            // First pass: calculate raw percentages
                            foreach ($gradeDistribution as $grade => $count) {
                                $percentage = $totalGrades > 0 ? ($count / $totalGrades) * 100 : 0;
                                $percentages[$grade] = $percentage;
                            }
                            
                            // Second pass: round and adjust to ensure sum is exactly 100%
                            $roundedPercentages = [];
                            $roundedSum = 0;
                            
                            // Round each percentage to 1 decimal place
                            foreach ($percentages as $grade => $percentage) {
                                $rounded = round($percentage, 1);
                                $roundedPercentages[$grade] = $rounded;
                                $roundedSum += $rounded;
                            }
                            
                            // Adjust if sum is not 100%
                            if ($roundedSum != 100) {
                                $difference = 100 - $roundedSum;
                                // Find the grade with the largest fractional part to adjust
                                $largestFractionGrade = '';
                                $largestFraction = -1;
                                
                                foreach ($percentages as $grade => $percentage) {
                                    $fraction = $percentage - floor($percentage);
                                    if ($fraction > $largestFraction) {
                                        $largestFraction = $fraction;
                                        $largestFractionGrade = $grade;
                                    }
                                }
                                
                                // Adjust the grade with the largest fractional part
                                if ($largestFractionGrade) {
                                    $roundedPercentages[$largestFractionGrade] += $difference;
                                }
                            }
                        @endphp
                        
                        @foreach($gradeDistribution as $grade => $count)
                            @php
                                $percentage = $roundedPercentages[$grade] ?? 0;
                                $gradeColor = [
                                    'A' => 'bg-green-100 text-green-800 border-green-300',
                                    'B' => 'bg-blue-100 text-blue-800 border-blue-300',
                                    'C' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                                    'D' => 'bg-orange-100 text-orange-800 border-orange-300',
                                    'E' => 'bg-red-100 text-red-800 border-red-300',
                                    'F' => 'bg-red-200 text-red-900 border-red-400'
                                ][$grade];
                            @endphp
                            <div class="border rounded-lg p-3 {{ $gradeColor }}">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-lg">Gr {{ $grade }}</span>
                                    <span class="text-xl font-extrabold">{{ $count }}</span>
                                </div>
                                <div class="mt-2">
                                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full {{ str_contains($gradeColor, 'green') ? 'bg-green-500' : 
                                                              (str_contains($gradeColor, 'blue') ? 'bg-blue-500' : 
                                                              (str_contains($gradeColor, 'yellow') ? 'bg-yellow-500' : 
                                                              (str_contains($gradeColor, 'orange') ? 'bg-orange-500' : 'bg-red-500'))) }}" 
                                             style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <div class="text-xs mt-1">{{ number_format($percentage, 1) }}%</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Statistics and Signatures -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h4 class="font-bold text-lg text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-green-600"></i>
                            Statistics
                        </h4>
                        <ul class="space-y-3">
                            <li class="flex justify-between items-center">
                                <span>Class Avg:</span>
                                <span class="font-bold">{{ $classAverage ?? 0 }}</span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span>Highest:</span>
                                <span class="font-bold text-green-600">{{ $highestTotal ?? 0 }}</span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span>Lowest:</span>
                                <span class="font-bold text-red-600">{{ $lowestTotal ?? 0 }}</span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span>Subjects:</span>
                                <span class="font-bold">{{ count($broadsheetData['subjects'] ?? []) }}</span>
                            </li>
                            <li class="flex justify-between items-center">
                                <span>Pass Rate:</span>
                                @php
                                    $passCount = 0;
                                    foreach ($broadsheetData['students'] ?? [] as $student) {
                                        if ($student['average'] >= 40) $passCount++;
                                    }
                                    $passRate = count($broadsheetData['students'] ?? []) > 0 
                                        ? round(($passCount / count($broadsheetData['students'])) * 100, 1) 
                                        : 0;
                                @endphp
                                <span class="font-bold {{ $passRate >= 70 ? 'text-green-600' : ($passRate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ $passRate }}%
                                </span>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-lg text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-key mr-2 text-purple-600"></i>
                            Grading Scale
                        </h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 rounded bg-green-50">
                                <span class="font-medium">80-100%</span>
                                <span class="performance-badge grade-a">A</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded bg-blue-50">
                                <span class="font-medium">70-79%</span>
                                <span class="performance-badge grade-b">B</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded bg-yellow-50">
                                <span class="font-medium">60-69%</span>
                                <span class="performance-badge grade-c">C</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded bg-orange-50">
                                <span class="font-medium">50-59%</span>
                                <span class="performance-badge grade-d">D</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded bg-red-50">
                                <span class="font-medium">40-49%</span>
                                <span class="performance-badge grade-e">E</span>
                            </div>
                            <div class="flex items-center justify-between p-2 rounded bg-red-100">
                                <span class="font-medium">0-39%</span>
                                <span class="performance-badge grade-f">F</span>
                            </div>
                        </div>
                    </div>
                    
                    
                </div>
                
                <!-- Footer Note -->
                <div class="mt-8 text-center text-sm text-gray-500 border-t border-gray-200 pt-4">
                    <div class="flex flex-wrap justify-center items-center gap-4 mb-2">
                        <span><i class="fas fa-phone-alt mr-1"></i> {{ $schoolDetails['school_phone'] ?? 'Phone: N/A' }}</span>
                        <span>•</span>
                        <span><i class="fas fa-envelope mr-1"></i> {{ $schoolDetails['school_email'] ?? 'Email: info@'.config('app.url') }}</span>
                        @if(isset($schoolDetails['school_website']) && $schoolDetails['school_website'])
                            <span>•</span>
                            <span><i class="fas fa-globe mr-1"></i> {{ $schoolDetails['school_website'] ?? 'Website: '.config('app.url') }}</span>
                        @else
                            <span>•</span>
                            <span><i class="fas fa-globe mr-1"></i> Website: {{ config('app.url') }}</span>
                        @endif
                    </div>
                    <p class="text-xs italic">Computer generated document • Official copy available on request</p>
                    <p class="mt-2 text-xs font-medium">Generated by Paramount Edusoft • {{ date('F j, Y \a\t h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printBroadsheet() {
            window.print();
        }
        
        function exportToExcel() {
            const table = document.querySelector('table');
            const html = table.outerHTML;
            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'broadsheet-{{ $record->name }}-{{ date("Y-m-d") }}.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        // Add hover effects for rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f0f9ff';
                });
                row.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('summary-highlight')) {
                        this.style.backgroundColor = '';
                    }
                });
            });
            
            // Add tooltips for subject headers
            const subjectHeaders = document.querySelectorAll('.subject-tooltip');
            subjectHeaders.forEach(header => {
                header.addEventListener('mouseenter', function(e) {
                    // Tooltip is handled by CSS
                });
            });
        });
    </script>
</body>
</html>