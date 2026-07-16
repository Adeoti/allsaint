<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h1 { text-align: center; margin-bottom: 2px; }
        .subtitle { text-align: center; color: #555; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 5px; text-align: center; }
        th { background-color: #eee; }
        .rank-1 { background-color: #fef9c3; }
        .rank-2 { background-color: #f3f4f6; }
        .rank-3 { background-color: #fed7aa; }
    </style>
</head>
<body>
    <h1>{{ $schoolDetails['school_name'] ?? 'School Name' }}</h1>
    <div class="subtitle">{{ $schoolDetails['school_address'] ?? '' }}</div>
    <h3 style="text-align:center;">Overall Best Students — {{ $academicSession }}</h3>
    <p style="text-align:center;">{{ $class->name }} | Terms: {{ implode(', ', $termsPresent) }}</p>

    <table>
        <thead>
            <tr>
                <th>Position</th>
                <th>Student Name</th>
                @foreach ($termsPresent as $term)
                    <th>{{ $term }} Total</th>
                @endforeach
                <th>Overall Total (Expected {{ $overallExpected }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $row)
                <tr class="{{ $row['rank'] == 1 ? 'rank-1' : ($row['rank'] == 2 ? 'rank-2' : ($row['rank'] == 3 ? 'rank-3' : '')) }}">
                    <td>{{ $row['rank'] }}</td>
                    <td style="text-align:left;">{{ $row['name'] }}</td>
                    @foreach ($termsPresent as $term)
                        <td>{{ $row['terms'][$term] }}</td>
                    @endforeach
                    <td><b>{{ $row['overall_total'] }}</b></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="text-align:center; font-size:10px; color:#777; margin-top:20px;">
        Generated on {{ now()->format('F j, Y h:i A') }}
    </p>
</body>
</html>