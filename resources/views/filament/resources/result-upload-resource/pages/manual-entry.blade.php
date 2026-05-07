@php
use App\Models\ResultRoot;
use App\Models\SchoolClass;
use App\Models\Subject;
@endphp

<x-filament-panels::page>
    <div class="max-w-7xl mx-auto">
        <!-- Selection Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Select Result Details</h2>
            <form method="GET" action="{{ route('filament.admin.resources.result-uploads.manual-entry') }}"
                class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Result Root
                        </label>
                        <select name="result_root_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Select Result Root</option>
                            @foreach ($resultRootList as $root)
                                <option value="{{ $root->id }}"
                                    {{ request('result_root_id') == $root->id ? 'selected' : '' }}>
                                    {{ $root->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Class
                        </label>
                        <select name="class_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Select Class</option>
                            @foreach ($classList as $class)
                                <option value="{{ $class->id }}"
                                    {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Subject
                        </label>
                        <select name="subject_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Select Subject</option>
                            @foreach ($subjectList as $subject)
                                <option value="{{ $subject->id }}"
                                    {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        style="background:tomato; color:white; padding:6px 13px; text-align:center; border-radius:5px;"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Load Students
                    </button>
                </div>
            </form>
        </div>

        <!-- Manual Entry Form -->
        @if ($resultRootId && $classId && $subjectId && count($students) > 0 && count($examColumns) > 0)
            <form method="POST" action="{{ route('manual-result-entry.save') }}" id="manualEntryForm">
                @csrf
                <input type="hidden" name="result_root_id" value="{{ $resultRootId }}">
                <input type="hidden" name="class_id" value="{{ $classId }}">
                <input type="hidden" name="subject_id" value="{{ $subjectId }}">

                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-semibold">
                                    Enter Scores for {{ count($students) }} Students
                                </h2>
                                <p class="text-sm text-gray-600">
                                    Result Root: {{ ResultRoot::find($resultRootId)->name }} |
                                    Class: {{ SchoolClass::find($classId)->name }} |
                                    Subject: {{ Subject::find($subjectId)->name }}
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                                    Save All Scores
                                </button>
                                <a href="{{ route('filament.admin.resources.result-uploads.index') }}"
                                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider sticky left-0 bg-gray-50">
                                        Student Name
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Roll No.
                                    </th>
                                    @foreach ($examColumns as $column)
                                        @php
                                            $columnKey = $column['label'] . ' - ' . $column['overall_score'];
                                        @endphp
                                        <th
                                            class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider min-w-32">
                                            {{ $columnKey }}
                                        </th>
                                    @endforeach
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($students as $student)
                                    @php
                                        $studentId = $student->id;
                                        $studentScores = $existingData[$studentId]['scores'] ?? [];
                                        $studentTotal = $existingData[$studentId]['total'] ?? 0;
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td
                                            class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white">
                                            {{ $student->name }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                            {{ $student->student->roll_number ?? 'N/A' }}
                                        </td>
                                        @foreach ($examColumns as $column)
                                            @php
                                                $columnKey = $column['label'] . ' - ' . $column['overall_score'];
                                                $maxScore = (int) $column['overall_score'];
                                                
                                                // Check for existing data with CSV format key
                                                if (isset($studentScores[$columnKey])) {
                                                    $currentScore = $studentScores[$columnKey];
                                                } 
                                                // Fallback to old format (just label) for backward compatibility
                                                elseif (isset($studentScores[$column['label']])) {
                                                    $currentScore = $studentScores[$column['label']];
                                                } else {
                                                    $currentScore = '';
                                                }
                                            @endphp
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input type="number"
                                                    name="scores[{{ $studentId }}][{{ $columnKey }}]"
                                                    value="{{ $currentScore }}" min="0"
                                                    max="{{ $maxScore }}" step="0.5"
                                                    class="score-input w-full px-2 py-1 border border-gray-300 rounded focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                                    placeholder="0"
                                                    oninput="updateStudentTotal(this, {{ $studentId }}, {{ $maxScore }})">
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span id="total-{{ $studentId }}"
                                                class="font-semibold {{ $studentTotal >= 50 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $studentTotal }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-6 border-t">
                        <div class="text-sm text-gray-600">
                            <p>• Enter scores for each student (blank = 0)</p>
                            <p>• Scores automatically validate against maximum values</p>
                            <p>• Use CSV format: "Label - MaxScore" (e.g., "3rd CA - 20")</p>
                            <p>• Decimals allowed (use .5 for half marks)</p>
                            <p>• Click "Save All Scores" when done</p>
                        </div>
                    </div>
                </div>
            </form>

            <script>
                // Simple function to update totals
                function updateStudentTotal(input, studentId, maxScore) {
                    let value = parseFloat(input.value) || 0;

                    // Validate against max
                    if (value > maxScore) {
                        input.value = maxScore;
                        value = maxScore;
                    }

                    // Get all inputs for this student row
                    const row = input.closest('tr');
                    const inputs = row.querySelectorAll('input[type="number"]');
                    let total = 0;

                    inputs.forEach(input => {
                        const val = parseFloat(input.value) || 0;
                        total += val;
                    });

                    // Update total display
                    const totalElement = document.getElementById('total-' + studentId);
                    if (totalElement) {
                        const roundedTotal = Math.round(total * 10) / 10;
                        totalElement.textContent = roundedTotal;
                        totalElement.className = roundedTotal >= 50 ?
                            'font-semibold text-green-600' :
                            'font-semibold text-red-600';
                    }
                }

                // Form submission with confirmation
                document.addEventListener('DOMContentLoaded', function() {
                    const form = document.getElementById('manualEntryForm');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            if (!confirm('Are you sure you want to save all scores?')) {
                                e.preventDefault();
                                return;
                            }

                            // Show loading indicator
                            const submitButton = this.querySelector('button[type="submit"]');
                            if (submitButton) {
                                submitButton.textContent = 'Saving...';
                                submitButton.disabled = true;
                            }
                        });
                    }
                });
            </script>
        @elseif(request()->hasAny(['result_root_id', 'class_id', 'subject_id']))
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <p class="text-gray-500">
                    @if (count($students) === 0)
                        No students found in this class.
                    @elseif(count($examColumns) === 0)
                        No exam columns configured for the selected result root.
                    @else
                        Please select all three options above.
                    @endif
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>