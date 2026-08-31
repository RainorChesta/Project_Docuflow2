@if(auth()->check() && !auth()->user()->hasSignature() && !request()->routeIs(['profile.edit', 'profile.update', 'profile.signature.*']))
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/80 backdrop-blur-sm animate-fade-in">
        <div class="card bg-base-100 max-w-md w-full shadow-2xl border border-warning/30">
            <div class="card-body text-center items-center py-8 px-6 space-y-4">
                <div class="w-16 h-16 rounded-full bg-warning/10 text-warning flex items-center justify-center animate-bounce">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </div>

                <h3 class="card-title text-xl font-bold text-base-content">
                    {{ __('Tanda Tangan Digital Wajib Diisi') }}
                </h3>

                <p class="text-sm text-base-content/70 leading-relaxed">
                    {!! __('Sesuai keamanan dan kebijakan sistem DokuFlow, Anda :strong pada profil akun Anda terlebih dahulu sebelum dapat mengakses atau mengelola fitur dokumen lainnya.', ['strong' => '<strong>'.__('wajib membuat dan menyimpan tanda tangan digital (TTD)').'</strong>']) !!}
                </p>

                <div class="card-actions w-full pt-2">
                    <a href="{{ route('profile.edit', ['must_sign' => 1]) }}" class="btn btn-warning w-full shadow-md gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        {{ __('Buat TTD Sekarang di Profil') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
