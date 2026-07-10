<?php

use App\Models\SchoolClass;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\ReportCardPdfController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ResultUploadController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\CumulativeReportController;
use App\Http\Controllers\StudentCumulativeReportController;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/cls', function(){
//     $classes_arr = SchoolClass::whereJsonContains('branch_ids', '1')->pluck('name', 'id');

//     dd($classes_arr);
// });


Route::middleware(['auth'])->group(function () {
    // CUMULATIVE REPORT ROUTES
    // ----- Teacher: cumulative report for a whole class -----
    Route::get('/cumulative-reports/select', [CumulativeReportController::class, 'select'])
        ->name('cumulative-reports.select');

    Route::get('/cumulative-reports/go', [CumulativeReportController::class, 'go'])
        ->name('cumulative-reports.go');

    Route::get('/cumulative-reports/show', [CumulativeReportController::class, 'show'])
        ->name('cumulative-reports.show');

    // ----- Student: cumulative report for themselves only -----
    Route::get('/my-cumulative-report/select', [StudentCumulativeReportController::class, 'select'])
        ->name('student.cumulative-reports.select');

    Route::get('/my-cumulative-report/go', [StudentCumulativeReportController::class, 'go'])
        ->name('student.cumulative-reports.go');

    Route::get('/my-cumulative-report/show', [StudentCumulativeReportController::class, 'show'])
        ->name('student.cumulative-reports.show');


    // BROADSHEET ROUTES
    Route::get(
        '/admin/broadsheets/{record}/view',
        [App\Http\Controllers\BroadsheetController::class, 'view']
    )
        ->name('filament.admin.resources.broadsheets.view');

    Route::post(
        '/admin/broadsheets/{record}/regenerate',
        [App\Http\Controllers\BroadsheetController::class, 'regenerate']
    )
        ->name('broadcast.regenerate');

    Route::get(
        '/admin/broadsheets/{record}/download',
        [App\Http\Controllers\BroadsheetController::class, 'downloadPdf']
    )
        ->name('broadcast.download');
    Route::post('/admin/result-uploads/manual-save', [ResultUploadController::class, 'saveManualEntry'])
        ->name('filament.admin.resources.result-uploads.manual-save');


    Route::get('/admin/result-uploads/manual-entry', \App\Filament\Resources\ResultUploadResource\Pages\ManualEntryPage::class)
        ->name('filament.admin.resources.result-uploads.manual-entry');

    Route::post('/admin/result-uploads/manual-save', [\App\Filament\Resources\ResultUploadResource\Pages\ManualEntryPage::class, 'saveManualEntry'])
        ->name('manual-result-entry.save');

    Route::post('/teacher-remark/save', [\App\Http\Controllers\TeacherRemarkController::class, 'store'])
        ->name('teacher-remark.save');

    Route::get('/teacher-remark/{studentId}/{resultRootId}', [\App\Http\Controllers\TeacherRemarkController::class, 'getRemark'])
        ->name('teacher-remark.get');

    // HOS remarks routes
    Route::post('/hos-remark/save', [\App\Http\Controllers\HOSRemarkController::class, 'store'])
        ->name('hos-remark.save');

    Route::get('/hos-remark/{studentId}/{resultRootId}', [\App\Http\Controllers\HOSRemarkController::class, 'getRemark'])
        ->name('hos-remark.get');
});

Route::get('/report-cards/{record}', [ReportCardController::class, 'show'])
    ->name('report-cards.show');

Route::get('/', [HomeController::class, 'index'])->name('home');
// In routes/web.php
Route::get('/homework/{homework}/download', [HomeworkController::class, 'download'])->name('homework.download');

Route::get('/download-report-cards/{recordId}', [ReportCardPdfController::class, 'downloadReportCards'])
    ->name('download-report-cards');

Route::get('/symlink', function () {
    if (function_exists('symlink')) {
        echo "symlink() is enabled.";
    } else {
        echo "symlink() is NOT enabled.";
    }
});

Route::get('/download-result/{recordId}', [ResultController::class, 'downloadResult'])->name('download.result');
