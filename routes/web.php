<?php

use App\Http\Controllers\Admin\DivisionController;
use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\RetentionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentExportController;
use App\Http\Controllers\JoditController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShareLinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Jodit image upload
    Route::post('/jodit-upload', [JoditController::class, 'upload'])->name('jodit.upload');

    // Documents
    Route::resource('documents', DocumentController::class)->except(['edit', 'update']);
    Route::get('/document-numbers/preview', [DocumentController::class, 'nextNumber'])->name('documents.next-number');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/preview-content', [DocumentController::class, 'previewContent'])->name('documents.preview-content');
    Route::get('/documents/{document}/versions/{version}/preview', [DocumentController::class, 'previewVersion'])->name('documents.preview-version');
    Route::get('/documents/{document}/versions/{version}/file', [DocumentController::class, 'file'])->name('documents.file');
    Route::put('/documents/{document}/save', [DocumentController::class, 'save'])->name('documents.save');
    Route::put('/documents/{document}/save-draft', [DocumentController::class, 'saveDraft'])->name('documents.save-draft');
    Route::post('/documents/{document}/versions/upload', [DocumentController::class, 'uploadVersion'])->name('documents.upload-version');
    Route::patch('/documents/{document}/visibility', [DocumentController::class, 'updateVisibility'])->name('documents.update-visibility');
    Route::post('/documents/{document}/discard', [DocumentController::class, 'discard'])->name('documents.discard');
    Route::post('/documents/{document}/toggle-public', [DocumentController::class, 'togglePublic'])->name('documents.toggle-public');

    // Approvals
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/documents/{document}/versions/{version}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/documents/{document}/versions/{version}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    Route::post('/documents/{document}/versions/{version}/rollback', [ApprovalController::class, 'rollback'])->name('approvals.rollback');
    Route::post('/documents/{document}/rollback-request/approve', [ApprovalController::class, 'approveRollback'])->name('approvals.rollback-request.approve');
    Route::post('/documents/{document}/rollback-request/reject', [ApprovalController::class, 'rejectRollback'])->name('approvals.rollback-request.reject');

    // Share Links
    Route::post('/documents/{document}/links', [ShareLinkController::class, 'store'])->name('links.store');
    Route::delete('/documents/{document}/links/{link}', [ShareLinkController::class, 'destroy'])->name('links.destroy');
    Route::get('/my-shared-edits', [ShareLinkController::class, 'history'])->name('shared.history');

    // PDF Export
    Route::post('/documents/{document}/export-pdf', [DocumentExportController::class, 'export'])
        ->name('documents.export-pdf');

    // Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('divisions', DivisionController::class);
        Route::resource('users', UserController::class);
        Route::get('/retention', [RetentionController::class, 'edit'])->name('retention.edit');
        Route::put('/retention', [RetentionController::class, 'update'])->name('retention.update');
        Route::resource('document-types', DocumentTypeController::class);
        Route::get('/documents', [AdminDocumentController::class, 'index'])->name('documents.index');
        Route::delete('/documents/{document}', [AdminDocumentController::class, 'destroy'])->name('documents.destroy');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Shared link access (no auth required)
Route::get('/share/{token}', [ShareLinkController::class, 'access'])->name('shared.documents');
Route::post('/share/{token}/save', [ShareLinkController::class, 'save'])->name('shared.documents.save');
Route::post('/share/{token}/discard', [ShareLinkController::class, 'discard'])->name('shared.documents.discard');
Route::post('/share/{token}/upload', [ShareLinkController::class, 'upload'])->name('shared.documents.upload');

require __DIR__.'/auth.php';
