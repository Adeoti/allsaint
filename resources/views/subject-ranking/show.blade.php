<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject->name }} Ranking - {{ $class->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            @page { margin: 0.5in; }
        }
        .rank-1 { background-color: #fef9c3; }
        .rank-2 { background-color: #f3f4f6; }
        .rank-3 { background-color: #fed7aa; }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex gap-3 no-print">
            <button onclick="window.print()"
                class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-500">
                Print
            </button>
            <a href="{{ route('subject-ranking.download.pdf', request()->query()) }}"
                target="_blank"
                class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-500">
                Download PDF
            </a>
            <a href="{{ route('subject-ranking.download.csv', request()->query()) }}"
                target="_blank"
                class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-500">
                Download CSV
            </a>
            <div class="flex-1"></div>
            <a href="{{ route('subject-ranking.select') }}"
                class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-semibold hover:bg-gray-500">
                New Search
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">{{ $schoolDetails['school_name'] ?? 'School Name' }}</h1>
                <p class="text-gray-500 text-sm">{{ $schoolDetails['school_address'] ?? '' }}</p>
                <h2 class="text-xl font-semibold mt-3">{{ $subject->name }} — Ranking</h2>
                <p class="text-gray-600 text-sm">
                    {{ $class->name }} &bull; {{ $resultRoot->term }} &bull; {{ $resultRoot->academic_session }}
                </p>
            </div>

            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border px-3 py-2">Position</th>
                        <th class="border px-3 py-2 text-left">Student Name</th>
                        <th class="border px-3 py-2">Total</th>
                        <th class="border px-3 py-2">Average</th>
                        <th class="border px-3 py-2">Grade</th>
                        <th class="border px-3 py-2">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $row)
                        <tr class="{{ $row['rank'] == 1 ? 'rank-1' : ($row['rank'] == 2 ? 'rank-2' : ($row['rank'] == 3 ? 'rank-3' : '')) }}">
                            <td class="border px-3 py-2 text-center font-bold">{{ $row['rank'] }}</td>
                            <td class="border px-3 py-2 font-medium">{{ $row['name'] }}</td>
                            <td class="border px-3 py-2 text-center">{{ $row['total'] }}</td>
                            <td class="border px-3 py-2 text-center">{{ $row['average'] }}</td>
                            <td class="border px-3 py-2 text-center">{{ $row['grade'] }}</td>
                            <td class="border px-3 py-2 text-center">{{ $row['remark'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-gray-500">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <p class="text-xs text-gray-500 mt-6 text-center">Generated on {{ now()->format('F j, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>