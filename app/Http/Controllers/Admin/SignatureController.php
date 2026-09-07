<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Signature;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OnlyOfficeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SignatureController extends Controller
{
    /**
     * Tampilkan manajemen tanda tangan & stempel terkelompok per pengguna.
     */
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $query = User::with(['signatures.company', 'companies', 'branches', 'divisions']);

        // Filter pencarian: nama, email, nip
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        // Filter status tanda tangan / stempel
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'has_original') {
                $query->whereHas('signatures', fn($q) => $q->where('type', 'original'));
            } elseif ($status === 'has_stamp') {
                $query->whereHas('signatures', fn($q) => $q->where('type', 'company_stamp'));
            } elseif ($status === 'missing_original') {
                $query->whereDoesntHave('signatures', fn($q) => $q->where('type', 'original'));
            } elseif ($status === 'no_signature') {
                $query->whereDoesntHave('signatures');
            }
        }

        // Filter berdasarkan perusahaan afiliasi pengguna
        if ($request->filled('company_id')) {
            $companyId = $request->company_id;
            $query->whereHas('companies', fn($q) => $q->where('companies.id', $companyId));
        }

        $users = $query->orderBy('name')->paginate(15)->appends($request->query());
        $allCompanies = Company::orderBy('name')->get();
        $allUsers = User::orderBy('name')->get(['id', 'name', 'email']);

        // Ringkasan metrik statistik
        $stats = [
            'total_users' => User::count(),
            'with_original' => User::whereHas('signatures', fn($q) => $q->where('type', 'original'))->count(),
            'with_stamps' => User::whereHas('signatures', fn($q) => $q->where('type', 'company_stamp'))->count(),
            'missing_all' => User::whereDoesntHave('signatures')->count(),
        ];

        return view('admin.signatures.index', compact('users', 'allCompanies', 'allUsers', 'stats'));
    }

    /**
     * Form untuk menambahkan tanda tangan / stempel atas nama pengguna.
     */
    public function create(Request $request): View
    {
        $this->authorize('admin');

        $users = User::orderBy('name')->get();
        $companies = Company::orderBy('name')->get();
        $selectedUserId = $request->query('user_id');
        $selectedType = $request->query('type', 'original');
        $selectedCompanyId = $request->query('company_id');

        return view('admin.signatures.create', compact('users', 'companies', 'selectedUserId', 'selectedType', 'selectedCompanyId'));
    }

    /**
     * Simpan tanda tangan / stempel baru (atau ganti yang sudah ada untuk kombinasi user+type+company yang sama).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:original,company_stamp'],
            'company_id' => ['nullable', 'required_if:type,company_stamp', 'exists:companies,id'],
            'signature_image' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ], [
            'signature_image.required' => __('Silakan unggah gambar tanda tangan / stempel.'),
            'signature_image.max' => __('Ukuran file tidak boleh lebih dari 2MB.'),
            'signature_image.image' => __('File harus berupa gambar.'),
            'signature_image.mimes' => __('Format file harus PNG, JPG, atau JPEG.'),
            'company_id.required_if' => __('Perusahaan wajib dipilih untuk stempel perusahaan.'),
        ]);

        $user = User::findOrFail($validated['user_id']);
        $type = $validated['type'];
        $companyId = $type === 'company_stamp' ? (int) $validated['company_id'] : null;

        $onlyOfficeService = app(OnlyOfficeService::class);
        $file = $request->file('signature_image');
        $imageData = file_get_contents($file->getRealPath());
        $imageData = $onlyOfficeService->trimSignatureImage($imageData);
        $filename = 'signatures/sig_' . $user->id . '_' . time() . '_' . uniqid() . '.png';

        if ($type === 'original') {
            $existing = $user->signatures()->where('type', 'original')->first();
            if ($existing && Storage::disk('public')->exists($existing->file_path)) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $signature = Signature::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'original'],
                ['file_path' => $filename, 'created_via' => 'upload', 'company_id' => null]
            );
        } else {
            $existing = $user->signatures()->where('type', 'company_stamp')->where('company_id', $companyId)->first();
            if ($existing && Storage::disk('public')->exists($existing->file_path)) {
                Storage::disk('public')->delete($existing->file_path);
            }
            $signature = Signature::updateOrCreate(
                ['user_id' => $user->id, 'type' => 'company_stamp', 'company_id' => $companyId],
                ['file_path' => $filename, 'created_via' => 'upload']
            );
        }

        Storage::disk('public')->put($filename, $imageData);
        $signature->refresh();

        app(AuditService::class)->log(
            user: auth()->user(),
            action: $existing ? 'admin_signature_replaced' : 'admin_signature_uploaded',
            targetType: 'signature',
            targetId: $signature->id,
            metadata: [
                'target_user_id' => $user->id,
                'target_user_name' => $user->name,
                'type' => $type,
                'company_id' => $companyId,
            ]
        );

        return redirect()->route('admin.signatures.index')->with('success', __('Tanda tangan / stempel untuk :name berhasil disimpan.', ['name' => $user->name]));
    }

    /**
     * Form untuk mengganti gambar tanda tangan / stempel yang sudah ada.
     */
    public function edit(Signature $signature): View
    {
        $this->authorize('admin');

        $signature->load(['user', 'company']);

        return view('admin.signatures.edit', compact('signature'));
    }

    /**
     * Ganti gambar tanda tangan / stempel yang sudah ada (user/type/company tetap sama).
     */
    public function update(Request $request, Signature $signature): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'signature_image' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ], [
            'signature_image.required' => __('Silakan unggah gambar pengganti.'),
            'signature_image.max' => __('Ukuran file tidak boleh lebih dari 2MB.'),
            'signature_image.image' => __('File harus berupa gambar.'),
            'signature_image.mimes' => __('Format file harus PNG, JPG, atau JPEG.'),
        ]);

        $onlyOfficeService = app(OnlyOfficeService::class);
        $file = $request->file('signature_image');
        $imageData = file_get_contents($file->getRealPath());
        $imageData = $onlyOfficeService->trimSignatureImage($imageData);

        if (Storage::disk('public')->exists($signature->file_path)) {
            Storage::disk('public')->delete($signature->file_path);
        }

        $filename = 'signatures/sig_' . $signature->user_id . '_' . time() . '_' . uniqid() . '.png';
        Storage::disk('public')->put($filename, $imageData);

        $signature->update([
            'file_path' => $filename,
            'created_via' => 'upload',
        ]);

        app(AuditService::class)->log(
            user: auth()->user(),
            action: 'admin_signature_replaced',
            targetType: 'signature',
            targetId: $signature->id,
            metadata: [
                'target_user_id' => $signature->user_id,
                'type' => $signature->type,
                'company_id' => $signature->company_id,
            ]
        );

        return redirect()->route('admin.signatures.index')->with('success', __('Gambar tanda tangan / stempel berhasil diperbarui.'));
    }

    /**
     * Hapus tanda tangan / stempel milik pengguna manapun.
     */
    public function destroy(Signature $signature): RedirectResponse
    {
        $this->authorize('admin');

        $metadata = [
            'target_user_id' => $signature->user_id,
            'target_user_name' => $signature->user?->name,
            'type' => $signature->type,
            'company_id' => $signature->company_id,
            'file_path' => $signature->file_path,
        ];
        $signatureId = $signature->id;
        $userName = $signature->user?->name ?? 'Pengguna';

        if (Storage::disk('public')->exists($signature->file_path)) {
            Storage::disk('public')->delete($signature->file_path);
        }
        $signature->delete();

        app(AuditService::class)->log(
            user: auth()->user(),
            action: 'admin_signature_deleted',
            targetType: 'signature',
            targetId: $signatureId,
            metadata: $metadata
        );

        return redirect()->route('admin.signatures.index')->with('success', __('Tanda tangan / stempel milik :name berhasil dihapus.', ['name' => $userName]));
    }
}
