<?php

namespace App\Http\Controllers;

use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResultUploadController extends Controller
{
    protected $resultService;
    
    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }
    
    /**
     * Save manual result entry
     */
    public function saveManualEntry(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'result_root_id' => 'required|exists:result_roots,id',
                'class_id' => 'required|exists:school_classes,id',
                'subject_id' => 'required|exists:subjects,id',
                'entry_type' => 'required|in:manual',
                'students' => 'required|array',
            ]);
            
            $userId = auth()->id();
            
            // Create manual entry
            $resultUpload = $this->resultService->createManualEntry(
                $validated,
                $validated['result_root_id'],
                $validated['class_id'],
                $validated['subject_id'],
                $userId
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Manual entry saved successfully',
                'data' => $resultUpload
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}