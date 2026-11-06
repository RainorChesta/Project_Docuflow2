<dialog id="archive-info-modal" onclick="event.stopPropagation();" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs z-50">
    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-2xl max-h-[92vh] flex flex-col">
        {{-- Modal Header with Soft Gradient --}}
        <div class="p-5 sm:p-6 pb-4 bg-gradient-to-r from-primary/10 via-base-100 to-base-100 border-b border-base-200">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-primary/15 text-primary flex items-center justify-center shrink-0 ring-4 ring-primary/10 shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-lg sm:text-xl text-base-content leading-snug">
                                {{ __('Mengapa Dokumen Perlu Diarsipkan?') }}
                            </h3>
                            <span class="badge badge-primary badge-sm text-[10px] font-semibold">{{ __('Tata Kelola') }}</span>
                        </div>
                        <p class="text-xs sm:text-sm text-base-content/60 mt-0.5">
                            {{ __('Fungsi & perbedaan Dokumen Kerja (My Documents) vs Arsip Resmi Perusahaan (Records Registry).') }}
                        </p>
                    </div>
                </div>
                <button type="button" 
                        onclick="document.getElementById('archive-info-modal').close()" 
                        class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200 shrink-0">
                    ✕
                </button>
            </div>
        </div>

        {{-- Modal Body (Scrollable) --}}
        <div class="p-5 sm:p-6 overflow-y-auto space-y-5 text-sm">
            {{-- Analogi Meja Kerja vs Brankas Arsip --}}
            <div class="p-3.5 sm:p-4 rounded-2xl bg-base-200/50 border border-base-300/60 flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 text-base">
                    💡
                </div>
                <div class="text-xs sm:text-sm leading-relaxed text-base-content/80 space-y-1">
                    <p class="font-semibold text-base-content">
                        {{ __('Dokumen tetap dapat diakses di Dokumen Saya agar operasional harian tidak terganggu.') }}
                    </p>
                    <p class="text-xs text-base-content/70 leading-normal">
                        {{ __('Ibarat berkas di meja kerja staf yang selalu siap dibaca, pengarsipan berfungsi menyerahkan salinan otentik ke Ruang Arsip Pusat / Brankas Perusahaan untuk kebutuhan hukum dan audit jangka panjang.') }}
                    </p>
                </div>
            </div>

            {{-- Comparison Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                {{-- Kolom Dokumen Kerja --}}
                <div class="p-4 rounded-2xl bg-base-200/30 border border-base-300/60 space-y-2.5">
                    <div class="flex items-center gap-2 text-base-content font-bold text-xs uppercase tracking-wider pb-1 border-b border-base-300/40">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>{{ __('Dokumen Kerja (My Documents)') }}</span>
                    </div>
                    <ul class="space-y-2 text-base-content/70">
                        <li class="flex items-start gap-2">
                            <span class="text-base-content/40">•</span>
                            <span><strong>{{ __('Dinamis & Operasional:') }}</strong> {{ __('Bisa terus diedit, direvisi ke versi baru (v1, v2), atau diubah namanya.') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-base-content/40">•</span>
                            <span><strong>{{ __('Milik Akun Pengguna:') }}</strong> {{ __('Melekat pada akun pemilik (owner). Berisiko tercecer bila staf mutasi atau resign.') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-base-content/40">•</span>
                            <span><strong>{{ __('Tanpa Jadwal Retensi:') }}</strong> {{ __('Hanya ruang penyimpanan kerja harian tanpa batas kepatuhan hukum.') }}</span>
                        </li>
                    </ul>
                </div>

                {{-- Kolom Arsip Resmi --}}
                <div class="p-4 rounded-2xl bg-primary/5 border border-primary/20 space-y-2.5">
                    <div class="flex items-center gap-2 text-primary font-bold text-xs uppercase tracking-wider pb-1 border-b border-primary/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <span>{{ __('Arsip Resmi Perusahaan') }}</span>
                    </div>
                    <ul class="space-y-2 text-base-content/80">
                        <li class="flex items-start gap-2">
                            <span class="text-primary font-bold">✓</span>
                            <span><strong>{{ __('Segel Beku (Snapshot SHA-256):') }}</strong> {{ __('Terkunci imutabel, kebal modifikasi sebagai alat bukti hukum & audit.') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary font-bold">✓</span>
                            <span><strong>{{ __('Aset Resmi Institusi:') }}</strong> {{ __('Diberi Nomor Arsip resmi & tercatat di Buku Induk perusahaan.') }}</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary font-bold">✓</span>
                            <span><strong>{{ __('Jadwal Retensi (JRA):') }}</strong> {{ __('Dipantau masa simpannya dan dimusnahkan via Berita Acara sah.') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- 5 Manfaat Utama Pengarsipan --}}
            <div class="space-y-3 pt-1">
                <h4 class="font-bold text-xs text-base-content/50 uppercase tracking-wider">
                    {{ __('5 Nilai Utama Mengapa Dokumen Wajib Diarsipkan:') }}
                </h4>

                <div class="space-y-2.5">
                    {{-- 1. Segel Imutabel & Hash --}}
                    <div class="p-3 rounded-xl bg-base-200/40 border border-base-200 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-success/10 text-success flex items-center justify-center shrink-0 text-xs font-bold ring-2 ring-success/10">
                            1
                        </div>
                        <div>
                            <div class="font-semibold text-xs text-base-content">{{ __('Bukti Otentik yang Dibekukan (Immutable Snapshot & Hash SHA-256)') }}</div>
                            <p class="text-[11px] text-base-content/70 mt-0.5 leading-relaxed">
                                {{ __('Saat didaftarkan, sistem membuat snapshot beku dengan fingerprint kriptografi SHA-256. Sekalipun dokumen kerja di kemudian hari direvisi ke v2 atau diubah, salinan arsip resmi tidak akan pernah berubah untuk kebutuhan pembuktian hukum dan audit ISO/akreditasi.') }}
                            </p>
                        </div>
                    </div>

                    {{-- 2. Jadwal Retensi Arsip --}}
                    <div class="p-3 rounded-xl bg-base-200/40 border border-base-200 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-info/10 text-info flex items-center justify-center shrink-0 text-xs font-bold ring-2 ring-info/10">
                            2
                        </div>
                        <div>
                            <div class="font-semibold text-xs text-base-content">{{ __('Kepatuhan Hukum & Jadwal Retensi Arsip (JRA)') }}</div>
                            <p class="text-[11px] text-base-content/70 mt-0.5 leading-relaxed">
                                {{ __('Mengatur masa simpan dokumen sesuai regulasi pemerintah (misal: Rekam Medis / Keuangan 5-10 tahun). Saat masa retensi habis, sistem memicu alur Pemusnahan Resmi (Disposisi) lengkap dengan Berita Acara sah.') }}
                            </p>
                        </div>
                    </div>

                    {{-- 3. Perlindungan Legal Hold --}}
                    <div class="p-3 rounded-xl bg-base-200/40 border border-base-200 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0 text-xs font-bold ring-2 ring-error/10">
                            3
                        </div>
                        <div>
                            <div class="font-semibold text-xs text-base-content">{{ __('Perlindungan Penahanan Hukum (Legal Hold Protection)') }}</div>
                            <p class="text-[11px] text-base-content/70 mt-0.5 leading-relaxed">
                                {{ __('Jika terjadi sengketa hukum, klaim asuransi, atau audit khusus, arsip dapat dikunci dalam status Legal Hold sehingga dicegah dari pemusnahan tidak peduli jadwal retensinya.') }}
                            </p>
                        </div>
                    </div>

                    {{-- 4. Aset Resmi Perusahaan --}}
                    <div class="p-3 rounded-xl bg-base-200/40 border border-base-200 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center shrink-0 text-xs font-bold ring-2 ring-secondary/10">
                            4
                        </div>
                        <div>
                            <div class="font-semibold text-xs text-base-content">{{ __('Aset Institusi Terdaftar (Bukan Milik Pribadi)') }}</div>
                            <p class="text-[11px] text-base-content/70 mt-0.5 leading-relaxed">
                                {{ __('Arsip memiliki kode klasifikasi dan Nomor Arsip Resmi (misal: 001/ARSIP/SOP-MED/CMH/XI/2026). Arsiparis, Kepala Cabang, atau Direktur dapat menemukannya di Buku Induk kapan saja tanpa khawatir staf pembuat telah mutasi atau resign.') }}
                            </p>
                        </div>
                    </div>

                    {{-- 5. Perlindungan dari Ketidaksengajaan Hapus --}}
                    <div class="p-3 rounded-xl bg-base-200/40 border border-base-200 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-warning/10 text-warning flex items-center justify-center shrink-0 text-xs font-bold ring-2 ring-warning/10">
                            5
                        </div>
                        <div>
                            <div class="font-semibold text-xs text-base-content">{{ __('Perlindungan Terhadap Penghapusan yang Tidak Disengaja') }}</div>
                            <p class="text-[11px] text-base-content/70 mt-0.5 leading-relaxed">
                                {{ __('Dokumen kerja di My Documents bisa saja terhapus atau dibersihkan pemiliknya. Rekaman arsip resmi yang sudah teregistrasi kebal dari penghapusan biasa dan selalu aman tersimpan di brankas arsip.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="p-4 sm:p-5 bg-base-200/40 border-t border-base-200 flex items-center justify-between gap-3">
            <span class="text-[11px] text-base-content/50 hidden sm:inline">
                {{ __('DokuFlow Records Management System') }}
            </span>
            <div class="flex items-center gap-2 ml-auto">
                <button type="button" 
                        onclick="document.getElementById('archive-info-modal').close()" 
                        class="btn btn-primary btn-sm rounded-xl px-5 font-medium shadow-xs">
                    {{ __('Saya Mengerti') }}
                </button>
            </div>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>{{ __('Tutup') }}</button>
    </form>
</dialog>

