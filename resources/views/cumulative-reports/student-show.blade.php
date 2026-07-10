<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Cumulative Report - {{ $session }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            border-bottom: 2px solid #056b05;
        }

        .table-head th {
            font-weight: 700;
            color: #056b05;
        }

        .percent-row td,
        .percent-row th {
            font-size: 0.75rem;
            font-weight: 500;
            color: #555;
            font-style: italic;
            border-top: none;
            padding-top: 0 !important;
        }

        .annual-col {
            font-weight: 700;
        }

        .student-report-card {
            margin: 0;
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
        }

        .print-remove {
            display: none !important;
        }

        @media print {
            .print-remove {
                display: none !important;
            }

            body * {
                visibility: hidden;
            }

            #printableArea,
            #printableArea * {
                visibility: visible;
            }

            #printableArea {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body class="bg-gray-100 p-4">

    <div class="print-remove" style="background: #003333; color:#fff; text-align:center; padding:10px 0px; margin-bottom:20px; border-radius: 8px;">
        <a href="{{ route('student.cumulative-reports.select') }}" style="color:#fff; text-decoration:none; margin-right:20px;">&larr; Back to selection</a>
        <button onclick="printResult()"
            style="border-radius:10px; border:1px solid #fff; padding:5px 14px; background:transparent; color:white; cursor:pointer;">
            Print / Save as PDF
        </button>
    </div>

    @if (empty($students))
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-600">
            No cumulative results found for you in the <strong>{{ $session }}</strong> session yet.
        </div>
    @else
        @php
            $school_logo = $schoolDetails['school_logo'] ?? null;
            $principal_signature = $schoolDetails['principal_signature'] ?? null;
            $studentId = array_key_first($students);
            $studentData = $students[$studentId];
        @endphp

        <div id="printableArea">
            <div class="student-report-card border p-6 mb-6 rounded-lg shadow-lg" style="margin-top:15px; margin-bottom:15px;">

                {{-- Header --}}
                <div class="mb-4 flex justify-between border p-2">
                    <div class="school_logo">
                        @if ($school_logo)
                            <img src="{{ Storage::url($school_logo) }}" alt="Logo" style="height: 70px; border-radius: 10%;">
                        @endif
                    </div>
                    <div class="text-center">
                        <h2 class="font-bold" style="font-size: 2.2rem;">{{ $schoolDetails['school_name'] ?? '' }}</h2>
                        <p><span style="font-weight: 600; color:darkmagenta;">
                                Annual Cumulative Report &mdash; {{ $session }}
                            </span></p>
                    </div>
                    <div class="student_passport">
                        @if (!empty($studentData['info']->passport))
                            <img src="{{ Storage::url($studentData['info']->passport) }}" alt="Passport" style="height: 70px; border-radius: 10%;">
                        @endif
                    </div>
                </div>

                {{-- Student Info --}}
                <div class="mb-4 flex justify-between border p-2">
                    <div>
                        <h2 class="text-xl font-bold">{{ $studentData['info']->name }}</h2>
                        <p>Email: {{ $studentData['info']->email }}</p>
                        <p><span class="font-semibold">Class:</span> {{ $class->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p><span class="font-semibold">Admission Number:</span>
                            {{ $studentData['info']->student->roll_number ?? 'N/A' }}</p>
                        <p><span class="font-semibold">Parent:</span>
                            {{ $studentData['info']->student->guardian_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p><span class="font-semibold">Terms Covered:</span> {{ implode(', ', $termsPresent) }}</p>
                    </div>
                </div>

                {{-- Cumulative Subjects Table --}}
                @php
                    $simpleTerms = array_filter($allTerms, fn($t) => $t !== '3rd Term');
                    $thirdTermColCount = count($thirdTermHeaders) + 1; // +1 for "3rd Term Summary"
                @endphp
                <table class="w-full border-collapse border border-gray-300 text-left">
                    <thead>
                        <tr class="table-head">
                            <th class="border px-2 py-1" rowspan="3">SUBJECTS</th>
                            @foreach ($simpleTerms as $term)
                                <th class="border px-2 py-1 text-center" rowspan="2">{{ $term }}</th>
                            @endforeach
                            <th class="border px-2 py-1 text-center" colspan="{{ $thirdTermColCount }}">3RD TERM</th>
                            <th class="border px-2 py-1 text-center annual-col" rowspan="2">Annual Summary</th>
                            <th class="border px-2 py-1 text-center annual-col" rowspan="2">Annual Average</th>
                            <th class="border px-2 py-1 text-center" rowspan="3">3rd Term Remark</th>
                        </tr>
                        <tr class="table-head">
                            @foreach ($thirdTermHeaders as $header)
                                <th class="border px-2 py-1 text-center text-xs">{{ $header }}</th>
                            @endforeach
                            <th class="border px-2 py-1 text-center text-xs">3rd Term Summary</th>
                        </tr>
                        <tr class="percent-row">
                            @foreach ($simpleTerms as $term)
                                <th class="border px-2 py-1 text-center">100%</th>
                            @endforeach
                            @foreach ($thirdTermHeaders as $header)
                                <th class="border px-2 py-1 text-center">&nbsp;</th>
                            @endforeach
                            <th class="border px-2 py-1 text-center">100%</th>
                            <th class="border px-2 py-1 text-center">{{ count($allTerms) * 100 }}%</th>
                            <th class="border px-2 py-1 text-center">100%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subjectOrder as $subjectName)
                            @php
                                $subjectData = $studentData['subjects'][$subjectName] ?? null;
                            @endphp
                            <tr>
                                <td class="border px-2 py-1 font-medium">{{ $subjectName }}</td>

                                @foreach ($simpleTerms as $term)
                                    <td class="border px-2 py-1 text-center">
                                        @if (($subjectData['per_term'][$term] ?? null) !== null)
                                            {{ $subjectData['per_term'][$term] }}
                                            <span class="text-xs text-gray-500">({{ $subjectData['per_term_percent'][$term] }}%)</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach

                                @foreach ($thirdTermHeaders as $header)
                                    <td class="border px-2 py-1 text-center">
                                        {{ $subjectData['third_term_scores'][$header] ?? '-' }}
                                    </td>
                                @endforeach

                                <td class="border px-2 py-1 text-center">
                                    @if (($subjectData['per_term']['3rd Term'] ?? null) !== null)
                                        {{ $subjectData['per_term']['3rd Term'] }}
                                        <span class="text-xs text-gray-500">(100%)</span>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="border px-2 py-1 text-center annual-col">
                                    @if (($subjectData['annual_summary'] ?? null) !== null)
                                        {{ $subjectData['annual_summary'] }}
                                        <span class="text-xs text-gray-500">({{ $subjectData['annual_summary_percent'] }}%)</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="border px-2 py-1 text-center annual-col">
                                    {{ $subjectData['annual_average'] ?? '-' }}
                                </td>
                                <td class="border px-2 py-1 text-center">
                                    {{ $subjectData['third_term_remark'] ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-head">
                            <td class="border px-2 py-1">OVERALL</td>
                            @foreach ($simpleTerms as $term)
                                <td class="border px-2 py-1 text-center">
                                    @if (($studentData['overall']['per_term'][$term] ?? null) !== null)
                                        {{ $studentData['overall']['per_term'][$term] }}
                                        <span class="text-xs" style="color:#777;">({{ $studentData['overall']['per_term_percent'][$term] }}%)</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endforeach
                            @if ($thirdTermColCount - 1 > 0)
                                <td class="border px-2 py-1 text-center" colspan="{{ $thirdTermColCount - 1 }}"></td>
                            @endif
                            <td class="border px-2 py-1 text-center">
                                @if (($studentData['overall']['per_term']['3rd Term'] ?? null) !== null)
                                    {{ $studentData['overall']['per_term']['3rd Term'] }}
                                    <span class="text-xs" style="color:#777;">(100%)</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border px-2 py-1 text-center annual-col" style="color:#056b05;">
                                @if (($studentData['overall']['annual_summary'] ?? null) !== null)
                                    {{ $studentData['overall']['annual_summary'] }}
                                    <span class="text-xs" style="color:#777;">({{ $studentData['overall']['annual_summary_percent'] }}%)</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border px-2 py-1 text-center annual-col" style="color:#056b05;">
                                {{ $studentData['overall']['annual_average'] ?? '-' }}
                            </td>
                            <td class="border px-2 py-1 text-center"></td>
                        </tr>
                        <tr class="table-head">
                            <td class="border px-2 py-1" colspan="{{ 1 + count($simpleTerms) + $thirdTermColCount }}" style="text-align:right;">
                                OVERALL AVERAGE
                                <span class="text-xs" style="color:#777;">
                                    ({{ $studentData['overall']['annual_summary'] ?? 0 }} &times; 100 / {{ $studentData['overall']['total_obtainable'] ?? 0 }})
                                </span>
                            </td>
                            <td class="border px-2 py-1 text-center" colspan="2" style="color:#056b05;">
                                {{ isset($studentData['overall']['overall_average_percent']) ? $studentData['overall']['overall_average_percent'] . '%' : '-' }}
                            </td>
                            <td class="border px-2 py-1 text-center"></td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Remarks (3rd Term) --}}
                <div class="teacher_comment">
                    <br>
                    <table class="w-full">
                        <thead>
                            <tr class="table-head">
                                <th style="text-align:center;" colspan="2">3rd Term Remarks/Comments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Class Teacher's Remark</td>
                                <td class="border px-2 py-1">
                                    {{ $teacherRemarks[$studentId]->remark ?? 'No remark added yet by class teacher' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="font-weight:600; width: 30%;" class="border px-2 py-1">Proprietor's Remark</td>
                                <td class="border px-2 py-1">
                                    {{ $hosRemarks[$studentId]->remark ?? 'No remark added yet' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- 3rd Term Skills & Behaviours --}}
                    @if (!empty($studentData['third_term_skills']) || !empty($studentData['third_term_behaviours']))
                        <div style="margin-top: 30px;">
                            <h3 style="text-align:center; font-weight:bold; font-size:1.1rem; margin-bottom:10px;">
                                3RD TERM SKILLS AND BEHAVIOURS
                            </h3>
                            <div style="display:flex; justify-content:space-between; gap:30px;">
                                <table class="border-collapse border border-gray-400 text-center w-1/2">
                                    <thead>
                                        <tr>
                                            <th class="border px-2 py-1 text-left">SKILLS (1-5)</th>
                                            @for ($i = 5; $i >= 1; $i--)
                                                <th class="border px-2 py-1">{{ $i }}</th>
                                            @endfor
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($studentData['third_term_skills'] as $s)
                                            <tr>
                                                <td class="border px-2 py-1 text-left">{{ $s->category->name }}</td>
                                                @for ($i = 5; $i >= 1; $i--)
                                                    <td class="border px-2 py-1">
                                                        @if ($s->score == $i)
                                                            &#10003;
                                                        @endif
                                                    </td>
                                                @endfor
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <table class="border-collapse border border-gray-400 text-center w-1/2">
                                    <thead>
                                        <tr>
                                            <th class="border px-2 py-1 text-left">BEHAVIOURS (1-5)</th>
                                            @for ($i = 5; $i >= 1; $i--)
                                                <th class="border px-2 py-1">{{ $i }}</th>
                                            @endfor
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($studentData['third_term_behaviours'] as $b)
                                            <tr>
                                                <td class="border px-2 py-1 text-left">{{ $b->category->name }}</td>
                                                @for ($i = 5; $i >= 1; $i--)
                                                    <td class="border px-2 py-1">
                                                        @if ($b->score == $i)
                                                            &#10003;
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
                            <td style="padding:15px;">
                                {{ $thirdTermRoot->teacher->name ?? '' }}
                                <br>
                                <b><cite>Class Teacher</cite></b>
                            </td>
                            <td style="padding:15px;">
                                @if ($principal_signature)
                                    <img src="{{ Storage::url($principal_signature) }}" alt="signature" style="height: 50px;">
                                @endif
                                {{ $schoolDetails['principal_name'] ?? '' }}
                                <br>
                                <b><cite>Principal</cite></b>
                            </td>
                        </tr>
                    </table>
                    <br><br>
                    <center><cite>&copy; {{ date('Y') }} Powered by Paramount Edusoft</cite></center>
                </div>
            </div>
        </div>
    @endif

    <script>
        function printResult() {
            const printContent = document.getElementById('printableArea').innerHTML;
            const originalContent = document.body.innerHTML;

            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
            window.location.reload();
        }
    </script>

</body>

</html>