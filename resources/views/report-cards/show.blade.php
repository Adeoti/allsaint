@php
    use App\Models\SchoolClass;
    use App\Models\Subject;
    use App\Models\User;
    use App\Models\Attendance;
    use App\Models\GradingSystem;
    use App\Models\StudentSkillBehaviour;
@endphp

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Report Cards - {{ $record->name }}</title>
    <!-- Tailwind CSS (for styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Additional custom styles -->
    <style>
        div.key-to-grades {
            background-color: #f4fa9c;
            text-align: center;
            padding: 10px;
        }

        table {
            margin-top: 20px;
        }

        .table-head {
            background-color: rgb(5, 107, 5) !important;
            color: #fff;
            width: 100% !important;
        }

        .hidden {
            display: none;
        }

        .existing-remark {
            padding: 8px;
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .existing-remark span {
            color: #2d3748;
        }

        #remark-error {
            color: #e53e3e;
            font-size: 0.875rem;
            margin-top: 4px;
        }

        #remark-success {
            color: #38a169;
            font-size: 0.875rem;
            margin-top: 4px;
        }

        /* Print styles – each card starts on a new page */
        .student-report-card {
            page-break-before: always;
            page-break-inside: auto;
            margin: 0;
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .student-report-card:first-child {
            page-break-before: auto;
        }

        /* Hide interactive elements when printing */
        .print-remove {
            display: none !important;
        }

        /* Static text replacement for inputs */
        .print-static-text {
            display: inline-block;
            padding: 0.5rem;
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.25rem;
            width: 100%;
            min-height: 42px;
        }
    </style>
</head>

<body class="bg-gray-100 p-4">

    @if ($resultUploads->isEmpty())
        <p>No results uploaded for this result root.</p>
    @else
        {{-- Print button --}}
        <div style="background: #003333; color:#fff; text-align:center; padding:10px 0px; margin-bottom:20px;">
            <button onclick="printAllReportCards()"
                style="border-radius:10px; border:1px solid #fff; padding:5px 10px; background:transparent; color:white; cursor:pointer;">
                Print / Save as PDF
            </button>
            <span id="print-loading" style="display:none; margin-left:10px;">Preparing print...</span>
        </div>

        @php

            $resultsByClass = [];
            $classNames = [];

            foreach ($resultUploads as $resultUpload) {
                $class = SchoolClass::find($resultUpload->class_id);
                $className = $class->name ?? 'Unknown Class';
                $resultsByClass[$resultUpload->class_id][] = $resultUpload;
                $classNames[$resultUpload->class_id] = $className;
            }
        @endphp

        {{-- Tabs for each class --}}
        <div class="tabs">
            <ul class="flex mb-4 border-b">
                @foreach ($classNames as $classId => $className)
                    <li class="mr-2">
                        <button class="tab-toggle px-4 py-2 rounded-t-lg bg-gray-200 hover:bg-gray-300"
                            data-tab="tab-{{ $classId }}">{{ $className }}</button>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Tab Content for each class --}}
        @foreach ($resultsByClass as $classId => $classResults)
            <div class="tab-content hidden" id="tab-{{ $classId }}">
                <h2 class="text-2xl font-semibold mb-4">{{ $classNames[$classId] }}</h2>

                @php
                    $students = [];
                    $dynamicHeaders = [];

                    // Helper function for ordinal suffix
                    if (!function_exists('ordinal_suffix')) {
                        function ordinal_suffix($number)
                        {
                            $suffixes = ['th', 'st', 'nd', 'rd'];
                            $value = $number % 100;
                            return $number . ($suffixes[($value - 20) % 10] ?? ($suffixes[$value] ?? $suffixes[0]));
                        }
                    }

                    foreach ($classResults as $resultUpload) {
                        $subject = Subject::find($resultUpload->subject_id);
                        $cardItems = is_array($resultUpload->card_items)
                            ? $resultUpload->card_items
                            : json_decode($resultUpload->card_items, true);

                        foreach ($cardItems as $studentId => $result) {
                            $student = User::find($studentId);
                            if ($student) {
                                $students[$studentId]['info'] = $student;
                                $students[$studentId]['subjects'][] = [
                                    'name' => $subject->name ?? 'No Subject',
                                    'scores' => $result['scores'] ?? [],
                                    'total' => $result['total'] ?? 'N/A',
                                    'average' => $result['average'] ?? 'N/A',
                                    'highest' => $result['highest'] ?? 'N/A',
                                    'lowest' => $result['lowest'] ?? 'N/A',
                                    'grade' => $result['grade'] ?? 'N/A',
                                    'remark' => $result['remark'] ?? 'N/A',
                                ];

                                // Collect dynamic headers from scores keys
                                $dynamicHeaders = array_unique(
                                    array_merge($dynamicHeaders, array_keys($result['scores'] ?? [])),
                                );
                            }
                        }
                    }

                    // Prepare total scores for ranking
                    $studentsWithScores = [];
                    foreach ($students as $studentId => $studentData) {
                        $totalScore = array_sum(array_column($studentData['subjects'], 'total'));
                        $studentsWithScores[$studentId] = $totalScore;
                    }
                    arsort($studentsWithScores);
                    $positions = [];
                    $rank = 1;
                    foreach ($studentsWithScores as $studentId => $score) {
                        $positions[$studentId] = ordinal_suffix($rank++);
                    }

                    $school_logo = $schoolDetails['school_logo'];
                    $principal_signature = $schoolDetails['principal_signature'];
                @endphp

                {{-- Render cards for each student --}}
                @foreach ($students as $studentId => $studentData)
                    {{-- Added class "student-report-card" for print selection --}}
                    <div class="student-report-card border p-6 mb-6 rounded-lg shadow-lg"
                        style="margin-top:15px; margin-bottom:15px;">
                        {{-- Header with logos --}}
                        <div class="mb-4 flex justify-between border p-2">
                            <div class="school_logo">

                                <img src="{{ Storage::url($school_logo) }}" alt="Logo" class="logo-img"
                                    style="height: 70px; border-radius: 10%;">
                            </div>
                            <div class="text-center">
                                <h2 class="font-bold" style="font-size: 2.7rem;">{{ $schoolDetails['school_name'] }}
                                </h2>
                                <p class="detail-item"><span class="bold"
                                        style="font-weight: 600; color:darkmagenta;">{{ $record->name }}</span></p>
                            </div>
                            <div class="student_passport">
                                <img src="{{ Storage::url($studentData['info']->passport) }}" alt="Passport"
                                    class="logo-img" style="height: 70px; border-radius: 10%;">

                            </div>
                        </div>

                        {{-- Student Info --}}
                        <div class="mb-4 flex justify-between border p-2">
                            <div>
                                <h2 class="text-xl font-bold">{{ $studentData['info']->name }}</h2>
                                <p>Email: {{ $studentData['info']->email }}</p>
                                {{-- <p>Attendance:
                                    {{ Attendance::where('result_root_id', $record->id)->where('student_id', $studentData['info']->id)->count() }}
                                </p> --}}
                                <p class="contact-item"><span class="bold">Class:</span> {{ $class->name ?? 'N/A' }}
                                </p>
                            </div>

                            @php
                                $student = User::find($studentData['info']->id);
                                if ($student && $student->student_class) {
                                    $number_in_class = User::whereHas('student', function ($query) use ($student) {
                                        $query->where('student_class', $student->student_class);
                                    })->count();
                                } else {
                                    $number_in_class = '';
                                }
                            @endphp

                            <div class="details-column">
                                <p class="detail-item"><span class="bold">Admission Number:</span>
                                    {{ $student->student->roll_number ?? 'N/A' }}</p>
                                <p class="detail-item"><span class="bold">Parent:</span>
                                    {{ $student->student->guardian_name ?? 'N/A' }}</p>
                                {{-- <p>Times present:
                                    {{ Attendance::where('status', 'Present')->where('result_root_id', $record->id)->where('student_id', $studentData['info']->id)->count() }}
                                </p> --}}
                            </div>

                            <div class="contact-column">
                                <p class="contact-item"><span class="bold">Number In Class:</span>
                                    {{ $number_in_class ?? 'N/A' }}</p>
                                <p class="contact-item">
                                    <span class="bold">Next Term Begins:</span>
                                    {{ $record->next_term ? \Carbon\Carbon::parse($record->next_term)->format('M j, Y') : 'N/A' }}
                                </p>
                            </div>
                        </div>

                        {{-- Subjects Table --}}
                        <table class="w-full border-collapse border border-gray-300 text-left">
                            <thead class="bg-gray-200">
                                <tr class="table-head">
                                    <th class="border px-2 py-1">SUBJECT</th>
                                    @foreach ($dynamicHeaders as $header)
                                        <th class="border px-2 py-1">{{ $header }}</th>
                                    @endforeach
                                    <th class="border px-2 py-1">TOTAL</th>
                                    <th class="border px-2 py-1">AVERAGE</th>
                                    <th class="border px-2 py-1">HIGHEST</th>
                                    <th class="border px-2 py-1">LOWEST</th>
                                    <th class="border px-2 py-1">GRADE</th>
                                    <th class="border px-2 py-1">REMARK</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentData['subjects'] as $subject)
                                    @php
                                        $allZero = true;

                                        foreach ($dynamicHeaders as $header) {
                                            $value = $subject['scores'][$header] ?? null;

                                            // Normalize value (this is the key fix)
                                            $normalized = trim((string) $value);

                                            if ($normalized !== '' && (float) $normalized != 0) {
                                                $allZero = false;
                                                break;
                                            }
                                        }
                                    @endphp

                                    <tr>
                                        <td class="border px-2 py-1">{{ $subject['name'] }}</td>

                                        @foreach ($dynamicHeaders as $header)
                                            <td class="border px-2 py-1">
                                                {{ $allZero ? '——' : $subject['scores'][$header] ?? 'N/A' }}
                                            </td>
                                        @endforeach

                                        <td class="border px-2 py-1">
                                            {{ $allZero ? '——' : $subject['total'] }}
                                        </td>

                                        <td class="border px-2 py-1">
                                            {{ $allZero ? '——' : number_format($subject['average'], 2) }}
                                        </td>

                                        <td class="border px-2 py-1">
                                            {{ $allZero ? '——' : $subject['highest'] }}
                                        </td>

                                        <td class="border px-2 py-1">
                                            {{ $allZero ? '——' : $subject['lowest'] }}
                                        </td>

                                        <td class="border px-2 py-1">
                                            {{ $allZero ? '——' : $subject['grade'] }}
                                        </td>

                                        <td class="border px-2 py-1">
                                            {{ $allZero ? '——' : $subject['remark'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="teacher_comment">
                            <br>
                            <hr><br>

                            {{-- Key to grades --}}
                            <div class="key-to-grades w-full">
                                @php
                                    $grade_systems = GradingSystem::find($record->grading_system_id);
                                    $grading_system = $grade_systems->grading_system;

                                    $usb = StudentSkillBehaviour::with('scores.category')
                                        ->where('student_id', $studentId)
                                        ->whereHas('skillBehaviour', function ($q) use ($record) {
                                            $q->where('result_root_id', $record->id);
                                        })
                                        ->first();

                                    $skills = [];
                                    $behaviours = [];

                                    if ($usb) {
                                        foreach ($usb->scores as $score) {
                                            if ($score->category->type === 'skill') {
                                                $skills[] = $score;
                                            } elseif ($score->category->type === 'behavior') {
                                                $behaviours[] = $score;
                                            }
                                        }
                                    }
                                @endphp
                                <strong>Key to Grades:</strong>
                                @foreach ($grading_system as $grade)
                                    {{ $grade['min_score'] }} - {{ $grade['max_score'] }} = {{ $grade['grade'] }}
                                    @if (!$loop->last)
                                        ||
                                    @endif
                                @endforeach
                            </div>

                            {{-- Remarks Table --}}
                            <table class="w-full">
                                <thead>
                                    <tr class="table-head">
                                        <th style="text-align:center;" colspan="2">Remarks/Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Total Score
                                        </td>
                                        <td>{{ array_sum(array_column($studentData['subjects'], 'total')) }}</td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Average</td>
                                        <td>{{ number_format(array_sum(array_column($studentData['subjects'], 'total')) / count($studentData['subjects']), 2) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Class
                                            Teacher's Remark</td>
                                        <td>
                                            <div id="teacher-remark-container-{{ $studentId }}">
                                                @php
                                                    $existingRemark = $teacherRemarks[$studentId]->remark ?? null;
                                                @endphp

                                                @if ($existingRemark)
                                                    <div class="existing-remark"
                                                        id="existing-remark-{{ $studentId }}">
                                                        <span>{{ $existingRemark }}</span>
                                                        <button type="button"
                                                            onclick="editRemark({{ $studentId }})"
                                                            class="ml-2 text-blue-600 hover:text-blue-800 text-sm">
                                                            Edit
                                                        </button>
                                                    </div>
                                                    <div id="edit-remark-form-{{ $studentId }}" class="hidden">
                                                        <input type="text" id="remark-input-{{ $studentId }}"
                                                            class="p-2 w-full rounded border border-gray-300"
                                                            value="{{ $existingRemark }}"
                                                            placeholder="Enter your remark for {{ $studentData['info']->name }}"
                                                            onblur="saveRemark({{ $studentId }}, {{ $record->id }}, this.value)">
                                                        <div id="remark-error-{{ $studentId }}"
                                                            class="text-red-500 text-sm mt-1"></div>
                                                        <div id="remark-success-{{ $studentId }}"
                                                            class="text-green-500 text-sm mt-1"></div>
                                                    </div>
                                                @else
                                                    <input type="text" id="remark-input-{{ $studentId }}"
                                                        class="p-2 w-full rounded border border-gray-300"
                                                        placeholder="Enter your remark for {{ $studentData['info']->name }}"
                                                        onblur="saveRemark({{ $studentId }}, {{ $record->id }}, this.value)">
                                                    <div id="remark-error-{{ $studentId }}"
                                                        class="text-red-500 text-sm mt-1"></div>
                                                    <div id="remark-success-{{ $studentId }}"
                                                        class="text-green-500 text-sm mt-1"></div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-weight:600; width: 30%;" class="border px-2 py-1">HOS's
                                            Remarks</td>
                                        <td>
                                            <div id="hos-remark-container-{{ $studentId }}">
                                                @php
                                                    $existingHOSRemark = $hosRemarks[$studentId]->remark ?? null;
                                                @endphp

                                                @if ($existingHOSRemark)
                                                    <div class="existing-remark"
                                                        id="existing-hos-remark-{{ $studentId }}">
                                                        <span>{{ $existingHOSRemark }}</span>
                                                        <button type="button"
                                                            onclick="editHOSRemark({{ $studentId }})"
                                                            class="ml-2 text-blue-600 hover:text-blue-800 text-sm ">
                                                            Edit
                                                        </button>
                                                    </div>
                                                    <div id="edit-hos-remark-form-{{ $studentId }}"
                                                        class="hidden">
                                                        <input type="text"
                                                            id="hos-remark-input-{{ $studentId }}"
                                                            class="p-2 w-full rounded border border-gray-300"
                                                            value="{{ $existingHOSRemark }}"
                                                            placeholder="Enter HOS remark for {{ $studentData['info']->name }}"
                                                            onblur="saveHOSRemark({{ $studentId }}, {{ $record->id }}, this.value)">
                                                        <div id="hos-remark-error-{{ $studentId }}"
                                                            class="text-red-500 text-sm mt-1"></div>
                                                        <div id="hos-remark-success-{{ $studentId }}"
                                                            class="text-green-500 text-sm mt-1"></div>
                                                    </div>
                                                @else
                                                    @php
                                                        $overallAverage = round(
                                                            array_sum(array_column($studentData['subjects'], 'total')) /
                                                                count($studentData['subjects']),
                                                            2,
                                                        );
                                                        $presentCount = Attendance::where('status', 'Present')
                                                            ->where('result_root_id', $record->id)
                                                            ->where('student_id', $studentData['info']->id)
                                                            ->count();
                                                        $totalDays = $record->total_school_days ?? 120;
                                                        $attendancePercentage =
                                                            $totalDays > 0
                                                                ? round(($presentCount / $totalDays) * 100, 2)
                                                                : 0;

                                                        if ($overallAverage >= 90) {
                                                            $comments = [
                                                                'An outstanding performance. Keep maintaining this high academic standard.',
                                                                'Excellent result! You have shown great commitment and hard work. Well done.',
                                                                'A brilliant performance. Continue to remain focused and disciplined.',
                                                                'Exceptional progress. Keep up the excellent attitude towards learning.',
                                                            ];
                                                            $defaultComment = $comments[array_rand($comments)];
                                                        } elseif ($overallAverage >= 80) {
                                                            $comments = [
                                                                'A very good result. With a little more effort, you will reach the top.',
                                                                'Strong performance. Keep putting in your best.',
                                                                'You worked hard this term. Maintain this good effort.',
                                                                'A commendable performance. Continue improving.',
                                                            ];
                                                            $defaultComment = $comments[array_rand($comments)];
                                                        } elseif ($overallAverage >= 70) {
                                                            $comments = [
                                                                'A good performance. You can do even better with more consistency.',
                                                                'You tried well. Aim for higher achievement next term.',
                                                                'Your work is good, but there is room for improvement.',
                                                                'Keep improving your study habits for better results.',
                                                            ];
                                                            $defaultComment = $comments[array_rand($comments)];
                                                        } elseif ($overallAverage >= 60) {
                                                            $comments = [
                                                                'An average performance. You need to work harder next term.',
                                                                'Fair performance. Focus more during lessons to improve.',
                                                                'You have potential; put in more effort to achieve better results.',
                                                                'Encouraged to work harder. Improvement is needed.',
                                                            ];
                                                            $defaultComment = $comments[array_rand($comments)];
                                                        } elseif ($overallAverage >= 50) {
                                                            $comments = [
                                                                'Below expected performance. Greater effort and concentration are needed.',
                                                                'Needs improvement. Encourage more seriousness with studies.',
                                                                'Work harder to avoid falling behind.',
                                                                'Performance is weak; more dedication is required.',
                                                            ];
                                                            $defaultComment = $comments[array_rand($comments)];
                                                        } else {
                                                            $comments = [
                                                                'Performance is poor. The pupil must work much harder next term.',
                                                                'A weak result. Encourage extra support and more study time.',
                                                                'Much improvement is needed across all subjects.',
                                                                'The performance is far below expectation. Serious effort is required.',
                                                            ];
                                                            $defaultComment = $comments[array_rand($comments)];
                                                        }
                                                    @endphp

                                                    <div class="existing-remark"
                                                        id="existing-hos-remark-{{ $studentId }}"
                                                        style="display: none;">
                                                        <span></span>
                                                        <button type="button"
                                                            onclick="editHOSRemark({{ $studentId }})"
                                                            class="ml-2 text-blue-600 hover:text-blue-800 text-sm ">
                                                            Edit
                                                        </button>
                                                    </div>
                                                    <div id="edit-hos-remark-form-{{ $studentId }}">
                                                        <input type="text"
                                                            id="hos-remark-input-{{ $studentId }}"
                                                            class="p-2 w-full rounded border border-gray-300"
                                                            value="{{ $defaultComment }}"
                                                            placeholder="Enter HOS remark for {{ $studentData['info']->name }}"
                                                            onblur="saveHOSRemark({{ $studentId }}, {{ $record->id }}, this.value)">
                                                        <div id="hos-remark-error-{{ $studentId }}"
                                                            class="text-red-500 text-sm mt-1"></div>
                                                        <div id="hos-remark-success-{{ $studentId }}"
                                                            class="text-green-500 text-sm mt-1"></div>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            {{-- Skills and Behaviours Section --}}
                            @if ($usb)
                                <div style="margin-top: 40px;">
                                    <h3
                                        style="text-align:center; font-weight:bold; font-size:1.2rem; margin-bottom:10px;">
                                        SKILLS AND BEHAVIOURS
                                    </h3>

                                    <div style="display:flex; justify-content:space-between; gap:30px;">
                                        <table class="border-collapse border border-gray-400 text-center w-1/2">
                                            <thead style="background:#f0f0f0;">
                                                <tr>
                                                    <th class="border px-2 py-1 text-left">SKILLS (1-5)</th>
                                                    @for ($i = 5; $i >= 1; $i--)
                                                        <th class="border px-2 py-1">{{ $i }}</th>
                                                    @endfor
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($skills as $s)
                                                    <tr>
                                                        <td class="border px-2 py-1 text-left">
                                                            {{ $s->category->name }}</td>
                                                        @for ($i = 5; $i >= 1; $i--)
                                                            <td class="border px-2 py-1">
                                                                @if ($s->score == $i)
                                                                    ✔
                                                                @endif
                                                            </td>
                                                        @endfor
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>

                                        <table class="border-collapse border border-gray-400 text-center w-1/2">
                                            <thead style="background:#f0f0f0;">
                                                <tr>
                                                    <th class="border px-2 py-1 text-left">BEHAVIOURS (1-5)</th>
                                                    @for ($i = 5; $i >= 1; $i--)
                                                        <th class="border px-2 py-1">{{ $i }}</th>
                                                    @endfor
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($behaviours as $b)
                                                    <tr>
                                                        <td class="border px-2 py-1 text-left">
                                                            {{ $b->category->name }}</td>
                                                        @for ($i = 5; $i >= 1; $i--)
                                                            <td class="border px-2 py-1">
                                                                @if ($b->score == $i)
                                                                    ✔
                                                                @endif
                                                            </td>
                                                        @endfor
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <table style="width: 100%; margin-top:30px;">
                                <tr>
                                    <td colspan="12" style="padding:15px;">
                                        <div>
                                            {{ $record->teacher->name ?? '' }}
                                            <br>
                                            <b><cite>Class Teacher</cite></b>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="padding:15px;">
                                            {{-- Principal signature image --}}
                                            <img src="{{ Storage::url($principal_signature) }}" alt="signature"
                                                class="logo-img" style="height: 50px;">
                                            {{ $schoolDetails['principal_name'] }}
                                            <br>
                                            <b><cite>HOS</cite></b>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <br>
                            <hr><br>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif

    <!-- Scripts -->
    <script>
        // Tab switching
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-toggle');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    contents.forEach(content => content.classList.add('hidden'));
                    tabs.forEach(t => t.classList.remove('bg-gray-300'));

                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.remove('hidden');
                    this.classList.add('bg-gray-300');
                });
            });

            if (tabs.length > 0) {
                tabs[0].click();
            }
        });

        // Teacher remark functions
        async function saveRemark(studentId, resultRootId, remark) {
            const errorDiv = document.getElementById(`remark-error-${studentId}`);
            const successDiv = document.getElementById(`remark-success-${studentId}`);
            const input = document.getElementById(`remark-input-${studentId}`);

            errorDiv.textContent = '';
            successDiv.textContent = '';
            input.disabled = true;

            try {
                const response = await fetch('{{ route('teacher-remark.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        result_root_id: resultRootId,
                        remark: remark
                    })
                });

                const data = await response.json();

                if (data.success) {
                    successDiv.textContent = 'Remark saved successfully!';
                    if (!document.getElementById(`existing-remark-${studentId}`)) {
                        const container = document.getElementById(`teacher-remark-container-${studentId}`);
                        container.innerHTML = `
                            <div class="existing-remark" id="existing-remark-${studentId}">
                                <span>${remark}</span>
                                <button type="button" onclick="editRemark(${studentId})" class="ml-2 text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                            </div>
                            <div id="edit-remark-form-${studentId}" class="hidden">
                                <input type="text" id="remark-input-${studentId}" class="p-2 w-full rounded border border-gray-300" value="${remark}" placeholder="Enter your remark" onblur="saveRemark(${studentId}, ${resultRootId}, this.value)">
                                <div id="remark-error-${studentId}" class="text-red-500 text-sm mt-1"></div>
                                <div id="remark-success-${studentId}" class="text-green-500 text-sm mt-1"></div>
                            </div>
                        `;
                    }
                } else {
                    errorDiv.textContent = data.message;
                }
            } catch (error) {
                errorDiv.textContent = 'Failed to save remark. Please try again.';
                console.error('Error saving remark:', error);
            } finally {
                input.disabled = false;
            }
        }

        function editRemark(studentId) {
            const existingRemarkDiv = document.getElementById(`existing-remark-${studentId}`);
            const editFormDiv = document.getElementById(`edit-remark-form-${studentId}`);

            if (existingRemarkDiv && editFormDiv) {
                existingRemarkDiv.classList.add('hidden');
                editFormDiv.classList.remove('hidden');
                const input = document.getElementById(`remark-input-${studentId}`);
                if (input) input.focus();
            }
        }

        // HOS remark functions
        async function saveHOSRemark(studentId, resultRootId, remark) {
            const errorDiv = document.getElementById(`hos-remark-error-${studentId}`);
            const successDiv = document.getElementById(`hos-remark-success-${studentId}`);
            const input = document.getElementById(`hos-remark-input-${studentId}`);

            errorDiv.textContent = '';
            successDiv.textContent = '';
            input.disabled = true;

            try {
                const response = await fetch('{{ route('hos-remark.save') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        result_root_id: resultRootId,
                        remark: remark
                    })
                });

                const data = await response.json();

                if (data.success) {
                    successDiv.textContent = 'HOS remark saved successfully!';
                    const existingDiv = document.getElementById(`existing-hos-remark-${studentId}`);
                    const span = existingDiv.querySelector('span');
                    if (span) span.textContent = remark;
                    existingDiv.style.display = 'block';
                } else {
                    errorDiv.textContent = data.message;
                }
            } catch (error) {
                errorDiv.textContent = 'Failed to save HOS remark. Please try again.';
                console.error('Error saving HOS remark:', error);
            } finally {
                input.disabled = false;
            }
        }

        function editHOSRemark(studentId) {
            const existingRemarkDiv = document.getElementById(`existing-hos-remark-${studentId}`);
            const editFormDiv = document.getElementById(`edit-hos-remark-form-${studentId}`);

            if (existingRemarkDiv && editFormDiv) {
                existingRemarkDiv.classList.add('hidden');
                editFormDiv.classList.remove('hidden');
                const input = document.getElementById(`hos-remark-input-${studentId}`);
                if (input) {
                    input.focus();
                    input.select();
                }
            }
        }

        // ========== PRINT FUNCTION ==========
        async function printAllReportCards() {
            // Show loading indicator
            const loadingSpan = document.getElementById('print-loading');
            if (loadingSpan) loadingSpan.style.display = 'inline';

            try {
                // Open a new blank window
                const printWindow = window.open('', '_blank');

                // Collect all style and link tags from the current document
                const styles = document.querySelectorAll('style, link[rel="stylesheet"]');
                let stylesHTML = '';
                styles.forEach(el => {
                    if (el.tagName === 'STYLE') {
                        stylesHTML += el.outerHTML;
                    } else if (el.tagName === 'LINK') {
                        stylesHTML += el.outerHTML;
                    }
                });

                // Add print-specific CSS to force page breaks
                const printCSS = `
                    <style>
                        .student-report-card {
                            page-break-before: always;
                            page-break-inside: auto;
                            margin: 0;
                            padding: 1.5rem;
                            border: 1px solid #ddd;
                            border-radius: 0.5rem;
                            background: white;
                        }
                        .student-report-card:first-child {
                            page-break-before: auto;
                        }
                        /* Hide interactive elements */
                        .print-remove, button, input, [id^="remark-error-"], [id^="remark-success-"], [id^="edit-remark-form-"], [id^="edit-hos-remark-form-"] {
                            display: none !important;
                        }
                        /* Convert existing remarks to visible blocks */
                        .existing-remark {
                            display: block !important;
                        }
                        /* Style for static text if any */
                        .print-static-text {
                            display: inline-block;
                            padding: 0.5rem;
                            background-color: #f7fafc;
                            border: 1px solid #e2e8f0;
                            border-radius: 0.25rem;
                            width: 100%;
                        }
                        /* Ensure images print */
                        img {
                            max-width: 100%;
                            height: auto;
                        }
                    </style>
                `;

                // Start building the new document
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Report Cards - {{ $record->name }}</title>
                        ${stylesHTML}
                        ${printCSS}
                    </head>
                    <body style="background: white; padding: 0; margin: 0;">
                `);

                // Select all student cards
                const cards = document.querySelectorAll('.student-report-card');
                console.log('Cards for print:', cards.length);

                if (cards.length === 0) {
                    alert('No student cards found.');
                    printWindow.close();
                    return;
                }

                // Clone and clean each card, then write to the new window
                cards.forEach((card, index) => {
                    const clone = card.cloneNode(true);

                    // Remove all interactive elements (buttons, inputs, error messages, edit forms)
                    clone.querySelectorAll(
                            '.print-remove, button, input, [id^="remark-error-"], [id^="remark-success-"], [id^="hos-remark-error-"], [id^="hos-remark-success-"], [id^="edit-remark-form-"], [id^="edit-hos-remark-form-"]'
                        )
                        .forEach(el => el.remove());

                    // Ensure existing remark spans are visible
                    clone.querySelectorAll('[id^="existing-remark-"], [id^="existing-hos-remark-"]').forEach(
                        el => {
                            el.style.display = 'block';
                            el.classList.remove('hidden');
                        });

                    // Write the cleaned card HTML
                    printWindow.document.write(clone.outerHTML);
                });

                printWindow.document.write('</body></html>');
                printWindow.document.close();

                // Wait for images and styles to load
                await new Promise(resolve => {
                    printWindow.onload = resolve;
                    // Fallback timeout
                    setTimeout(resolve, 2000);
                });

                // Trigger print dialog
                printWindow.focus();
                printWindow.print();

            } catch (error) {
                console.error('Print preparation failed:', error);
                alert('Failed to prepare print. Check console for details.');
            } finally {
                if (loadingSpan) loadingSpan.style.display = 'none';
            }
        }
    </script>

</body>

</html>
