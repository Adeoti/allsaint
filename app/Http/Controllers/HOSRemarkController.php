<?php

namespace App\Http\Controllers;

use App\Models\HOSRemark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HOSRemarkController extends Controller
{
    /**
     * Store or update HOS remark
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
            'result_root_id' => 'required|exists:result_roots,id',
            'remark' => 'nullable|string|max:500',
        ]);

        try {
            $hosRemark = HOSRemark::updateOrCreate(
                [
                    'student_id' => $request->student_id,
                    'result_root_id' => $request->result_root_id,
                    'hos_id' => Auth::id(),
                ],
                [
                    'remark' => $request->remark,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'HOS remark saved successfully',
                'remark' => $hosRemark->remark,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save HOS remark: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get HOS remark for a student
     */
    public function getRemark($studentId, $resultRootId)
    {
        $remark = HOSRemark::where('student_id', $studentId)
            ->where('result_root_id', $resultRootId)
            ->where('hos_id', Auth::id())
            ->first();

        return response()->json([
            'remark' => $remark ? $remark->remark : null,
            'exists' => $remark !== null,
        ]);
    }
}