<?php

use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\DocumentTemplateController;
use App\Http\Controllers\Admin\RetentionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DocumentTypeController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentExportController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

Route::get('/', function () {
    return view('welcome');
});

// Public QR Code verification routes
Route::get('/d/{token}', [DocumentController::class, 'viewByHash'])->name('documents.hash');
Route::get('/d/{token}/preview', [DocumentController::class, 'previewByHash'])->name('documents.hash.preview');
Route::get('/archives/verify/{token}', [\App\Http\Controllers\ArchiveController::class, 'verifyByToken'])->name('archives.verify');

use App\Http\Controllers\SignatureController;

Route::middleware('auth')->group(function () {
    Route::get('/verification-pending', [\App\Http\Controllers\Auth\AccountVerificationController::class, 'notice'])
        ->name('verification.pending');
    Route::get('/verification-status', [\App\Http\Controllers\Auth\AccountVerificationController::class, 'status'])
        ->name('verification.status');

    Route::middleware(['account.verified', 'signature.required'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Global Document Search (respects visibility)
    Route::get('/search', [SearchController::class, 'search'])->name('search');

    // Notifications (Page + API)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/bulk-read', [NotificationController::class, 'bulkMarkAsRead'])->name('notifications.bulk-read');
    Route::delete('/notifications/bulk-delete', [NotificationController::class, 'bulkDestroy'])->name('notifications.bulk-delete');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'destroyAll'])->name('notifications.delete-all');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Signatures
    Route::get('/profile/signature', [SignatureController::class, 'show'])->name('profile.signature.show');
    Route::post('/profile/signature', [SignatureController::class, 'store'])->name('profile.signature.store');
    Route::delete('/profile/signature/{signature?}', [SignatureController::class, 'destroy'])->name('profile.signature.destroy');
    Route::get('/signatures/users', [SignatureController::class, 'availableUsers'])->name('signatures.users');
    Route::get('/signature-requests', [SignatureController::class, 'requestsIndex'])->name('signatures.requests.index');
    Route::post('/signature-requests/bulk-approve', [SignatureController::class, 'bulkApprove'])->name('signatures.requests.bulk-approve');
    Route::post('/signature-requests/bulk-reject', [SignatureController::class, 'bulkReject'])->name('signatures.requests.bulk-reject');
    Route::post('/signature-requests/approve-all-pending', [SignatureController::class, 'approveAllPending'])->name('signatures.requests.approve-all-pending');
    Route::post('/signature-requests/{signatureRequest}/approve', [SignatureController::class, 'approve'])->name('signatures.requests.approve');
    Route::post('/signature-requests/{signatureRequest}/reject', [SignatureController::class, 'reject'])->name('signatures.requests.reject');
    Route::post('/signature-requests/{signatureRequest}/consume', [SignatureController::class, 'consume'])->name('signatures.requests.consume');
    Route::post('/documents/{document}/stamp-signature', [SignatureController::class, 'stampPdfSignature'])->name('documents.stamp-signature');
    Route::post('/documents/{document}/stamp-qrcode', [SignatureController::class, 'stampPdfQrCode'])->name('documents.stamp-qrcode');
    Route::post('/documents/{document}/revert-pdf-signature', [SignatureController::class, 'revertPdfSignature'])->name('documents.revert-pdf-signature');

    // Documents
    Route::get('/documents/choose', [DocumentController::class, 'choose'])->name('documents.choose');
    Route::resource('documents', DocumentController::class)->except(['edit', 'update']);
    Route::get('/document-numbers/preview', [DocumentController::class, 'nextNumber'])->name('documents.next-number');
    Route::get('/document-numbers/check', [DocumentController::class, 'checkNumber'])->name('documents.check-number');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download')->withTrashed();
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview')->withTrashed();
    Route::get('/documents/templates/{template}/preview', [DocumentTemplateController::class, 'preview'])->name('templates.preview');
    Route::get('/documents/templates/{template}/preview-config', [DocumentTemplateController::class, 'previewConfig'])->name('templates.preview-config');
    Route::post('/documents/{document}/summarize', [DocumentController::class, 'summarize'])->name('documents.summarize');
    Route::get('/documents/{document}/summary-status', [DocumentController::class, 'summaryStatus'])->name('documents.summary-status');
    Route::get('/documents/{document}/onlyoffice-status', [DocumentController::class, 'onlyofficeStatus'])->name('documents.onlyoffice-status');
    Route::get('/documents/{document}/preview-content', [DocumentController::class, 'previewContent'])->name('documents.preview-content')->withTrashed();
    Route::get('/documents/{document}/versions/{version}/preview', [DocumentController::class, 'previewVersion'])->name('documents.preview-version')->withTrashed();
    Route::get('/documents/{document}/versions/{version}/file', [DocumentController::class, 'file'])->name('documents.file')->withTrashed();
    Route::get('/documents/{document}/qrcode', [DocumentController::class, 'qrCode'])->name('documents.qrcode');
    Route::put('/documents/{document}/save', [DocumentController::class, 'save'])->name('documents.save');
    Route::put('/documents/{document}/save-draft', [DocumentController::class, 'saveDraft'])->name('documents.save-draft');
    Route::post('/documents/{document}/save-draft-editor', [DocumentController::class, 'saveDraftEditor'])->name('documents.save-draft-editor');
    Route::post('/documents/{document}/versions/upload', [DocumentController::class, 'uploadVersion'])->name('documents.upload-version');
    Route::post('/documents/{document}/upload-edit', [DocumentController::class, 'uploadAndEdit'])->name('documents.upload-edit');
    Route::patch('/documents/{document}/visibility', [DocumentController::class, 'updateVisibility'])->name('documents.update-visibility');
    Route::post('/documents/{document}/discard', [DocumentController::class, 'discard'])->name('documents.discard');
    Route::post('/documents/{document}/finish-editing', [DocumentController::class, 'finishEditing'])->name('documents.finish-editing');
    Route::post('/documents/{document}/toggle-public', [DocumentController::class, 'togglePublic'])->name('documents.toggle-public');
    Route::post('/documents/{document}/rename', [DocumentController::class, 'rename'])->name('documents.rename');
    Route::post('/documents/{document}/request-rename', [DocumentController::class, 'requestRename'])->name('documents.request-rename');
    Route::post('/documents/{document}/cancel-rename', [DocumentController::class, 'cancelRenameRequest'])->name('documents.cancel-rename');
    Route::post('/documents/{document}/request-deletion', [DocumentController::class, 'requestDeletion'])->name('documents.request-deletion');
    Route::post('/documents/{document}/cancel-deletion-request', [DocumentController::class, 'cancelDeletionRequest'])->name('documents.cancel-deletion-request');
    Route::post('/documents/{document}/approve-deletion', [DocumentController::class, 'approveDeletion'])->name('documents.approve-deletion');
    Route::post('/documents/{document}/reject-deletion', [DocumentController::class, 'rejectDeletion'])->name('documents.reject-deletion');
    Route::get('/documents/{document}/corporate-soft-files', [DocumentController::class, 'corporateSoftFiles'])->name('documents.corporate-soft-files');
    Route::post('/documents/{document}/corporate-soft-files/{corporateSoftFile}/apply', [DocumentController::class, 'applyCorporateSoftFile'])->name('documents.corporate-soft-files.apply');
    Route::post('/documents/{document}/corporate-soft-files/remove', [DocumentController::class, 'removeCorporateSoftFile'])->name('documents.corporate-soft-files.remove');

    // Approvals
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::get('/approvals/versions', [ApprovalController::class, 'versions'])->name('approvals.versions');
    Route::get('/approvals/signatures', [SignatureController::class, 'requestsIndex'])->name('approvals.signatures');
    Route::get('/approvals/renames', [ApprovalController::class, 'renames'])->name('approvals.renames');
    Route::get('/approvals/rollbacks', [ApprovalController::class, 'rollbacks'])->name('approvals.rollbacks');
    Route::get('/approvals/deletions', [ApprovalController::class, 'deletions'])->name('approvals.deletions');
    Route::post('/approvals/bulk-approve-versions', [ApprovalController::class, 'bulkApproveVersions'])->name('approvals.bulk-approve-versions');
    Route::post('/approvals/bulk-reject-versions', [ApprovalController::class, 'bulkRejectVersions'])->name('approvals.bulk-reject-versions');
    Route::post('/documents/{document}/versions/{version}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/documents/{document}/versions/{version}/reject', [ApprovalController::class, 'reject'])->name('approvals.reject');
    Route::post('/documents/{document}/versions/{version}/rollback', [ApprovalController::class, 'rollback'])->name('approvals.rollback');
    Route::post('/documents/{document}/rollback-request/approve', [ApprovalController::class, 'approveRollback'])->name('approvals.rollback-request.approve');
    Route::post('/documents/{document}/rollback-request/reject', [ApprovalController::class, 'rejectRollback'])->name('approvals.rollback-request.reject');
    Route::post('/documents/{document}/rename-request/approve', [ApprovalController::class, 'approveRename'])->name('approvals.rename-request.approve');
    Route::post('/documents/{document}/rename-request/reject', [ApprovalController::class, 'rejectRename'])->name('approvals.rename-request.reject');
    Route::post('/documents/{document}/deletion-request/approve', [ApprovalController::class, 'approveDeletion'])->name('approvals.deletion-request.approve');
    Route::post('/documents/{document}/deletion-request/reject', [ApprovalController::class, 'rejectDeletion'])->name('approvals.deletion-request.reject');

    // Document Shares (Google Docs model)
    Route::post('/documents/{document}/shares', [DocumentShareController::class, 'store'])->name('shares.store');
    Route::patch('/documents/{document}/shares/{share}', [DocumentShareController::class, 'updateUserShare'])->name('shares.update');
    Route::delete('/documents/{document}/shares/{share}', [DocumentShareController::class, 'destroyUserShare'])->name('shares.destroy');
    Route::patch('/documents/{document}/unit-kerja-shares/{unitKerjaShare}', [DocumentShareController::class, 'updateUnitKerjaShare'])->name('shares.unit-kerja.update');
    Route::delete('/documents/{document}/unit-kerja-shares/{unitKerjaShare}', [DocumentShareController::class, 'destroyUnitKerjaShare'])->name('shares.unit-kerja.destroy');
    Route::patch('/documents/{document}/general-access', [DocumentShareController::class, 'updateGeneralAccess'])->name('shares.general-access.update');
    Route::post('/documents/{document}/regenerate-token', [DocumentShareController::class, 'regenerateToken'])->name('shares.regenerate-token');
    Route::get('/documents/{document}/share-data', [DocumentShareController::class, 'shareData'])->name('shares.data');
    Route::get('/documents/{document}/search-sharees', [DocumentShareController::class, 'searchSharees'])->name('shares.search');

    // Cross-Branch Distribution
    Route::post('/documents/{document}/distributions', [\App\Http\Controllers\DocumentDistributionController::class, 'store'])->name('distributions.store');
    // PDF Export
    Route::post('/documents/{document}/export-pdf', [DocumentExportController::class, 'export'])
        ->name('documents.export-pdf');
    Route::get('/documents/{document}/export-preview', [DocumentExportController::class, 'preview'])
        ->name('documents.export-preview');

    // Context Switcher (Company & Branch)
    Route::post('/context/switch', [\App\Http\Controllers\ContextSwitchController::class, 'switch'])->name('context.switch');
    Route::get('/companies/{company}/branches', [\App\Http\Controllers\ContextSwitchController::class, 'branchesForCompany'])->name('companies.branches');

    // Director Active Documents Review & Accordion Browsing
    Route::get('/director/active-documents', [\App\Http\Controllers\DirectorDocumentController::class, 'activeDocuments'])->name('director.active-documents.index');
    Route::get('/director/active-docs', [\App\Http\Controllers\DirectorDocumentController::class, 'activeDocuments'])->name('director.active-documents');
    Route::get('/director/documents', [\App\Http\Controllers\DirectorDocumentController::class, 'index'])->name('director.documents.index');
    Route::post('/director/documents/bulk-acknowledge', [\App\Http\Controllers\DirectorDocumentController::class, 'bulkAcknowledge'])->name('director.documents.bulk-acknowledge');
    Route::post('/director/documents/{document}/acknowledge', [\App\Http\Controllers\DirectorDocumentController::class, 'acknowledgeRead'])->name('director.documents.acknowledge');

    // Trash (Sampah Dokumen) - Accessible to all roles
    Route::get('/trash', [\App\Http\Controllers\TrashController::class, 'index'])->name('trash.index');
    Route::get('/trash/{id}/preview', [\App\Http\Controllers\TrashController::class, 'preview'])->name('trash.preview');
    Route::post('/trash/bulk-restore', [\App\Http\Controllers\TrashController::class, 'bulkRestore'])->name('trash.bulk-restore');
    Route::delete('/trash/bulk-force-delete', [\App\Http\Controllers\TrashController::class, 'bulkForceDelete'])->name('trash.bulk-force-delete');
    Route::post('/trash/{id}/restore', [\App\Http\Controllers\TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('/trash/{id}/force-delete', [\App\Http\Controllers\TrashController::class, 'forceDelete'])->name('trash.force-delete');
    Route::delete('/trash/empty', [\App\Http\Controllers\TrashController::class, 'emptyTrash'])->name('trash.empty');

    // Archiving & Records Management (Arsip)
    Route::get('/archives', [\App\Http\Controllers\ArchiveController::class, 'index'])->name('archives.index');
    Route::get('/archives/export', [\App\Http\Controllers\ArchiveController::class, 'export'])->name('archives.export');
    Route::get('/archives/{archive}', [\App\Http\Controllers\ArchiveController::class, 'show'])->name('archives.show');
    Route::get('/archives/{archive}/download', [\App\Http\Controllers\ArchiveController::class, 'download'])->name('archives.download');
    Route::get('/archives/{archive}/preview', [\App\Http\Controllers\ArchiveController::class, 'preview'])->name('archives.preview');
    Route::post('/archives/{archive}/verify-integrity', [\App\Http\Controllers\ArchiveController::class, 'verifyIntegrity'])->name('archives.verify-integrity');
    Route::patch('/archives/{archive}/metadata', [\App\Http\Controllers\ArchiveController::class, 'correctMetadata'])->name('archives.correct-metadata');
    Route::post('/archives/{archive}/reactivate', [\App\Http\Controllers\ArchiveController::class, 'reactivate'])->name('archives.reactivate');
    Route::post('/archives/{archive}/void', [\App\Http\Controllers\ArchiveController::class, 'void'])->name('archives.void');

    // Archive Requests & Approvals
    Route::get('/archive-requests', [\App\Http\Controllers\ArchiveRequestController::class, 'index'])->name('archive-requests.index');
    Route::get('/archive-requests/create', [\App\Http\Controllers\ArchiveRequestController::class, 'create'])->name('archive-requests.create');
    Route::post('/archive-requests', [\App\Http\Controllers\ArchiveRequestController::class, 'store'])->name('archive-requests.store');
    Route::get('/archive-requests/preview-number', [\App\Http\Controllers\ArchiveRequestController::class, 'previewNumber'])->name('archive-requests.preview-number');
    Route::get('/archive-requests/{archiveRequest}', [\App\Http\Controllers\ArchiveRequestController::class, 'show'])->name('archive-requests.show');
    Route::post('/archive-requests/{archiveRequest}/submit', [\App\Http\Controllers\ArchiveRequestController::class, 'submit'])->name('archive-requests.submit');
    Route::post('/archive-requests/{archiveRequest}/approve', [\App\Http\Controllers\ArchiveRequestController::class, 'approve'])->name('archive-requests.approve');
    Route::post('/archive-requests/{archiveRequest}/reject', [\App\Http\Controllers\ArchiveRequestController::class, 'reject'])->name('archive-requests.reject');
    Route::post('/archive-requests/{archiveRequest}/return', [\App\Http\Controllers\ArchiveRequestController::class, 'returnForCorrection'])->name('archive-requests.return');
    Route::post('/archive-requests/{archiveRequest}/cancel', [\App\Http\Controllers\ArchiveRequestController::class, 'cancel'])->name('archive-requests.cancel');
    Route::delete('/archive-requests/{archiveRequest}', [\App\Http\Controllers\ArchiveRequestController::class, 'destroy'])->name('archive-requests.destroy');
    Route::post('/archive-requests/{archiveRequest}/finalize', [\App\Http\Controllers\ArchiveRequestController::class, 'finalize'])->name('archive-requests.finalize');

    // Archive Governance & Legal Holds
    Route::get('/archive-governance', [\App\Http\Controllers\ArchiveGovernanceController::class, 'index'])->name('archive-governance.index');
    Route::post('/archives/{archive}/holds', [\App\Http\Controllers\ArchiveGovernanceController::class, 'storeHold'])->name('archives.holds.store');
    Route::post('/archive-holds/{hold}/release', [\App\Http\Controllers\ArchiveGovernanceController::class, 'releaseHold'])->name('archives.holds.release');
    Route::post('/archives/{archive}/dispositions', [\App\Http\Controllers\ArchiveGovernanceController::class, 'storeDisposition'])->name('archives.dispositions.store');
    Route::post('/archive-dispositions/{disposition}/approve', [\App\Http\Controllers\ArchiveGovernanceController::class, 'approveDisposition'])->name('archives.dispositions.approve');
    Route::post('/archive-dispositions/{disposition}/reject', [\App\Http\Controllers\ArchiveGovernanceController::class, 'rejectDisposition'])->name('archives.dispositions.reject');
    Route::get('/archive-dispositions/{disposition}/certificate', [\App\Http\Controllers\ArchiveGovernanceController::class, 'certificate'])->name('archives.dispositions.certificate');

    // Admin
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('companies', \App\Http\Controllers\Admin\CompanyController::class);
        Route::resource('branches', \App\Http\Controllers\Admin\BranchController::class);
        Route::resource('unit-kerja', \App\Http\Controllers\Admin\UnitKerjaController::class);
        Route::resource('users', UserController::class);
        Route::resource('signatures', \App\Http\Controllers\Admin\SignatureController::class)->except(['show']);
        Route::get('/retention', [RetentionController::class, 'edit'])->name('retention.edit');
        Route::put('/retention', [RetentionController::class, 'update'])->name('retention.update');
        Route::get('/document-expirations', [\App\Http\Controllers\Admin\DocumentExpirationController::class, 'index'])->name('expirations.index');
        Route::put('/document-expirations/default-reminder', [\App\Http\Controllers\Admin\DocumentExpirationController::class, 'updateDefaultReminder'])->name('expirations.update-default');
        Route::put('/document-expirations/{document}/reminder', [\App\Http\Controllers\Admin\DocumentExpirationController::class, 'updateDocumentReminder'])->name('expirations.update-document-reminder');
        Route::post('/document-expirations/{document}/notify', [\App\Http\Controllers\Admin\DocumentExpirationController::class, 'notify'])->name('expirations.notify');
        Route::resource('document-types', DocumentTypeController::class);
        Route::get('/templates/create-manual', [DocumentTemplateController::class, 'createManual'])->name('templates.create-manual');
        Route::post('/templates/manual', [DocumentTemplateController::class, 'storeManual'])->name('templates.store-manual');
        Route::get('/templates/{template}/editor', [DocumentTemplateController::class, 'editor'])->name('templates.editor');
        Route::post('/templates/{template}/finish-editing', [DocumentTemplateController::class, 'finishEditing'])->name('templates.finish-editing');
        Route::resource('templates', DocumentTemplateController::class)->except(['show']);
        Route::patch('/templates/{template}/toggle-status', [DocumentTemplateController::class, 'toggleStatus'])->name('templates.toggle-status');
        Route::get('/templates/{template}/download', [DocumentTemplateController::class, 'download'])->name('templates.download');
        Route::post('/templates/{template}/corporate-soft-files/{corporateSoftFile}/apply', [DocumentTemplateController::class, 'applyCorporateSoftFile'])->name('templates.corporate-soft-files.apply');
        Route::post('/templates/{template}/corporate-soft-files/remove', [DocumentTemplateController::class, 'removeCorporateSoftFile'])->name('templates.corporate-soft-files.remove');
        Route::resource('corporate-soft-files', \App\Http\Controllers\Admin\CorporateSoftFileController::class)->except(['show']);
        Route::get('/corporate-soft-files/{corporateSoftFile}/preview', [\App\Http\Controllers\Admin\CorporateSoftFileController::class, 'preview'])->name('corporate-soft-files.preview');
        Route::get('/corporate-soft-files/{corporateSoftFile}/preview-content', [\App\Http\Controllers\Admin\CorporateSoftFileController::class, 'previewContent'])->name('corporate-soft-files.preview-content');
        Route::get('/corporate-soft-files/{corporateSoftFile}/preview-config', [\App\Http\Controllers\Admin\CorporateSoftFileController::class, 'previewConfig'])->name('corporate-soft-files.preview-config');
        Route::patch('/corporate-soft-files/{corporateSoftFile}/toggle-status', [\App\Http\Controllers\Admin\CorporateSoftFileController::class, 'toggleStatus'])->name('corporate-soft-files.toggle-status');
        Route::get('/corporate-soft-files/{corporateSoftFile}/download', [\App\Http\Controllers\Admin\CorporateSoftFileController::class, 'download'])->name('corporate-soft-files.download');
        Route::get('/documents', [AdminDocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{document}/edit', [AdminDocumentController::class, 'edit'])->name('documents.edit');
        Route::put('/documents/{document}', [AdminDocumentController::class, 'update'])->name('documents.update');
        Route::get('/documents/{document}/download', [AdminDocumentController::class, 'download'])->name('documents.download');
        Route::post('/documents/bulk-download', [AdminDocumentController::class, 'bulkDownload'])->name('documents.bulk-download');
        Route::delete('/documents/bulk-delete', [AdminDocumentController::class, 'bulkDestroy'])->name('documents.bulk-delete');
        Route::delete('/documents/{document}', [AdminDocumentController::class, 'destroy'])->name('documents.destroy');

        // Trash (Sampah Dokumen) - Legacy admin aliases redirecting to general trash
        Route::get('/trash', fn() => redirect()->route('trash.index'))->name('trash.index');
        Route::post('/trash/bulk-restore', [\App\Http\Controllers\TrashController::class, 'bulkRestore'])->name('trash.bulk-restore');
        Route::delete('/trash/bulk-force-delete', [\App\Http\Controllers\TrashController::class, 'bulkForceDelete'])->name('trash.bulk-force-delete');
        Route::post('/trash/{id}/restore', [\App\Http\Controllers\TrashController::class, 'restore'])->name('trash.restore');
        Route::delete('/trash/{id}/force-delete', [\App\Http\Controllers\TrashController::class, 'forceDelete'])->name('trash.force-delete');
        Route::delete('/trash/empty', [\App\Http\Controllers\TrashController::class, 'emptyTrash'])->name('trash.empty');

        // Archive Configuration (Klasifikasi, Penomoran, JRA)
        Route::get('/archive-settings', [\App\Http\Controllers\Admin\ArchiveSettingController::class, 'index'])->name('archive-settings.index');
        Route::post('/archive-settings/classifications', [\App\Http\Controllers\Admin\ArchiveSettingController::class, 'storeClassification'])->name('archive-settings.classifications.store');
        Route::post('/archive-settings/numbering', [\App\Http\Controllers\Admin\ArchiveSettingController::class, 'storeScheme'])->name('archive-settings.numbering.store');
        Route::post('/archive-settings/numbering/preview', [\App\Http\Controllers\Admin\ArchiveSettingController::class, 'previewScheme'])->name('archive-settings.numbering.preview');
        Route::post('/archive-settings/retention', [\App\Http\Controllers\Admin\ArchiveSettingController::class, 'storeRetentionPolicy'])->name('archive-settings.retention.store');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

// ONLYOFFICE Routes (Accessed server-to-server by ONLYOFFICE Document Server)
Route::get('/onlyoffice/documents/{document}/versions/{version}/file', [\App\Http\Controllers\OnlyOfficeController::class, 'file'])->name('onlyoffice.file')->withTrashed();
Route::get('/onlyoffice/users/{user}/signature.png', [\App\Http\Controllers\OnlyOfficeController::class, 'signature'])->name('onlyoffice.signature');
Route::get('/onlyoffice/signatures/{signature}/image.png', [\App\Http\Controllers\OnlyOfficeController::class, 'signatureImage'])->name('onlyoffice.signature.image');
Route::get('/onlyoffice/signature-placeholder.png', [\App\Http\Controllers\OnlyOfficeController::class, 'signaturePlaceholder'])->name('onlyoffice.signature.placeholder');
Route::get('/onlyoffice/documents/{document}/qrcode', [\App\Http\Controllers\OnlyOfficeController::class, 'qrcode'])->name('onlyoffice.qrcode');
Route::match(['get', 'post'], '/onlyoffice/documents/{document}/callback', [\App\Http\Controllers\OnlyOfficeController::class, 'callback'])->name('onlyoffice.callback')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::get('/onlyoffice/templates/{template}/file', [\App\Http\Controllers\OnlyOfficeController::class, 'templateFile'])->name('onlyoffice.templates.file');
Route::match(['get', 'post'], '/onlyoffice/templates/{template}/callback', [\App\Http\Controllers\OnlyOfficeController::class, 'templateCallback'])->name('onlyoffice.templates.callback')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::get('/onlyoffice/corporate-soft-files/{corporateSoftFile}/file', [\App\Http\Controllers\OnlyOfficeController::class, 'corporateSoftFile'])->name('onlyoffice.corporate-soft-files.file');

// Share-token link access (Google Docs model)
Route::get('/shared/{token}', [DocumentShareController::class, 'accessByToken'])->name('documents.shared');
Route::get('/shared/{token}/download', [DocumentShareController::class, 'downloadByToken'])->name('documents.shared.download');
Route::post('/shared/{token}/export-pdf', [DocumentShareController::class, 'exportPdfByToken'])->name('documents.shared.export-pdf');
Route::get('/shared/{token}/file', [DocumentShareController::class, 'fileByToken'])->name('documents.shared.file');

require __DIR__.'/auth.php';

