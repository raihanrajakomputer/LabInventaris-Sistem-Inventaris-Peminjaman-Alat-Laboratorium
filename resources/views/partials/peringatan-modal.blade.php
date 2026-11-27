<!-- Modal Beri Peringatan Keterlambatan (Petugas / Laboran) -->
<div id="peringatan-modal" class="no-print fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 backdrop-blur-xs p-4" role="dialog" aria-modal="true" aria-labelledby="peringatan-modal-title">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden">
        <!-- Header -->
        <div class="px-5 py-4 bg-gradient-to-r from-rose-600 to-red-600 text-white flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 id="peringatan-modal-title" class="text-sm sm:text-base font-bold">Beri Peringatan Keterlambatan</h3>
                    <p id="peringatan-modal-subtitle" class="text-[11px] text-rose-100">Transaksi #</p>
                </div>
            </div>
            <button type="button" onclick="closePeringatanModal()" aria-label="Tutup modal peringatan"
                    class="w-8 h-8 rounded-lg text-rose-100 hover:text-white hover:bg-white/15 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="peringatan-modal-form" method="POST" action="" class="p-5 space-y-4">
            @csrf

            <div class="rounded-xl border border-rose-200 bg-rose-50/80 px-3.5 py-2.5 text-xs text-rose-900 space-y-0.5">
                <p>Peminjam: <strong id="peringatan-peminjam-name">-</strong></p>
                <p>Jadwal Kembali: <strong id="peringatan-jadwal">-</strong></p>
                <p class="text-[11px] text-rose-700 pt-1">
                    Mengirim peringatan akan menampilkan notifikasi merah di akun mahasiswa dan otomatis mengubah status menjadi <strong>Terlambat</strong>.
                </p>
            </div>

            <div>
                <label for="peringatan-textarea" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Pesan Peringatan untuk Mahasiswa <span class="text-rose-500">*</span>
                </label>
                <textarea id="peringatan-textarea"
                          name="pesan_peringatan"
                          rows="4"
                          required
                          minlength="5"
                          maxlength="1000"
                          class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 outline-none"></textarea>
                <p class="mt-1 text-[11px] text-slate-400">Anda dapat mengedit pesan default di atas sebelum mengirim.</p>
            </div>

            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2.5">
                <a id="peringatan-wa-link"
                   href="#"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="hidden inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold px-3.5 py-2.5 transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.52 3.48A11.86 11.86 0 0012.06 0C5.46 0 .1 5.36.1 11.96c0 2.1.55 4.16 1.6 5.97L0 24l6.24-1.64a11.93 11.93 0 005.82 1.49h.01c6.6 0 11.96-5.36 11.96-11.96 0-3.2-1.24-6.2-3.51-8.41zm-8.46 18.35h-.01a9.9 9.9 0 01-5.05-1.38l-.36-.22-3.7.97.99-3.61-.24-.37a9.9 9.9 0 01-1.52-5.26c0-5.47 4.45-9.92 9.93-9.92 2.65 0 5.14 1.03 7.01 2.91a9.86 9.86 0 012.91 7.01c0 5.47-4.46 9.92-9.96 9.92zm5.44-7.43c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.21 5.09 4.5.71.31 1.27.49 1.7.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.42.25-.7.25-1.29.17-1.42-.07-.12-.27-.2-.57-.35z"/>
                    </svg>
                    Hubungi via WhatsApp
                </a>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" onclick="closePeringatanModal()"
                            class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs sm:text-sm font-semibold px-4 py-2.5 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs sm:text-sm font-semibold px-5 py-2.5 shadow-sm shadow-rose-600/20 transition">
                        Kirim Peringatan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const DEFAULT_WARNING = 'Mohon segera mengembalikan alat yang dipinjam karena sudah melewati batas waktu pengembalian.';
        const modal = document.getElementById('peringatan-modal');
        const form = document.getElementById('peringatan-modal-form');
        const subtitle = document.getElementById('peringatan-modal-subtitle');
        const peminjamNameEl = document.getElementById('peringatan-peminjam-name');
        const jadwalEl = document.getElementById('peringatan-jadwal');
        const textarea = document.getElementById('peringatan-textarea');
        const waLink = document.getElementById('peringatan-wa-link');

        let activeWaPhone = '';
        let activeTxId = '';
        let activePeminjamName = '';

        function updateWaHref() {
            if (!activeWaPhone) {
                waLink.classList.add('hidden');
                waLink.setAttribute('href', '#');
                return;
            }
            const pesan = (textarea.value || DEFAULT_WARNING).trim();
            const fullText = `Halo ${activePeminjamName}, terkait peminjaman alat laboratorium LabInventaris (#${activeTxId}): ${pesan}`;
            waLink.setAttribute('href', `https://wa.me/${activeWaPhone}?text=${encodeURIComponent(fullText)}`);
            waLink.classList.remove('hidden');
        }

        window.openPeringatanModal = function (btn) {
            activeTxId = btn.dataset.id || '';
            activePeminjamName = btn.dataset.peminjam || 'Mahasiswa';
            activeWaPhone = btn.dataset.waPhone || '';

            form.action = btn.dataset.action || '';
            subtitle.textContent = `Transaksi #${activeTxId}`;
            peminjamNameEl.textContent = activePeminjamName;
            jadwalEl.textContent = btn.dataset.jadwal || '-';
            textarea.value = (btn.dataset.pesan || '').trim() || DEFAULT_WARNING;

            updateWaHref();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            setTimeout(() => textarea.focus(), 50);
        };

        window.closePeringatanModal = function () {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        };

        textarea.addEventListener('input', updateWaHref);

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                window.closePeringatanModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closePeringatanModal();
            }
        });
    })();
</script>
@endpush
