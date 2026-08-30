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
                return response()->json(['success' => false, 'message' => 'ID dokumen diperlukan untuk memeriksa izin.'], 403);
            }

            $requestRecord = SignatureRequest::firstOrCreate([
                'requester_id' => Auth::id(),
                'target_user_id' => $user->id,
                'document_id' => $documentId,
            ], [
                'status' => 'pending',
                'requested_at' => now(),
            ]);

            if ($requestRecord->status !== 'approved') {
                if ($requestRecord->status === 'rejected') {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Permintaan penggunaan tanda tangan telah ditolak oleh pengguna.' 
                    ], 403);
                }

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
                    'message' => 'Permintaan penggunaan tanda tangan telah dikirim ke pengguna terkait. Tanda tangan disisipkan sebagai placeholder sementara.',
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

        return response()->json(['success' => false, 'message' => 'Tanda tangan belum dibuat.'], 404);
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
                return response()->json(['success' => false, 'message' => 'Silakan gambar atau unggah tanda tangan.'], 422);
            }
            return back()->with('error', 'Silakan gambar atau unggah tanda tangan.');
        }

        $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
        $imageData = null;
        $signatureType = 'canvas';

        if ($request->hasFile('signature_image')) {
            $file = $request->file('signature_image');
            $imageData = file_get_contents($file->getRealPath());
            if ($imageData === false) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Gagal membaca file gambar.'], 422);
                }
                return back()->with('error', 'Gagal membaca file gambar.');
            }
            $signatureType = 'upload';
        } else {
            $dataUrl = $request->input('signature_data');

            if (!preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $type)) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Format gambar tanda tangan tidak valid.'], 422);
                }
                return back()->with('error', 'Format gambar tanda tangan tidak valid.');
            }

            $imageData = substr($dataUrl, strpos($dataUrl, ',') + 1);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'Gagal memproses gambar tanda tangan.'], 422);
                }
                return back()->with('error', 'Gagal memproses gambar tanda tangan.');
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
                'message'        => 'Tanda tangan digital berhasil disimpan.',
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
                'message' => 'Tanda tangan digital berhasil dihapus.',
            ]);
        }

        return back()->with('status', 'signature-deleted');
    }

    /**
     * Get list of users with roles for Jodit editor toolbar signature dropdown.
     */
    public function availableUsers(): JsonResponse
    {
        $currentUser = Auth::user();

        $users = User::with('division')
            ->where('is_active', true)
            ->get()
            ->map(function ($u) use ($currentUser) {
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
                    'is_me' => $u->id === $currentUser->id,
                    'has_signature' => $u->hasSignature(),
                    'placeholder' => $u->id === $currentUser->id ? '[ttd.me]' : '[ttd:' . $u->name . ']',
                ];
            });

        return response()->json([
            'users' => $users,
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

        return back()->with('success', 'Permintaan tanda tangan telah disetujui. Dokumen sedang diperbarui di latar belakang.');
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
