<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Overall Best Students</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <h1 class="text-xl font-bold text-gray-800 mb-6">Overall Best Students (Session)</h1>

        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">{{ session('error') }}</div>
        @endif

        <form action="{{ route('overall-ranking.show') }}" method="GET" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Academic Session</label>
                <select name="academic_session" id="academic_session" required
                    class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Select --</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session }}">{{ $session }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" id="class_id" required disabled
                    class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Select Session first --</option>
                </select>
            </div>

            <button type="submit" id="submit_btn" disabled
                class="w-full bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                View Ranking
            </button>
        </form>
    </div>

    <script>
        const sessionSelect = document.getElementById('academic_session');
        const classSelect = document.getElementById('class_id');
        const submitBtn = document.getElementById('submit_btn');

        sessionSelect.addEventListener('change', async function () {
            classSelect.innerHTML = '<option value="">Loading...</option>';
            classSelect.disabled = true;
            submitBtn.disabled = true;

            if (!this.value) {
                classSelect.innerHTML = '<option value="">-- Select Session first --</option>';
                return;
            }

            const res = await fetch(`{{ route('overall-ranking.classes') }}?academic_session=${encodeURIComponent(this.value)}`);
            const classes = await res.json();

            classSelect.innerHTML = '<option value="">-- Select --</option>';
            classes.forEach(c => {
                classSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });
            classSelect.disabled = false;
        });

        classSelect.addEventListener('change', function () {
            submitBtn.disabled = !this.value;
        });
    </script>
</body>
</html>