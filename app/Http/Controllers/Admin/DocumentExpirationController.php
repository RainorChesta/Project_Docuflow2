<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Setting;
use App\Models\UnitKerja;
use App\Notifications\DocumentExpiredNotification;
use App\Notifications\DocumentExpiringWarningNotification;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentExpirationController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display the document expiration notifications management dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $defaultReminderDays = (int) Setting::get('document_expiration_reminder_days', 30);

        // Filters
        $query = Document::withoutTrashed()
            ->whereNotNull('expiration_date')
            ->with(['owner', 'unitKerja', 'branch.company', 'documentType', 'currentVersion']);

        // Search filter
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhereHas('owner', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Branch filter
        if ($branchId = $request->input('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        // Unit Kerja filter
        if ($unitKerjaId = $request->input('unit_kerja_id')) {
            $query->where('unit_kerja_id', $unitKerjaId);
        }

        // Document Type filter
        if ($documentTypeId = $request->input('document_type_id')) {
            $query->where('document_type_id', $documentTypeId);
        }

        // Status filter
        $status = $request->input('status', 'all');
        if ($status === 'expired') {
            $query->where(function ($q) {
                $q->where('is_expired', true)
                    ->orWhereDate('expiration_date', '<', now()->toDateString());
            });
        } elseif ($status === 'critical') {
            $query->where('is_expired', false)
                ->whereDate('expiration_date', '>=', now()->toDateString())
                ->whereDate('expiration_date', '<=', now()->addDays(7)->toDateString());
        } elseif ($status === 'expiring_soon') {
            $query->where('is_expired', false)
                ->whereDate('expiration_date', '>=', now()->toDateString())
                ->where(function ($q) use ($defaultReminderDays) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('expiration_reminder_days')
                            ->whereRaw('expiration_date <= DATE_ADD(CURRENT_DATE, INTERVAL expiration_reminder_days DAY)');
                    })->orWhere(function ($sub) use ($defaultReminderDays) {
                        $sub->whereNull('expiration_reminder_days')
                            ->whereDate('expiration_date', '<=', now()->addDays($defaultReminderDays)->toDateString());
                    });
                });
        } elseif ($status === 'safe') {
            $query->where('is_expired', false)
                ->where(function ($q) use ($defaultReminderDays) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('expiration_reminder_days')
                            ->whereRaw('expiration_date > DATE_ADD(CURRENT_DATE, INTERVAL expiration_reminder_days DAY)');
                    })->orWhere(function ($sub) use ($defaultReminderDays) {
                        $sub->whereNull('expiration_reminder_days')
                            ->whereDate('expiration_date', '>', now()->addDays($defaultReminderDays)->toDateString());
                    });
                });
        }

        // Sorting
        $sort = $request->input('sort', 'expiration_asc');
        match ($sort) {
            'expiration_desc' => $query->orderByDesc('expiration_date'),
            'title_asc'       => $query->orderBy('title', 'asc'),
            'title_desc'      => $query->orderBy('title', 'desc'),
            'created_desc'    => $query->orderByDesc('created_at'),
            default           => $query->orderBy('expiration_date', 'asc'),
        };

        $documents = $query->paginate(15)->withQueryString();

        $branches = Branch::orderBy('name')->get();
        $unitKerjas = UnitKerja::orderBy('nama_unit_kerja')->get();
        $documentTypes = DocumentType::orderBy('name')->get();

        return view('admin.expirations.index', compact(
            'documents',
            'defaultReminderDays',
            'branches',
            'unitKerjas',
            'documentTypes',
            'status',
            'sort'
        ));
    }

    /**
     * Update the default expiration reminder period (global setting).
     */
    public function updateDefaultReminder(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'default_reminder_days' => 'required|integer|min:1|max:365',
        ], [
            'default_reminder_days.required' => __('Masa pengingat default wajib diisi.'),
            'default_reminder_days.min' => __('Masa pengingat minimal 1 hari.'),
            'default_reminder_days.max' => __('Masa pengingat maksimal 365 hari (1 tahun).'),
        ]);

        $days = (int) $validated['default_reminder_days'];
        Setting::set('document_expiration_reminder_days', $days);

        $this->auditService->log(
            auth()->user(),
            'setting.updated',
            'setting',
            0,
            ['key' => 'document_expiration_reminder_days', 'value' => $days]
        );

        return back()->with('success', __('Default periode pengingat notifikasi kedaluwarsa berhasil diubah menjadi :days hari.', ['days' => $days]));
    }

    /**
     * Update reminder notification settings or expiration date for a specific document.
     */
    public function updateDocumentReminder(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'expiration_reminder_days' => 'nullable|integer|min:1|max:365',
            'use_default_reminder'     => 'nullable|boolean',
        ], [
            'expiration_reminder_days.min' => __('Jumlah hari pengingat minimal 1 hari.'),
            'expiration_reminder_days.max' => __('Jumlah hari pengingat maksimal 365 hari.'),
        ]);

        $useDefault = $request->boolean('use_default_reminder') || empty($validated['expiration_reminder_days']);
        $reminderDays = $useDefault ? null : (int) $validated['expiration_reminder_days'];

        // Determine target expiration date from the document
        $targetExpDate = $document->expiration_date ? $document->expiration_date->copy()->startOfDay() : null;

        // Validate that custom reminder period does not exceed the document's actual expiration period
        if (!$useDefault && !is_null($reminderDays)) {
            if (!$targetExpDate) {
                $errorMsg = __('Dokumen permanen tanpa tanggal kedaluwarsa tidak dapat memiliki pengingat khusus.');
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'errors' => ['expiration_reminder_days' => [$errorMsg]],
                    ], 422);
                }
                return back()->withErrors(['expiration_reminder_days' => $errorMsg])->withInput();
            }

            $daysRemaining = (int) now()->startOfDay()->diffInDays($targetExpDate, false);
            if ($daysRemaining <= 0) {
                $errorMsg = __('Dokumen telah kedaluwarsa atau berakhir hari ini. Pengingat tidak dapat diatur melebihi sisa masa berlaku.');
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'errors' => ['expiration_reminder_days' => [$errorMsg]],
                    ], 422);
                }
                return back()->withErrors(['expiration_reminder_days' => $errorMsg])->withInput();
            }

            if ($reminderDays > $daysRemaining) {
                $errorMsg = __('Periode pengingat (:days hari) tidak boleh melebihi sisa masa berlaku dokumen (:max hari).', [
                    'days' => $reminderDays,
                    'max' => $daysRemaining,
                ]);
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'errors' => ['expiration_reminder_days' => [$errorMsg]],
                    ], 422);
                }
                return back()->withErrors(['expiration_reminder_days' => $errorMsg])->withInput();
            }
        }

        $document->expiration_reminder_days = $reminderDays;
        $document->save();

        $this->auditService->log(
            auth()->user(),
            'document.expiration_setting_updated',
            'document',
            $document->id,
            [
                'document_id' => $document->id,
                'reminder_days' => $reminderDays,
            ]
        );

        $message = __('Pengaturan pengingat untuk dokumen ":title" berhasil disimpan.', ['title' => $document->title]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'document' => [
                    'id' => $document->id,
                    'expiration_date' => $document->expiration_date?->format('d M Y'),
                    'effective_reminder_days' => $document->effective_reminder_days,
                    'days_remaining' => $document->daysUntilExpiration(),
                    'is_expired' => $document->isExpired(),
                ]
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Manually trigger/send a reminder notification to the creator/owner of the document.
     */
    public function notify(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('admin');

        $owner = $document->owner;
        if (!$owner) {
            $errMsg = __('Dokumen tidak memiliki pemilik / pembuat yang valid.');
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $errMsg], 422);
            }
            return back()->with('error', $errMsg);
        }

        if (!$document->hasExpiration()) {
            $errMsg = __('Dokumen ini bersifat permanen dan tidak memiliki tanggal kedaluwarsa.');
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $errMsg], 422);
            }
            return back()->with('error', $errMsg);
        }

        $days = $document->daysUntilExpiration() ?? 0;

        if ($document->isExpired() || $days < 0) {
            $document->updateQuietly([
                'is_expired' => true,
                'is_expiration_notified' => true,
                'expiration_notif_status' => 'expired',
                'expiration_notified_at' => now(),
            ]);
            $owner->notify(new DocumentExpiredNotification($document));
        } else {
            $statusKey = $days <= 1 ? '1day' : ($days <= 7 ? '7days' : 'manual');
            $document->updateQuietly([
                'is_expiration_notified' => true,
                'expiration_notif_status' => $statusKey,
                'expiration_notified_at' => now(),
            ]);
            $owner->notify(new DocumentExpiringWarningNotification($document, $days, $statusKey));
        }

        $this->auditService->log(
            auth()->user(),
            'document.expiration_notification_sent',
            'document',
            $document->id,
            [
                'document_id' => $document->id,
                'owner_id' => $owner->id,
                'days_remaining' => $days,
            ]
        );

        $successMsg = __('Notifikasi pengingat kedaluwarsa berhasil dikirimkan kepada :name (:email).', [
            'name' => $owner->name,
            'email' => $owner->email,
        ]);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'notified_at' => now()->translatedFormat('d M Y, H:i'),
            ]);
        }

        return back()->with('success', $successMsg);
    }
}
