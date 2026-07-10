<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cumulative Report - Select Session & Class</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .gradient-header {
            background: linear-gradient(135deg, #056b05 0%, #034d03 100%);
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="gradient-header text-white px-8 py-6">
            <h1 class="text-2xl font-bold">Cumulative Report</h1>
            <p class="text-green-100 mt-1 text-sm">Select an academic session and class to view the annual
                cumulative report for every student.</p>
        </div>

        <form action="{{ route('cumulative-reports.go') }}" method="GET" class="px-8 py-8 space-y-6">
            <div>
                <label for="academic_session" class="block text-sm font-semibold text-gray-700 mb-2">
                    Academic Session
                </label>
                <select name="academic_session" id="academic_session" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 focus:ring-2 focus:ring-green-600 focus:border-green-600 outline-none">
                    <option value="" disabled selected>Choose a session</option>
                    @foreach ($sessions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="class_id" class="block text-sm font-semibold text-gray-700 mb-2">
                    Class
                </label>
                <select name="class_id" id="class_id" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-gray-800 focus:ring-2 focus:ring-green-600 focus:border-green-600 outline-none">
                    <option value="" disabled selected>Choose a class</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-3 rounded-lg transition-colors shadow-md">
                View Cumulative Report
            </button>
        </form>
    </div>

</body>

</html>