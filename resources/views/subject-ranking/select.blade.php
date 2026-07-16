<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Best Students in Subject</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
        <h1 class="text-xl font-bold text-gray-800 mb-6">Best Students in a Subject</h1>

        @if (session('error'))
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">{{ session('error') }}</div>
        @endif

        <form action="{{ route('subject-ranking.show') }}" method="GET" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Term / Session (Result Root)</label>
                <select name="result_root_id" id="result_root_id" required
                    class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Select --</option>
                    @foreach ($resultRoots as $root)
                        <option value="{{ $root->id }}">
                            {{ $root->name }} ({{ $root->term }} - {{ $root->academic_session }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" id="class_id" required disabled
                    class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Select Term first --</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <select name="subject_id" id="subject_id" required disabled
                    class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Select Class first --</option>
                </select>
            </div>

            <button type="submit" id="submit_btn" disabled
                class="w-full bg-blue-600 text-white py-2 rounded-md font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                View Ranking
            </button>
        </form>
    </div>

    <script>
        const resultRootSelect = document.getElementById('result_root_id');
        const classSelect = document.getElementById('class_id');
        const subjectSelect = document.getElementById('subject_id');
        const submitBtn = document.getElementById('submit_btn');

        function resetSelect(select, placeholder) {
            select.innerHTML = `<option value="">${placeholder}</option>`;
            select.disabled = true;
        }

        resultRootSelect.addEventListener('change', async function () {
            resetSelect(classSelect, 'Loading...');
            resetSelect(subjectSelect, '-- Select Class first --');
            submitBtn.disabled = true;

            if (!this.value) {
                resetSelect(classSelect, '-- Select Term first --');
                return;
            }

            const res = await fetch(`{{ route('subject-ranking.classes') }}?result_root_id=${this.value}`);
            const classes = await res.json();

            classSelect.innerHTML = '<option value="">-- Select --</option>';
            classes.forEach(c => {
                classSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });
            classSelect.disabled = false;
        });

        classSelect.addEventListener('change', async function () {
            resetSelect(subjectSelect, 'Loading...');
            submitBtn.disabled = true;

            if (!this.value) {
                resetSelect(subjectSelect, '-- Select Class first --');
                return;
            }

            const res = await fetch(`{{ route('subject-ranking.subjects') }}?result_root_id=${resultRootSelect.value}&class_id=${this.value}`);
            const subjects = await res.json();

            subjectSelect.innerHTML = '<option value="">-- Select --</option>';
            subjects.forEach(s => {
                subjectSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
            });
            subjectSelect.disabled = false;
        });

        subjectSelect.addEventListener('change', function () {
            submitBtn.disabled = !this.value;
        });
    </script>
</body>
</html>