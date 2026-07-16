<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Overall Ranking - {{ $class->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print { .no-print { display: none !important; } @page { margin: 0.5in; } }
        .rank-1 { background-color: #fef9c3; }
        .rank-2 { background-color: #f3f4f6; }
        .rank-3 { background-color: #fed7aa; }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-5xl mx-auto">
        <div class="mb-6 flex gap-3 no-print">
            <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-500">Print</button>
            <a href="{{ route('overall-ranking.download.pdf', request()->query()) }}" target="_blank" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-500">Download PDF</a>
            <a href="{{ route('overall-ranking.download.csv', request()->query()) }}" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-500">Download CSV</a>
            <div class="flex-1"></div>
            <a href="{{ route('overall-ranking.select') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md text-sm font-semibold hover:bg-gray-500">New Search</a>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">{{ $schoolDetails['school_name'] ?? 'School Name' }}</h1>
                <p class="text-gray-500 text-sm">{{ $schoolDetails['school_address'] ?? '' }}</p>
                <h2 class="text-xl font-semibold mt-3">Overall Best Students — {{ $academicSession }}</h2>
                <p class="text-gray-600 text-sm">
                    {{ $class->name }} &bull; Terms: {{ implode(', ', $termsPresent) }}
                </p>
            </div>

            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border px-3 py-2">Position</th>
                        <th class="border px-3 py-2 text-left">Student Name</th>
                        @foreach ($termsPresent as $term)
                            <th class="border px-3 py-2">{{ $term }} Total</th>
                        @endforeach
                        <th class="border px-3 py-2">Overall Total<br><span class="font-normal text-xs">(Expected {{ $overallExpected }})</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $row)
                        <tr class="{{ $row['rank'] == 1 ? 'rank-1' : ($row['rank'] == 2 ? 'rank-2' : ($row['rank'] == 3 ? 'rank-3' : '')) }}">
                            <td class="border px-3 py-2 text-center font-bold">{{ $row['rank'] }}</td>
                            <td class="border px-3 py-2 font-medium">{{ $row['name'] }}</td>
                            @foreach ($termsPresent as $term)
                                <td class="border px-3 py-2 text-center">{{ $row['terms'][$term] }}</td>
                            @endforeach
                            <td class="border px-3 py-2 text-center font-bold">{{ $row['overall_total'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-4 text-gray-500">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <p class="text-xs text-gray-500 mt-6 text-center">Generated on {{ now()->format('F j, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>