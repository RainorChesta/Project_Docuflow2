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

            // Find an active (non-used, non-rejected) signature request, or create a new pending request
            $requestRecord = SignatureRequest::where('requester_id', Auth::id())
                ->where('target_user_id', $user->id)
                ->where('document_id', $documentId)
                ->where('is_used', false)
                ->whereIn('status', ['pending', 'approved'])
                ->latest()
                ->first();

            if (!$requestRecord) {
                $requestRecord = SignatureRequest::create([
                    'requester_id' => Auth::id(),
                    'target_user_id' => $user->id,
                    'document_id' => $documentId,
                    'status' => 'pending',
                    'is_used' => false,
                    'requested_at' => now(),
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
                    'message' => 'PERMINTAAN PENGGUNAAN TANDA TANGAN TELAH DIKIRIM KE PENGGUNA TERKAIT. TANDA TANGAN DISISIPKAN SEBAGAI PLACEHOLDER SEMENTARA.',
                ]);
            }
        }

        if ($user->hasSignature()) {
            $sig = $user->signature;
            $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
            $onlyOfficeUrl = $onlyOfficeService->getSignatureFileUrl($user);
            $token = $onlyOfficeUrl ? $onlyOfficeService->generateInsertImageToken($onlyOfficeUrl) : null;

            return response()->json([
                'success'    => true,
                'is_pending' => false,
                'url'        => $onlyOfficeUrl,
                'token'      => $token,
                'client_url' => asset('storage/' . $sig->file_path),
                'data_uri'   => 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($sig->file_path)),
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
        $request->validate([
            'signature_data' => ['nullable', 'string'], // base64 data url
            'signature_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'], // image file
        ]);

        $user = Auth::user();

        if (!$request->filled('signature_data') && !$request->hasFile('signature_image')) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'SILAKAN GAMBAR ATAU UNGGAH TANDA TANGAN.'], 422);
            }
            return back()->with('error', 'SILAKAN GAMBAR ATAU UNGGAH TANDA TANGAN.');
        }

        $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
        $imageData = null;
        $signatureType = 'canvas';

        if ($request->hasFile('signature_image')) {
            $file = $request->file('signature_image');
            $imageData = file_get_contents($file->getRealPath());
            if ($imageData === false) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'GAGAL MEMBACA FILE GAMBAR.'], 422);
                }
                return back()->with('error', 'GAGAL MEMBACA FILE GAMBAR.');
            }
            $signatureType = 'upload';
        } else {
            $dataUrl = $request->input('signature_data');

            if (!preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $type)) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'FORMAT GAMBAR TANDA TANGAN TIDAK VALID.'], 422);
                }
                return back()->with('error', 'FORMAT GAMBAR TANDA TANGAN TIDAK VALID.');
            }

            $imageData = substr($dataUrl, strpos($dataUrl, ',') + 1);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'GAGAL MEMPROSES GAMBAR TANDA TANGAN.'], 422);
                }
                return back()->with('error', 'GAGAL MEMPROSES GAMBAR TANDA TANGAN.');
            }
        }

        // Format to square and add transparency handling if needed (OnlyOfficeService handles it)
        $imageData = $onlyOfficeService->formatSquareSignature($imageData, 400, 24);

        $filename = 'signatures/sig_' . $user->id . '_' . time() . '.png';

        // Delete existing signature file if exists
        if ($user->signature && Storage::disk('public')->exists($user->signature->file_path)) {
            Storage::disk('public')->delete($user->signature->file_path);
        }

        Storage::disk('public')->put($filename, $imageData);

        $signature = Signature::updateOrCreate(
            ['user_id' => $user->id],
            [
                'file_path' => $filename,
                'signature_type' => $signatureType,
            ]
        );

        // updateOrCreate doesn't always bump updated_at when values are identical;
        // refresh to get the final DB state.
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
            ]);
        }

        return back()->with('status', 'signature-saved');
    }

    /**
     * Delete current user's signature.
     */
    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();

        if ($user->signature) {
            if (Storage::disk('public')->exists($user->signature->file_path)) {
                Storage::disk('public')->delete($user->signature->file_path);
            }
            $user->signature->delete();
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
                    'placeholder' => $isMe ? '[ttd.me]' : '[ttd:' . $u->name . ']',
                    'request_id' => $requestId,
                    'request_status' => $requestStatus,
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
        $onlyOfficeUrl = $onlyOfficeService->getSignatureFileUrl($targetUser);
        $token = $onlyOfficeUrl ? $onlyOfficeService->generateInsertImageToken($onlyOfficeUrl) : null;
        $sig = $targetUser->signature;

        return response()->json([
            'success'        => true,
            'message'        => 'Tanda tangan resmi berhasil dimuat.',
            'url'            => $onlyOfficeUrl,
            'token'          => $token,
            'client_url'     => asset('storage/' . $sig->file_path),
            'request_id'     => $signatureRequest->id,
            'target_user_id' => $targetUser->id,
            'target_user_name' => $targetUser->name,
        ]);
    }

    /**
     * Display signature requests list (incoming & outgoing).
     */
    public function requestsIndex()
    {
        $user = Auth::user();

        $incomingRequests = SignatureRequest::with(['requester', 'document'])
            ->where('target_user_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'incoming_page');

        $outgoingRequests = SignatureRequest::with(['targetUser', 'document'])
            ->where('requester_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'outgoing_page');

        return view('signature_requests.index', compact('incomingRequests', 'outgoingRequests'));
    }

    /**
     * Approve signature usage request.
     */
    public function approve(SignatureRequest $signatureRequest): RedirectResponse
    {
        if (Auth::id() !== $signatureRequest->target_user_id) {
            abort(403, 'Anda tidak berhak menyetujui permintaan ini.');
        }

        $signatureRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);
        
        $document = $signatureRequest->document;
        $version = $document?->displayVersion();
        $targetUser = $signatureRequest->targetUser;
        
        if ($document && $version && $targetUser) {
            $requestId = $signatureRequest->id;
            
            if ($targetUser->signature) {
                $signaturePath = Storage::disk('public')->path($targetUser->signature->file_path);
                
                // Process the signature synchronously using PHPWord
                $processor = app(\App\Services\DocumentProcessorService::class);
                $processor->processSignature($document, $version, $requestId, $signaturePath);
            }
        }

        $signatureRequest->loadMissing(['requester', 'document', 'targetUser']);
        if ($signatureRequest->requester && $signatureRequest->document) {
            $signatureRequest->requester->notify(
                new \App\Notifications\SignatureRequestApprovedNotification(
                    $signatureRequest,
                    $signatureRequest->document,
                    $signatureRequest->targetUser?->name ?? Auth::user()->name
                )
            );
        }

        return back()->with('success', 'Permintaan tanda tangan telah disetujui.');
    }

    /**
     * Reject signature usage request.
     */
    public function reject(Request $request, SignatureRequest $signatureRequest): RedirectResponse
    {
        if (Auth::id() !== $signatureRequest->target_user_id) {
            abort(403, 'Anda tidak berhak menolak permintaan ini.');
        }

        $signatureRequest->update([
            'status' => 'rejected',
            'rejected_reason' => $request->input('reason', 'Ditolak oleh pemilik tanda tangan.'),
            'responded_at' => now(),
        ]);

        return back()->with('success', 'Permintaan tanda tangan telah ditolak.');
    }
}
