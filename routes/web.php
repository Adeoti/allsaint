<?php

use App\Models\SchoolClass;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HomeworkController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\ReportCardPdfController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ResultUploadController;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/cls', function(){
//     $classes_arr = SchoolClass::whereJsonContains('branch_ids', '1')->pluck('name', 'id');

//     dd($classes_arr);
// });


Route::middleware(['auth'])->group(function () {
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

    Route::get('/report-cards/{record}', [ReportCardController::class, 'show'])
        ->name('report-cards.show');

    // HOS remarks routes
    Route::post('/hos-remark/save', [\App\Http\Controllers\HOSRemarkController::class, 'store'])
        ->name('hos-remark.save');

    Route::get('/hos-remark/{studentId}/{resultRootId}', [\App\Http\Controllers\HOSRemarkController::class, 'getRemark'])
        ->name('hos-remark.get');
});

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
