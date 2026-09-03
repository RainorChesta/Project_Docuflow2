<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    /**
     * Return the user's saved signature data (for AJAX fetch).
     */
    public function show(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $documentId = $request->query('document_id');
        $user = $userId ? User::find($userId) : Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        // If requesting someone else's signature, check approval
        if ($user->id !== Auth::id()) {
            if (!$documentId) {
                return response()->json(['success' => false, 'message' => 'ID DOKUMEN DIPERLUKAN UNTUK MEMERIKSA IZIN.'], 403);
            }

            $signatureId = $request->query('signature_id') ?? $request->input('signature_id');
            $pageNumber = (int) $request->input('page_number', 1);
            $posX = ($request->filled('pos_x') || $request->has('pos_x')) && $request->input('pos_x') !== null && $request->input('pos_x') !== '' ? (float) $request->input('pos_x') : null;
            $posY = ($request->filled('pos_y') || $request->has('pos_y')) && $request->input('pos_y') !== null && $request->input('pos_y') !== '' ? (float) $request->input('pos_y') : null;
            $width = (float) $request->input('width', 40.0);
            $height = (float) $request->input('height', 25.0);
            $preset = $request->input('preset_position', 'bottom-right');

            // Find an active (non-used, non-rejected) signature request, or create a new pending request
            $requestRecord = SignatureRequest::where('requester_id', Auth::id())
                ->where('target_user_id', $user->id)
                ->where('document_id', $documentId)
                ->where('requested_signature_id', $signatureId)
                ->where('is_used', false)
                ->whereIn('status', ['pending', 'approved'])
                ->latest()
                ->first();

            if (!$requestRecord) {
                $requestRecord = SignatureRequest::create([
                    'requester_id' => Auth::id(),
                    'target_user_id' => $user->id,
                    'document_id' => $documentId,
                    'requested_signature_id' => $signatureId,
                    'status' => 'pending',
                    'is_used' => false,
                    'page_number' => $pageNumber,
                    'pos_x' => $posX,
                    'pos_y' => $posY,
                    'width' => $width,
                    'height' => $height,
                    'preset_position' => $preset,
                    'requested_at' => now(),
                ]);
            } else {
                $requestRecord->update([
                    'page_number' => $pageNumber,
                    'pos_x' => $posX,
                    'pos_y' => $posY,
                    'width' => $width,
                    'height' => $height,
                    'preset_position' => $preset,
                ]);
            }

            if ($requestRecord->status !== 'approved') {

                $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
                $internalBase = rtrim(config('onlyoffice.internal_url'), '/');
                $placeholderUrl = $internalBase . route('onlyoffice.signature.placeholder', [], false);
                $token = $placeholderUrl ? $onlyOfficeService->generateInsertImageToken($placeholderUrl) : null;

                return response()->json([
                    'success' => true,
                    'is_pending' => true,
                    'request_id' => $requestRecord->id,
                    'url' => $placeholderUrl,
                    'token' => $token,
                    'message' => 'PERMINTAAN PENGGUNAAN TANDA TANGAN TELAH DIKIRIM KE PENGGUNA TERKAIT.',
                ]);
            }
        }

        $signatureId = $request->query('signature_id');

        if ($user->hasSignature()) {
            $sig = $signatureId ? $user->signatures()->find($signatureId) : $user->signature;
            
            if (!$sig) {
                 return response()->json(['success' => false, 'message' => 'TANDA TANGAN TIDAK DITEMUKAN.'], 404);
            }

            $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
            $onlyOfficeUrl = $onlyOfficeService->getSignatureFileUrlForSignature($sig);
            $token = $onlyOfficeUrl ? $onlyOfficeService->generateInsertImageToken($onlyOfficeUrl) : null;
            $rawBytes = Storage::disk('public')->get($sig->file_path);
            $trimmedBytes = $onlyOfficeService->trimSignatureImage($rawBytes);

            return response()->json([
                'success'    => true,
                'is_pending' => false,
                'url'        => $onlyOfficeUrl,
                'token'      => $token,
                'client_url' => asset('storage/' . $sig->file_path),
                'data_uri'   => 'data:image/png;base64,' . base64_encode($trimmedBytes),
                'updated_at' => $sig->updated_at->toISOString(),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'TANDA TANGAN BELUM DIBUAT.'], 404);
    }

    /**
     * Save or update current user's digital signature drawn on profile canvas.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'signature_data' => ['nullable', 'string'],
            'signature_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'type' => ['nullable', 'string', 'in:original,company_stamp'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ], [
            'signature_image.max' => __('Ukuran file tanda tangan tidak boleh lebih dari 2MB.'),
            'signature_image.image' => __('File harus berupa gambar.'),
            'signature_image.mimes' => __('Format file harus berupa PNG, JPG, atau JPEG.'),
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();
        $type = $request->input('type', 'original');
        $companyId = $request->input('company_id');

        if ($type === 'company_stamp') {
            if (!$user->hasSignature()) {
                if ($request->wantsJson()) return response()->json(['success' => false, 'message' => 'HARUS MEMBUAT TANDA TANGAN ORIGINAL TERLEBIH DAHULU.'], 422);
                return back()->with('error', 'HARUS MEMBUAT TANDA TANGAN ORIGINAL TERLEBIH DAHULU.');
            }
            if (!$request->hasFile('signature_image')) {
                if ($request->wantsJson()) return response()->json(['success' => false, 'message' => 'TANDA TANGAN PERUSAHAAN HARUS BERUPA UNGGAHAN GAMBAR.'], 422);
                return back()->with('error', 'TANDA TANGAN PERUSAHAAN HARUS BERUPA UNGGAHAN GAMBAR.');
            }
            if (!$companyId || !$user->companies->contains('id', $companyId)) {
                if ($request->wantsJson()) return response()->json(['success' => false, 'message' => 'PERUSAHAAN TIDAK VALID ATAU ANDA TIDAK MEMILIKI AKSES.'], 422);
                return back()->with('error', 'PERUSAHAAN TIDAK VALID ATAU ANDA TIDAK MEMILIKI AKSES.');
            }
        } else {
            $companyId = null;
        }

        if (!$request->filled('signature_data') && !$request->hasFile('signature_image')) {
            if ($request->wantsJson()) return response()->json(['success' => false, 'message' => 'SILAKAN GAMBAR ATAU UNGGAH TANDA TANGAN.'], 422);
            return back()->with('error', 'SILAKAN GAMBAR ATAU UNGGAH TANDA TANGAN.');
        }

        $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
        $imageData = null;
        $createdVia = 'canvas';

        if ($request->hasFile('signature_image')) {
            $file = $request->file('signature_image');
            $imageData = file_get_contents($file->getRealPath());
            if ($imageData === false) {
                if ($request->wantsJson()) return response()->json(['success' => false, 'message' => 'GAGAL MEMBACA FILE GAMBAR.'], 422);
                return back()->with('error', 'GAGAL MEMBACA FILE GAMBAR.');
            }
            $createdVia = 'upload';
        } else {
            $dataUrl = $request->input('signature_data');
            if (!preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $matches)) {
                if ($request->wantsJson()) return response()->json(['success' => false, 'message' => 'FORMAT GAMBAR TANDA TANGAN TIDAK VALID.'], 422);
                return back()->with('error', 'FORMAT GAMBAR TANDA TANGAN TIDAK VALID.');
            }
            $imageData = substr($dataUrl, strpos($dataUrl, ',') + 1);
            $imageData = base64_decode($imageData);
            if ($imageData === false) {
                if ($request->wantsJson()) return response()->json(['success' => false, 'message' => 'GAGAL MEMPROSES GAMBAR TANDA TANGAN.'], 422);
                return back()->with('error', 'GAGAL MEMPROSES GAMBAR TANDA TANGAN.');
            }
        }

        $imageData = $onlyOfficeService->trimSignatureImage($imageData);
        $filename = 'signatures/sig_' . $user->id . '_' . time() . '_' . uniqid() . '.png';

        if ($type === 'original') {
            $existing = $user->signature;
            if ($existing && Storage::disk('public')->exists($existing->file_path)) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $signature = Signature::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'original'],
                ['file_path' => $filename, 'created_via' => $createdVia, 'company_id' => null]
            );
        } else {
            $signature = Signature::create([
                'user_id' => $user->id,
                'file_path' => $filename,
                'type' => 'company_stamp',
                'company_id' => $companyId,
                'created_via' => $createdVia,
            ]);
        }
        
        Storage::disk('public')->put($filename, $imageData);
        $signature->refresh();

        if ($request->wantsJson()) {
            $onlyOfficeUrl = $onlyOfficeService->getSignatureFileUrl($user);
            $token = $onlyOfficeUrl ? $onlyOfficeService->generateInsertImageToken($onlyOfficeUrl) : null;

            return response()->json([
                'success'        => true,
                'message'        => 'TANDA TANGAN DIGITAL BERHASIL DISIMPAN.',
                'url'            => asset('storage/' . $signature->file_path),
                'onlyoffice_url' => $onlyOfficeUrl,
                'token'          => $token,
                'updated_at'     => $signature->updated_at->toISOString(),
                'signature'      => $signature,
            ]);
        }

        return back()->with('status', 'signature-saved');
    }

    /**
     * Get all signatures for a user.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $user = $userId ? User::find($userId) : Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan.'], 404);
        }

        // Only return companies the user still has access to
        $companyIds = $user->allCompanyIds();
        $signatures = $user->signatures()->with('company')->get()->filter(function ($sig) use ($companyIds) {
            if ($sig->type === 'original') return true;
            return in_array($sig->company_id, $companyIds);
        })->values();

        return response()->json([
            'success' => true,
            'signatures' => $signatures,
        ]);
    }

    /**
     * Delete user's signature.
     */
    public function destroy(Request $request, ?Signature $signature = null): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if (!$signature) {
            $signature = $user->signature;
        }

        if ($signature && $signature->user_id === $user->id) {
            if (Storage::disk('public')->exists($signature->file_path)) {
                Storage::disk('public')->delete($signature->file_path);
            }
            $signature->delete();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'TANDA TANGAN DIGITAL BERHASIL DIHAPUS.',
            ]);
        }

        return back()->with('status', 'signature-deleted');
    }

    /**
     * Get list of users with roles and signature request status for a document.
     */
    public function availableUsers(Request $request): JsonResponse
    {
        $currentUser = Auth::user();
        $documentId = $request->query('document_id');

        $requestsGrouped = collect();
        if ($documentId) {
            $requestsGrouped = SignatureRequest::where('document_id', $documentId)
                ->where('requester_id', $currentUser->id)
                ->latest()
                ->get()
                ->groupBy('target_user_id');
        }

        $availableToReplaceCount = 0;

        $users = User::with('division')
            ->where('is_active', true)
            ->get()
            ->map(function ($u) use ($currentUser, $requestsGrouped, &$availableToReplaceCount) {
                $isMe = $u->id === $currentUser->id;
                $userRequests = $requestsGrouped->get($u->id, collect());

                // Find approved and unused request if available
                $approvedAvailableReq = $userRequests->first(fn($r) => $r->isApproved() && !$r->is_used);
                $pendingReq = $userRequests->first(fn($r) => $r->isPending());
                $latestReq = $userRequests->first();

                $requestStatus = 'none'; // none, pending, approved, rejected, used
                $requestId = null;
                $isAvailableToReplace = false;
                $availableCredits = 0;

                if ($isMe) {
                    $requestStatus = 'me';
                } elseif ($userRequests->isNotEmpty()) {
                    $availableCredits = $userRequests->filter(fn($r) => $r->isApproved() && !$r->is_used)->count();
                    $availableToReplaceCount += $availableCredits;

                    if ($approvedAvailableReq) {
                        $requestId = $approvedAvailableReq->id;
                        $requestStatus = 'approved';
                        $isAvailableToReplace = true;
                    } elseif ($pendingReq) {
                        $requestId = $pendingReq->id;
                        $requestStatus = 'pending';
                    } elseif ($latestReq && $latestReq->is_used) {
                        $requestId = $latestReq->id;
                        $requestStatus = 'used';
                    } elseif ($latestReq && $latestReq->isRejected()) {
                        $requestId = $latestReq->id;
                        $requestStatus = 'rejected';
                    }
                }

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => match($u->system_role) {
                        'admin' => 'Admin',
                        'head' => 'Kepala Divisi',
                        default => 'Staff',
                    },
                    'division' => $u->division ? $u->division->name : 'Umum',
                    'is_me' => $isMe,
                    'has_signature' => $u->hasSignature(),
                    'signatures' => $u->signatures()->with('company')->get()->map(function($sig) {
                        return [
                            'id' => $sig->id,
                            'type' => $sig->type,
                            'company_name' => $sig->company ? $sig->company->name : null,
                        ];
                    }),
                    'placeholder' => $isMe ? '[ttd.me]' : '[ttd:' . $u->name . ']',
                    'request_id' => $requestId,
                    'request_status' => $requestStatus,
                    'rejected_reason' => ($latestReq && $latestReq->isRejected()) ? $latestReq->rejected_reason : null,
                    'is_available_to_replace' => $isAvailableToReplace,
                    'available_credits' => $availableCredits,
                ];
            });

        return response()->json([
            'users' => $users,
            'available_to_replace_count' => $availableToReplaceCount,
        ]);
    }

    /**
     * Consume an approved signature replacement (1-to-1 rule).
     */
    public function consume(Request $request, SignatureRequest $signatureRequest): JsonResponse
    {
        $currentUser = Auth::user();

        if ($signatureRequest->requester_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak untuk menggunakan persetujuan tanda tangan ini.',
            ], 403);
        }

        if ($signatureRequest->is_used) {
            return response()->json([
                'success' => false,
                'message' => 'Persetujuan tanda tangan ini sudah pernah digunakan (hanya berlaku 1x penggantian).',
            ], 422);
        }

        if (!$signatureRequest->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Permintaan tanda tangan belum disetujui.',
            ], 422);
        }

        $targetUser = $signatureRequest->targetUser;
        if (!$targetUser || !$targetUser->hasSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Tanda tangan pengguna tidak ditemukan.',
            ], 404);
        }

        $signatureRequest->update([
            'is_used' => true,
            'used_at' => now(),
        ]);

        $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
        $sig = $signatureRequest->requestedSignature ?? $targetUser->signature;

        if (!$sig) {
            return response()->json([
                'success' => false,
                'message' => 'Tanda tangan pengguna tidak ditemukan.',
            ], 404);
        }

        $onlyOfficeUrl = $onlyOfficeService->getSignatureFileUrlForSignature($sig);
        $token = $onlyOfficeUrl ? $onlyOfficeService->generateInsertImageToken($onlyOfficeUrl) : null;

        $document = $signatureRequest->document;
        $version = $document?->displayVersion();
        $isPdf = false;
        if ($version && (($version->file_path && str_ends_with(strtolower($version->file_path), '.pdf')) || ($version->file_mime && str_contains(strtolower($version->file_mime), 'pdf')))) {
            $isPdf = true;
            if ($sig && $sig->file_path) {
                $signaturePath = Storage::disk('public')->path($sig->file_path);
                $processor = app(\App\Services\DocumentProcessorService::class);
                $processor->processSignature($document, $version, $signatureRequest->id, $signaturePath, $signatureRequest);
            }
        }

        return response()->json([
            'success'        => true,
            'message'        => $isPdf ? 'Tanda tangan resmi berhasil dibubuhkan pada dokumen PDF.' : 'Tanda tangan resmi berhasil dimuat.',
            'url'            => $onlyOfficeUrl,
            'token'          => $token,
            'is_pdf'         => $isPdf,
            'client_url'     => asset('storage/' . $sig->file_path),
            'request_id'     => $signatureRequest->id,
            'target_user_id' => $targetUser->id,
            'target_user_name' => $targetUser->name,
        ]);
    }

    /**
     * Direct stamping of current user's own signature onto a PDF document.
     */
    public function stampPdfSignature(Request $request, \App\Models\Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $currentUser = Auth::user();
        if (!$currentUser->hasSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum memiliki tanda tangan digital tersimpan.',
            ], 422);
        }

        $signatureId = $request->input('signature_id');
        $sig = $signatureId ? $currentUser->signatures()->find($signatureId) : $currentUser->signature;

        if (!$sig || !$sig->file_path || !Storage::disk('public')->exists($sig->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanda tangan / stempel digital tidak ditemukan.',
            ], 422);
        }

        $version = $document->displayVersion();
        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Versi dokumen tidak ditemukan.',
            ], 404);
        }

        $isPdf = ($version->file_path && str_ends_with(strtolower($version->file_path), '.pdf')) ||
                 ($version->file_mime && str_contains(strtolower($version->file_mime), 'pdf'));

        if (!$isPdf) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur ini khusus untuk dokumen format PDF.',
            ], 422);
        }

        $pageNumber = (int) $request->input('page_number', 1);
        $preset = $request->input('preset_position', 'bottom-right');
        $posX = ($request->filled('pos_x') || $request->has('pos_x')) && $request->input('pos_x') !== null && $request->input('pos_x') !== '' ? (float) $request->input('pos_x') : null;
        $posY = ($request->filled('pos_y') || $request->has('pos_y')) && $request->input('pos_y') !== null && $request->input('pos_y') !== '' ? (float) $request->input('pos_y') : null;
        $width = (float) $request->input('width', 40.0);
        $height = (float) $request->input('height', 25.0);

        $signaturePath = Storage::disk('public')->path($sig->file_path);
        $pdfProcessor = app(\App\Services\PdfSignatureProcessorService::class);

        $result = $pdfProcessor->processPdfSignature(
            $document,
            $version,
            $signaturePath,
            $pageNumber,
            $posX,
            $posY,
            $width,
            $height,
            $preset
        );

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => $sig->type === 'company_stamp' ? 'Stempel perusahaan berhasil dibubuhkan pada dokumen PDF.' : 'Tanda tangan Anda berhasil dibubuhkan pada dokumen PDF.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal membubuhkan tanda tangan pada PDF.',
        ], 500);
    }

    /**
     * Direct stamping of document verification QR Code onto a PDF document.
     */
    public function stampPdfQrCode(Request $request, \App\Models\Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $version = $document->displayVersion();
        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Versi dokumen tidak ditemukan.',
            ], 404);
        }

        $isPdf = ($version->file_path && str_ends_with(strtolower($version->file_path), '.pdf')) ||
                 ($version->file_mime && str_contains(strtolower($version->file_mime), 'pdf'));

        if (!$isPdf) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur ini khusus untuk dokumen format PDF.',
            ], 422);
        }

        $pageNumber = (int) $request->input('page_number', 1);
        $preset = $request->input('preset_position', 'bottom-right');
        $posX = ($request->filled('pos_x') || $request->has('pos_x')) && $request->input('pos_x') !== null && $request->input('pos_x') !== '' ? (float) $request->input('pos_x') : null;
        $posY = ($request->filled('pos_y') || $request->has('pos_y')) && $request->input('pos_y') !== null && $request->input('pos_y') !== '' ? (float) $request->input('pos_y') : null;
        $width = (float) $request->input('width', 30.0);
        $height = (float) $request->input('height', 30.0);

        $qrCodeService = app(\App\Services\QrCodeService::class);
        $qrPngBytes = $qrCodeService->pngBytes($qrCodeService->qrcodeUrl($document));

        $tempQrPath = storage_path('app/temp_qr_' . uniqid() . '.png');
        file_put_contents($tempQrPath, $qrPngBytes);

        $pdfProcessor = app(\App\Services\PdfSignatureProcessorService::class);

        $result = $pdfProcessor->processPdfSignature(
            $document,
            $version,
            $tempQrPath,
            $pageNumber,
            $posX,
            $posY,
            $width,
            $height,
            $preset
        );

        @unlink($tempQrPath);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'QR Code verifikasi berhasil dibubuhkan pada dokumen PDF.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal membubuhkan QR Code pada PDF.',
        ], 500);
    }

    /**
     * Revert / Undo a stamped signature on a PDF document back to its original state.
     */
    public function revertPdfSignature(Request $request, \App\Models\Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $version = $document->displayVersion();
        if (!$version) {
            return response()->json([
                'success' => false,
                'message' => 'Versi dokumen tidak ditemukan.',
            ], 404);
        }

        $pdfProcessor = app(\App\Services\PdfSignatureProcessorService::class);

        if (!$pdfProcessor->hasOriginalBackup($version)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan salinan asli dokumen sebelum tanda tangan dibubuhkan.',
            ], 422);
        }

        $result = $pdfProcessor->revertPdfSignature($document, $version);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => 'Tanda tangan berhasil dihapus dan dokumen PDF dikembalikan ke versi semula.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengembalikan dokumen PDF ke versi semula.',
        ], 500);
    }

    /**
     * Display signature requests list (incoming & outgoing) with search, filter, and pagination.
     */
    public function requestsIndex(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        // Base query for incoming requests
        $incomingBaseQuery = SignatureRequest::with(['requester', 'document.branch', 'document.company', 'document.division'])
            ->where('target_user_id', $user->id);

        // Counts for tab badges
        $counts = [
            'all'      => (clone $incomingBaseQuery)->count(),
            'pending'  => (clone $incomingBaseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $incomingBaseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $incomingBaseQuery)->where('status', 'rejected')->count(),
        ];

        // Apply status filter
        $incomingQuery = clone $incomingBaseQuery;
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $incomingQuery->where('status', $status);
        }

        // Apply search
        if (strlen($search) >= 2) {
            $incomingQuery->where(function ($sq) use ($search) {
                $sq->whereHas('document', function ($dq) use ($search) {
                    $dq->where('title', 'like', "%{$search}%")
                       ->orWhere('document_number', 'like', "%{$search}%");
                })->orWhereHas('requester', function ($rq) use ($search) {
                    $rq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // Order pending first, then latest
        $incomingRequests = $incomingQuery
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->latest('requested_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'incoming_page')
            ->withQueryString();

        // Outgoing requests
        $outgoingRequests = SignatureRequest::with(['targetUser', 'document'])
            ->where('requester_id', $user->id)
            ->latest('requested_at')
            ->latest('id')
            ->paginate(10, ['*'], 'outgoing_page')
            ->withQueryString();

        return view('signature_requests.index', compact(
            'incomingRequests',
            'outgoingRequests',
            'status',
            'search',
            'perPage',
            'counts'
        ));
    }

    /**
     * Approve signature usage request.
     */
    public function approve(SignatureRequest $signatureRequest): RedirectResponse
    {
        if (Auth::id() !== $signatureRequest->target_user_id) {
            abort(403, 'Anda tidak berhak menyetujui permintaan ini.');
        }

        $this->executeApproval($signatureRequest);

        return back()->with('success', __('Permintaan tanda tangan telah disetujui.'));
    }

    /**
     * Bulk approve selected signature requests.
     */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $requestIds = $request->input('request_ids', []);

        if (is_string($requestIds)) {
            $requestIds = explode(',', $requestIds);
        }

        $requestIds = array_filter(array_map('intval', (array) $requestIds));

        if (empty($requestIds)) {
            return back()->with('error', __('Pilih setidaknya satu permintaan tanda tangan untuk disetujui.'));
        }

        $requests = SignatureRequest::whereIn('id', $requestIds)
            ->where('target_user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        if ($requests->isEmpty()) {
            return back()->with('error', __('Tidak ada permintaan tertunda yang valid untuk disetujui.'));
        }

        $count = 0;
        foreach ($requests as $sigReq) {
            $this->executeApproval($sigReq, $user);
            $count++;
        }

        return back()->with('success', __(':count permintaan tanda tangan berhasil disetujui sekaligus.', ['count' => $count]));
    }

    /**
     * Approve all pending signature requests for current user.
     */
    public function approveAllPending(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $requests = SignatureRequest::where('target_user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        if ($requests->isEmpty()) {
            return back()->with('error', __('Tidak ada permintaan tanda tangan pending yang perlu disetujui.'));
        }

        $count = 0;
        foreach ($requests as $sigReq) {
            $this->executeApproval($sigReq, $user);
            $count++;
        }

        return back()->with('success', __('Semua :count permintaan tanda tangan pending berhasil disetujui.', ['count' => $count]));
    }

    /**
     * Reject signature usage request.
     */
    public function reject(Request $request, SignatureRequest $signatureRequest): RedirectResponse
    {
        if (Auth::id() !== $signatureRequest->target_user_id) {
            abort(403, __('Anda tidak berhak menolak permintaan ini.'));
        }

        $reason = $request->input('reason', __('Ditolak oleh pemilik tanda tangan.'));
        $this->executeRejection($signatureRequest, $reason);

        return back()->with('success', __('Permintaan tanda tangan telah ditolak.'));
    }

    /**
     * Bulk reject selected signature requests.
     */
    public function bulkReject(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $requestIds = $request->input('request_ids', []);

        if (is_string($requestIds)) {
            $requestIds = explode(',', $requestIds);
        }

        $requestIds = array_filter(array_map('intval', (array) $requestIds));

        if (empty($requestIds)) {
            return back()->with('error', __('Pilih setidaknya satu permintaan tanda tangan untuk ditolak.'));
        }

        $reason = $request->input('reason', __('Ditolak massal oleh pemilik tanda tangan.'));

        $requests = SignatureRequest::whereIn('id', $requestIds)
            ->where('target_user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        if ($requests->isEmpty()) {
            return back()->with('error', __('Tidak ada permintaan tertunda yang valid untuk ditolak.'));
        }

        $count = 0;
        foreach ($requests as $sigReq) {
            $this->executeRejection($sigReq, $reason, $user);
            $count++;
        }

        return back()->with('success', __(':count permintaan tanda tangan berhasil ditolak.', ['count' => $count]));
    }

    /**
     * Internal helper to execute a single approval.
     */
    protected function executeApproval(SignatureRequest $signatureRequest, ?User $actor = null): void
    {
        $actor = $actor ?? Auth::user();

        $signatureRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);
        
        $document = $signatureRequest->document;
        $version = $document?->displayVersion();
        $targetUser = $signatureRequest->targetUser ?? $actor;
        
        if ($document && $version && $targetUser) {
            $requestId = $signatureRequest->id;
            
            if ($targetUser->signature) {
                $signaturePath = Storage::disk('public')->path($targetUser->signature->file_path);
                
                // Process the signature synchronously using PHPWord or FPDI
                $processor = app(\App\Services\DocumentProcessorService::class);
                $processor->processSignature($document, $version, $requestId, $signaturePath, $signatureRequest);
            }
        }

        $signatureRequest->loadMissing(['requester', 'document', 'targetUser']);
        if ($signatureRequest->requester && $signatureRequest->document) {
            $signatureRequest->requester->notify(
                new \App\Notifications\SignatureRequestApprovedNotification(
                    $signatureRequest,
                    $signatureRequest->document,
                    $signatureRequest->targetUser?->name ?? $actor->name
                )
            );
        }
    }

    /**
     * Internal helper to execute a single rejection.
     */
    protected function executeRejection(SignatureRequest $signatureRequest, ?string $reason = null, ?User $actor = null): void
    {
        $actor = $actor ?? Auth::user();
        $reason = $reason ?: __('Ditolak oleh pemilik tanda tangan.');

        $signatureRequest->update([
            'status' => 'rejected',
            'rejected_reason' => $reason,
            'responded_at' => now(),
        ]);

        $signatureRequest->loadMissing(['requester', 'document', 'targetUser']);
        if ($signatureRequest->requester && $signatureRequest->document) {
            $signatureRequest->requester->notify(
                new \App\Notifications\SignatureRequestRejectedNotification(
                    $signatureRequest,
                    $signatureRequest->document,
                    $signatureRequest->targetUser?->name ?? $actor->name,
                    $reason
                )
            );
        }
    }
}
