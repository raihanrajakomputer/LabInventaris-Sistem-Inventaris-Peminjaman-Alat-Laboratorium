@php
    $labBotUser = session('auth_user', []);
    $labBotIsMahasiswa = ($labBotUser['role'] ?? null) === 'mahasiswa';
    $labBotUserName = $labBotUser['name'] ?? 'Pengguna';
    $labBotQuickReplies = [
        'Cek stok alat tersedia',
        $labBotIsMahasiswa ? 'Status peminjaman saya' : 'Ada pengajuan menunggu?',
        'Kegunaan Timbangan Analitik',
        'Cara meminjam alat',
    ];
@endphp

<!-- LabBot Floating Chatbot Widget -->
<div id="labbot-root" class="no-print fixed bottom-5 right-5 z-50 flex flex-col items-end">
    <!-- Chat Window Pop-up -->
    <div id="labbot-window"
         class="hidden mb-3 w-[calc(100vw-2rem)] sm:w-[390px] h-[540px] max-h-[calc(100vh-6.5rem)] rounded-2xl bg-white shadow-2xl shadow-slate-900/20 border border-slate-200/90 flex flex-col overflow-hidden transition-all duration-200 origin-bottom-right"
         role="dialog"
         aria-label="LabBot Asisten Lab">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 px-4 py-3.5 text-white flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="relative w-10 h-10 rounded-xl bg-white/15 backdrop-blur-xs ring-1 ring-white/25 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" />
                    </svg>
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-400 ring-2 ring-indigo-600"></span>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-bold tracking-tight truncate">LabBot &mdash; Asisten Lab</h3>
                    <p class="text-[11px] text-indigo-100 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        <span id="labbot-status-text">Online &middot; Data Real-Time &amp; AI</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                <button type="button" id="labbot-reset-btn" title="Mulai percakapan baru"
                        class="w-8 h-8 rounded-lg text-indigo-100 hover:text-white hover:bg-white/15 flex items-center justify-center transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </button>
                <button type="button" id="labbot-close-btn" title="Tutup chat" aria-label="Tutup jendela LabBot"
                        class="w-8 h-8 rounded-lg text-indigo-100 hover:text-white hover:bg-white/15 flex items-center justify-center transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div id="labbot-messages" class="flex-1 overflow-y-auto px-4 py-4 space-y-3 bg-slate-50/80 text-sm">
            <!-- Initial Welcome Bubble -->
            <div class="flex items-start gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center shrink-0 mt-0.5 text-[11px] font-bold shadow-xs">
                    LB
                </div>
                <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-white border border-slate-200/80 px-3.5 py-2.5 text-slate-700 shadow-2xs space-y-1.5 leading-relaxed text-[13px]">
                    <p>Halo, <strong class="text-slate-900">{{ $labBotUserName }}</strong>! 👋</p>
                    <p>Saya <strong>LabBot</strong>, asisten virtual LabInventaris. Tanyakan stok alat real-time, lokasi lab, status peminjaman, atau fungsi alat laboratorium di sini.</p>
                </div>
            </div>

            <!-- Quick Replies -->
            <div id="labbot-quick-replies" class="pl-9 flex flex-wrap gap-1.5 pt-0.5">
                @foreach ($labBotQuickReplies as $quickText)
                    <button type="button"
                            data-quick-reply="{{ $quickText }}"
                            class="labbot-quick-btn rounded-full border border-indigo-200 bg-white hover:bg-indigo-50 hover:border-indigo-300 text-indigo-700 text-xs font-medium px-3 py-1.5 transition shadow-2xs text-left">
                        {{ $quickText }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Input Footer -->
        <form id="labbot-form" class="p-3 bg-white border-t border-slate-200/80 flex items-center gap-2 shrink-0">
            <input type="text"
                   id="labbot-input"
                   name="message"
                   maxlength="1000"
                   autocomplete="off"
                   placeholder="Tanyakan stok alat, status pinjam, dll..."
                   class="flex-1 rounded-xl border border-slate-300 bg-slate-50/70 focus:bg-white px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 outline-none transition">
            <button type="submit"
                    id="labbot-send-btn"
                    title="Kirim pesan"
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 disabled:opacity-50 disabled:cursor-not-allowed text-white flex items-center justify-center shadow-sm shadow-indigo-500/25 transition shrink-0">
                <svg class="w-4 h-4 translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
            </button>
        </form>
    </div>

    <!-- Floating Action Button -->
    <button type="button"
            id="labbot-toggle-btn"
            aria-expanded="false"
            aria-controls="labbot-window"
            class="group relative inline-flex items-center gap-2.5 rounded-full bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white px-4 py-3.5 shadow-lg shadow-indigo-600/35 hover:shadow-xl hover:shadow-indigo-600/40 transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-indigo-500/30">
        <span class="relative flex h-6 w-6 items-center justify-center">
            <svg id="labbot-icon-chat" class="w-6 h-6 transition-transform duration-200 group-hover:scale-105" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
            </svg>
            <svg id="labbot-icon-close" class="hidden w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </span>
        <span class="text-xs sm:text-sm font-bold pr-1 hidden sm:inline">LabBot</span>
        <span class="absolute top-0.5 right-0.5 flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-400 ring-2 ring-white"></span>
        </span>
    </button>
</div>

<script>
    (function () {
        const endpoint = @json(route('chatbot.ask'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());

        const chatWindow = document.getElementById('labbot-window');
        const toggleBtn = document.getElementById('labbot-toggle-btn');
        const closeBtn = document.getElementById('labbot-close-btn');
        const resetBtn = document.getElementById('labbot-reset-btn');
        const iconChat = document.getElementById('labbot-icon-chat');
        const iconClose = document.getElementById('labbot-icon-close');
        const messagesEl = document.getElementById('labbot-messages');
        const formEl = document.getElementById('labbot-form');
        const inputEl = document.getElementById('labbot-input');
        const sendBtn = document.getElementById('labbot-send-btn');

        const initialMarkup = messagesEl.innerHTML;
        let isSending = false;

        function escapeHtml(text) {
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function formatBotMarkdown(rawText) {
            const escaped = escapeHtml(rawText || '');
            const lines = escaped.split(/\r?\n/);
            let html = '';
            let inList = false;

            const formatInline = (str) => str
                .replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold text-slate-900">$1</strong>')
                .replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 rounded bg-slate-100 text-indigo-700 font-mono text-[11px]">$1</code>')
                .replace(/\*(.+?)\*/g, '<em>$1</em>');

            for (const line of lines) {
                const trimmed = line.trim();
                const bulletMatch = trimmed.match(/^[-*•]\s+(.+)$/);
                const numberMatch = trimmed.match(/^(\d+)\.\s+(.+)$/);

                if (bulletMatch) {
                    if (!inList) {
                        html += '<ul class="list-disc pl-4 space-y-1 my-1">';
                        inList = true;
                    }
                    html += `<li>${formatInline(bulletMatch[1])}</li>`;
                } else if (numberMatch) {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    html += `<p class="leading-relaxed"><span class="font-semibold text-indigo-700">${numberMatch[1]}.</span> ${formatInline(numberMatch[2])}</p>`;
                } else {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    if (trimmed === '') {
                        html += '<div class="h-1.5"></div>';
                    } else {
                        html += `<p class="leading-relaxed">${formatInline(trimmed)}</p>`;
                    }
                }
            }

            if (inList) {
                html += '</ul>';
            }

            return html;
        }

        function scrollToBottom() {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function setOpen(open) {
            chatWindow.classList.toggle('hidden', !open);
            iconChat.classList.toggle('hidden', open);
            iconClose.classList.toggle('hidden', !open);
            toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');

            if (open) {
                setTimeout(() => {
                    scrollToBottom();
                    inputEl.focus();
                }, 60);
            }
        }

        function appendUserMessage(text) {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex justify-end';
            wrapper.innerHTML = `<div class="max-w-[82%] rounded-2xl rounded-br-md bg-indigo-600 text-white px-3.5 py-2.5 text-[13px] leading-relaxed shadow-xs break-words">${escapeHtml(text)}</div>`;
            messagesEl.appendChild(wrapper);
            scrollToBottom();
        }

        function appendBotMessage(text, mode) {
            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-start gap-2.5';

            const badge = mode === 'fallback'
                ? '<span class="inline-block mt-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-[10px] font-semibold px-2 py-0.5">Mode Data Lokal</span>'
                : '';

            wrapper.innerHTML = `
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center shrink-0 mt-0.5 text-[11px] font-bold shadow-xs">LB</div>
                <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-white border border-slate-200/80 px-3.5 py-2.5 text-slate-700 shadow-2xs text-[13px] break-words space-y-1">
                    ${formatBotMarkdown(text)}
                    ${badge}
                </div>
            `;
            messagesEl.appendChild(wrapper);
            scrollToBottom();
        }

        function showTypingIndicator() {
            const el = document.createElement('div');
            el.id = 'labbot-typing';
            el.className = 'flex items-start gap-2.5';
            el.innerHTML = `
                <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center shrink-0 mt-0.5 text-[11px] font-bold shadow-xs">LB</div>
                <div class="rounded-2xl rounded-tl-md bg-white border border-slate-200/80 px-4 py-3 shadow-2xs flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-bounce" style="animation-delay: 0ms;"></span>
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce" style="animation-delay: 150ms;"></span>
                    <span class="w-2 h-2 rounded-full bg-violet-500 animate-bounce" style="animation-delay: 300ms;"></span>
                </div>
            `;
            messagesEl.appendChild(el);
            scrollToBottom();
        }

        function removeTypingIndicator() {
            const el = document.getElementById('labbot-typing');
            if (el) el.remove();
        }

        function bindQuickReplies() {
            messagesEl.querySelectorAll('.labbot-quick-btn').forEach((btn) => {
                btn.addEventListener('click', function () {
                    const text = this.dataset.quickReply;
                    if (text) {
                        sendMessage(text);
                    }
                });
            });
        }

        async function sendMessage(rawText) {
            const text = String(rawText || '').trim();
            if (!text || isSending) return;

            isSending = true;
            inputEl.value = '';
            inputEl.disabled = true;
            sendBtn.disabled = true;

            appendUserMessage(text);
            showTypingIndicator();

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        message: text,
                    }),
                });

                removeTypingIndicator();

                if (response.status === 401 || response.status === 419) {
                    appendBotMessage('Sesi login Anda telah berakhir. Silakan muat ulang halaman untuk masuk kembali.', 'fallback');
                    return;
                }

                if (response.status === 429) {
                    appendBotMessage('Terlalu banyak pesan dalam waktu singkat. Mohon tunggu sebentar sebelum bertanya lagi.', 'fallback');
                    return;
                }

                const data = await response.json().catch(() => null);

                if (!response.ok || !data || !data.reply) {
                    const errMsg = data?.message || 'Maaf, terjadi kendala saat memproses pertanyaan Anda. Silakan coba lagi.';
                    appendBotMessage(errMsg, 'fallback');
                    return;
                }

                appendBotMessage(data.reply, data.mode || 'ai');
            } catch (error) {
                removeTypingIndicator();
                appendBotMessage('Tidak dapat terhubung ke server saat ini. Pastikan server Laravel sedang berjalan lalu coba lagi.', 'fallback');
            } finally {
                isSending = false;
                inputEl.disabled = false;
                sendBtn.disabled = false;
                inputEl.focus();
            }
        }

        toggleBtn.addEventListener('click', function () {
            const isHidden = chatWindow.classList.contains('hidden');
            setOpen(isHidden);
        });

        closeBtn.addEventListener('click', function () {
            setOpen(false);
        });

        resetBtn.addEventListener('click', function () {
            if (isSending) return;
            messagesEl.innerHTML = initialMarkup;
            bindQuickReplies();
            scrollToBottom();
            inputEl.focus();

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ reset: true }),
            }).catch(() => {});
        });

        formEl.addEventListener('submit', function (e) {
            e.preventDefault();
            sendMessage(inputEl.value);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !chatWindow.classList.contains('hidden')) {
                setOpen(false);
            }
        });

        bindQuickReplies();
    })();
</script>
