<?php

namespace App\Http\Controllers;

use App\Models\TeacherRemark;
use App\Models\ResultRoot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherRemarkController extends Controller
{
    /**
     * Store or update teacher remark
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'result_root_id' => 'required|exists:result_roots,id',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $teacherRemark = TeacherRemark::updateOrCreate(
                [
                    'student_id' => $request->student_id,
                    'result_root_id' => $request->result_root_id,
                    'teacher_id' => Auth::id(),
                ],
                [
                    'remark' => $request->remark,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Remark saved successfully',
                'remark' => $teacherRemark->remark,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save remark: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get teacher remark for a student
     */
    public function getRemark($studentId, $resultRootId)
    {
        $remark = TeacherRemark::where('student_id', $studentId)
            ->where('result_root_id', $resultRootId)
            ->where('teacher_id', Auth::id())
            ->first();

        return response()->json([
            'remark' => $remark ? $remark->remark : null,
            'exists' => $remark !== null,
        ]);
    }
}