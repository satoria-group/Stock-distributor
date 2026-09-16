<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    @include('partials.flash-alert')

    <!-- Header & Quick Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-200/80">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold shadow-xs"
                     style="background: linear-gradient(135deg, #07352d 0%, #0d6d5f 100%);">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Inbox Email Distributor</h1>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pantau email laporan stok harian dari distributor, unduh lampiran Excel, dan unggah langsung ke sistem.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <!-- Tombol Refresh Inbox -->
            <button type="button" wire:click="refreshInbox" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition cursor-pointer disabled:opacity-50"
                    title="Perbarui daftar kotak masuk langsung dari mail server">
                <svg wire:loading.remove wire:target="refreshInbox" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <svg wire:loading wire:target="refreshInbox" class="animate-spin text-[#0d6d5f]" width="14" height="14" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="refreshInbox">Refresh Kotak Masuk</span>
                <span wire:loading wire:target="refreshInbox">Menghubungkan Server...</span>
            </button>

            <!-- Shortcut ke Halaman Upload Stock -->
            <a href="{{ route('stock.upload') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-white text-xs font-bold shadow-xs transition hover:opacity-95 cursor-pointer"
               style="background: #0d6d5f;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span>Buka Form Upload Stock</span>
            </a>
        </div>
    </div>

    <!-- Banner Jika Belum Dikonfigurasi -->
    @if (! $isConfigured)
        <div class="rounded-2xl border border-amber-300 bg-amber-50/90 p-5 shadow-xs">
            <div class="flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-amber-900">Koneksi Mail Server IMAP Belum Dikonfigurasi</h3>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        Fitur penampil email ini menghubungkan sistem langsung ke server email internal perusahaan (menggunakan protokol aman IMAPS Port 993).
                        Untuk mulai menerima dan membaca email distributor, silakan isi parameter berikut pada berkas <code>.env</code> Anda:
                    </p>
                    <div class="bg-slate-900 text-slate-100 p-3.5 rounded-xl font-mono text-[11px] select-all space-y-1 overflow-x-auto shadow-inner">
                        <div>IMAP_HOST=mail.satoria.co.id</div>
                        <div>IMAP_PORT=993</div>
                        <div>IMAP_ENCRYPTION=ssl</div>
                        <div>IMAP_VALIDATE_CERT=true</div>
                        <div>IMAP_USERNAME=stock-report@satoria.co.id</div>
                        <div>IMAP_PASSWORD=password_mailbox_anda</div>
                        <div>IMAP_MAILBOX=INBOX</div>
                    </div>
                    <p class="text-[11px] text-amber-700 italic">
                        * Setelah memperbarui berkas <code>.env</code>, klik tombol <b>Refresh Kotak Masuk</b> di atas.
                    </p>
                </div>
            </div>
        </div>
    @elseif ($hasError)
        <!-- Banner Error Koneksi -->
        <div class="rounded-2xl border border-rose-300 bg-rose-50 p-4 flex items-start gap-3 shadow-xs">
            <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <h4 class="text-xs font-bold text-rose-900">Gagal Mengambil Data dari Mail Server</h4>
                <p class="text-xs text-rose-700">{{ $errorMessage }}</p>
                <div class="pt-1">
                    <button type="button" wire:click="refreshInbox" class="text-xs font-bold text-rose-800 underline hover:text-rose-950 cursor-pointer">
                        Coba hubungkan kembali sekarang
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Kartu Kontrol & Status Otomasi Email Background Worker -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-xs shrink-0" style="background: #0d6d5f;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">Otomasi Email Laporan Stok (Background Worker)</h3>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-200 shadow-2xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                        <span>Aktif (Interval 1 Menit)</span>
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Sistem otomatis membaca email ber-subjek <i>"Satoria Daily Stock"</i>, mengekstrak Excel, memvalidasi keamanan, dan menginput ke database.
                </p>
                <!-- Quick Stats Badges -->
                <div class="flex flex-wrap items-center gap-2 mt-2 text-[11px]">
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-mono">
                        Total Diproses: <b>{{ $automationStats['total_processed'] }}</b>
                    </span>
                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                        Sukses Masuk DB: <b>{{ $automationStats['success_count'] }}</b>
                    </span>
                    @if ($automationStats['partial_count'] > 0)
                        <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 border border-amber-200 font-mono">
                            Perlu Mapping: <b>{{ $automationStats['partial_count'] }}</b>
                        </span>
                    @endif
                    @if ($automationStats['failed_count'] > 0)
                        <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200 font-mono">
                            Gagal Validasi: <b>{{ $automationStats['failed_count'] }}</b>
                        </span>
                    @endif
                    @if ($automationStats['last_run'])
                        <span class="text-slate-400">
                            Terakhir: {{ $automationStats['last_run']->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <button type="button" wire:click="openLogsModal"
                    class="px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-bold transition shadow-2xs cursor-pointer flex items-center gap-1.5">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Lihat Riwayat Log ({{ $automationStats['total_processed'] }})</span>
            </button>

            <button type="button" wire:click="runAutomationNow"
                    wire:loading.attr="disabled"
                    class="px-3.5 py-2 rounded-xl text-white text-xs font-bold shadow-xs transition hover:brightness-110 cursor-pointer flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
                    style="background: #0d6d5f;"
                    title="Jalankan pengecekan dan pemrosesan email masuk sekarang tanpa menunggu scheduler 1 menit">
                <svg wire:loading.remove wire:target="runAutomationNow" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg wire:loading wire:target="runAutomationNow" class="animate-spin text-white" width="14" height="14" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="runAutomationNow">Jalankan Otomasi Sekarang</span>
                <span wire:loading wire:target="runAutomationNow">Memproses Email...</span>
            </button>
        </div>
    </div>

    <!-- Toolbar: Filter & Pencarian -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Cari pengirim, alamat email, atau subjek pesan..."
                   class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/70 pl-9 pr-4 py-2.5 text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0d6d5f]/20 focus:border-[#0d6d5f] transition">
        </div>

        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <!-- Filter Khusus Satoria Daily Stock -->
            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold px-3 py-2 rounded-xl border transition-all duration-150 select-none {{ $onlyDailyStock ? 'bg-emerald-50 text-emerald-900 border-emerald-300 ring-2 ring-emerald-500/20 shadow-xs' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200' }}"
                   title="Hanya tampilkan email dengan subjek 'Satoria Daily Stock'">
                <input type="checkbox" wire:model.live="onlyDailyStock" class="rounded border-slate-300 text-[#0d6d5f] focus:ring-[#0d6d5f]">
                <span class="flex items-center gap-1.5">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="{{ $onlyDailyStock ? 'text-emerald-700' : 'text-slate-400' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Khusus "Satoria Daily Stock"</span>
                </span>
            </label>

            <!-- Filter Berkas Lampiran -->
            <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-medium text-slate-700 select-none px-2 py-1 hover:text-slate-900 transition">
                <input type="checkbox" wire:model.live="onlyWithAttachments" class="rounded border-slate-300 text-[#0d6d5f] focus:ring-[#0d6d5f]">
                <span>Hanya yang ada lampiran</span>
            </label>

            <!-- Per Halaman -->
            <div class="flex items-center gap-1.5 text-xs text-slate-500 border-l border-slate-200 pl-3">
                <span>Per Halaman:</span>
                <select wire:model.live="perPage" class="rounded-lg border-slate-200 text-xs py-1 px-2 font-mono">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="30">30</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Email -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[11px] tracking-wider border-b border-slate-200/80">
                    <tr>
                        <th class="py-3.5 px-4 w-20 text-center">Status</th>
                        <th class="py-3.5 px-4 w-60">Pengirim</th>
                        <th class="py-3.5 px-4">Subjek Pesan</th>
                        <th class="py-3.5 px-4 w-44">Lampiran</th>
                        <th class="py-3.5 px-4 w-44 text-right">Tanggal Diterima</th>
                        <th class="py-3.5 px-4 w-28 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($emails as $email)
                        <tr wire:click="selectEmail('{{ $email['uid'] }}')"
                            class="group transition-colors duration-150 ease-in-out cursor-pointer {{ $selectedUid === $email['uid'] ? 'bg-emerald-50/70' : 'hover:bg-slate-50/90' }}">
                            <!-- Status -->
                            <td class="relative py-3.5 px-4 text-center">
                                <!-- Garis Indikator Hijau Saat Hover / Dipilih -->
                                <div class="absolute inset-y-0 left-0 w-1 rounded-r-xs transition-all duration-150 {{ $selectedUid === $email['uid'] ? 'bg-[#0d6d5f]' : 'bg-transparent group-hover:bg-[#0d6d5f]' }}"></div>

                                @if (! $email['is_read'])
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-200/80 group-hover:bg-blue-100 transition" title="Email Belum Dibaca">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                        <span>Baru</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[10px] font-medium group-hover:bg-slate-200/80 transition" title="Email Sudah Dibaca">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        <span>Dibaca</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Pengirim -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 truncate max-w-[220px] group-hover:text-slate-950 transition" title="{{ $email['from_name'] }}">
                                    {{ $email['from_name'] ?: ($email['from_email'] ?: 'Pengirim Tidak Dikenal') }}
                                </div>
                                @if (!empty($email['from_email']) && $email['from_email'] !== $email['from_name'])
                                    <div class="font-mono text-[11px] text-slate-400 truncate max-w-[220px] group-hover:text-slate-600 transition">
                                        {{ $email['from_email'] }}
                                    </div>
                                @endif
                            </td>

                            <!-- Subjek -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="font-semibold text-slate-900 line-clamp-1 group-hover:text-[#0d6d5f] transition-colors">
                                        {{ $email['subject'] }}
                                    </div>
                                    @if (!empty($email['is_daily_stock']))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-200 shrink-0 shadow-2xs" title="Sesuai pola Satoria Daily Stock">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                            <span>Daily Stock</span>
                                        </span>
                                    @endif

                                    @if (isset($emailLogs[$email['uid']]))
                                        @php $log = $emailLogs[$email['uid']]; @endphp
                                        @if ($log->status === 'success')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200 shrink-0 shadow-2xs" title="Otomatis terproses: {{ $log->imported_rows }} baris masuk database">
                                                <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                <span>Auto-Imported ({{ $log->imported_rows }})</span>
                                            </span>
                                        @elseif ($log->status === 'partial_unmapped')
                                            <div class="inline-flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200 shrink-0 shadow-2xs" title="{{ $log->error_message ?: ($log->imported_rows . ' baris masuk, ' . $log->skipped_rows . ' item belum ter-mapping') }}">
                                                    <span>⚠️ Auto: Sebagian ({{ $log->imported_rows }}/{{ $log->total_rows }})</span>
                                                </span>
                                            </div>
                                        @elseif ($log->status === 'data_already_exists')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 text-[10px] font-bold border border-amber-300 shrink-0 shadow-2xs" title="{{ $log->error_message ?: 'Data sudah ada. Silakan upload manual.' }}">
                                                <span>⚠️ Data Sudah Ada (Upload Manual)</span>
                                            </span>
                                        @elseif ($log->status === 'inactive_distributor')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200 shrink-0 shadow-2xs" title="{{ $log->error_message }}">
                                                <span>✕ Distributor Non-Aktif</span>
                                            </span>
                                        @elseif ($log->status === 'invalid_template')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200 shrink-0 shadow-2xs" title="{{ $log->error_message }}">
                                                <span>✕ Template Tidak Valid</span>
                                            </span>
                                        @elseif ($log->status === 'unauthorized_sender')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200 shrink-0 shadow-2xs" title="{{ $log->error_message }}">
                                                <span>✕ Pengirim Tidak Dikenal</span>
                                            </span>
                                        @elseif ($log->status === 'failed')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-[10px] font-bold border border-rose-200 shrink-0 shadow-2xs" title="{{ $log->error_message }}">
                                                <span>✕ Gagal Proses</span>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>

                            <!-- Lampiran -->
                            <td class="py-3.5 px-4">
                                @if ($email['has_attachments'])
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 font-medium text-[11px] border border-emerald-200/80 group-hover:bg-emerald-100/70 group-hover:border-emerald-300 transition">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                        <span>{{ $email['attachment_count'] }} Berkas</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono text-[11px]">—</span>
                                @endif
                            </td>

                            <!-- Tanggal -->
                            <td class="py-3.5 px-4 text-right font-mono text-slate-600 text-[11px] group-hover:text-slate-900 transition">
                                {{ $email['date_display'] }}
                            </td>

                            <!-- Tombol Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if (! empty($email['is_daily_stock']) && $email['has_attachments'])
                                        <a href="{{ route('stock.upload', ['from_email_uid' => $email['uid']]) }}"
                                           wire:click.stop
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-white text-[11px] font-bold transition-all duration-150 shadow-2xs cursor-pointer hover:brightness-110"
                                           style="background: #0d6d5f;"
                                           title="Langsung proses lampiran Excel dari email ini ke Upload Stock">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            <span>Upload</span>
                                        </a>
                                    @else
                                        <button type="button" disabled
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-slate-100 text-slate-400 border border-slate-200/80 text-[11px] font-medium cursor-not-allowed opacity-60"
                                                title="{{ empty($email['is_daily_stock']) ? 'Hanya tersedia untuk email bertanda Satoria Daily Stock' : 'Email ini tidak memiliki berkas lampiran' }}">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            <span>Upload</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="text-3xl">📭</div>
                                    <div class="text-xs font-semibold text-slate-600">Tidak ada email yang ditemukan</div>
                                    <p class="text-[11px] text-slate-400 max-w-sm">
                                        @if (! $isConfigured)
                                            Konfigurasikan akun email pada <code>.env</code> untuk menghubungkan ke mailbox perusahaan.
                                        @elseif ($onlyDailyStock)
                                            Tidak ada email dengan pola subjek <b>"Satoria Daily Stock"</b>. Nonaktifkan filter di atas untuk melihat seluruh email.
                                        @else
                                            Kotak masuk saat ini kosong atau kata kunci pencarian tidak cocok dengan pesan apapun.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginator -->
        @if ($lastPage > 1)
            <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500">
                    Halaman <b>{{ $currentPage }}</b> dari <b>{{ $lastPage }}</b> (Total {{ $total }} email)
                </span>
                <div class="flex items-center gap-1.5 font-mono">
                    <button type="button" wire:click="gotoPage({{ $currentPage - 1 }})"
                            {{ $currentPage <= 1 ? 'disabled' : '' }}
                            class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold disabled:opacity-40 disabled:cursor-not-allowed transition">
                        &laquo; Sebelumnya
                    </button>
                    <button type="button" wire:click="gotoPage({{ $currentPage + 1 }})"
                            {{ $currentPage >= $lastPage ? 'disabled' : '' }}
                            class="px-3 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold disabled:opacity-40 disabled:cursor-not-allowed transition">
                        Berikutnya &raquo;
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- MODAL DETAIL EMAIL & UNDUH LAMPIRAN -->
    @if ($selectedEmail)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4 animate-in fade-in duration-200"
             wire:click.self="closeEmail">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col border border-slate-200 overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-start justify-between gap-4 bg-slate-50/80 shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Detail Pesan Masuk</span>
                            @if (!empty($selectedEmail['is_daily_stock']))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold border border-emerald-200 shadow-2xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                    <span>Satoria Daily Stock</span>
                                </span>
                            @endif
                        </div>
                        <h2 class="text-base font-extrabold text-slate-900 mt-0.5 leading-snug">
                            {{ $selectedEmail['subject'] }}
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if (!empty($selectedEmail['is_read']))
                            <button type="button" wire:click="toggleReadStatus('{{ $selectedEmail['uid'] }}')"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 text-xs font-medium transition cursor-pointer"
                                    title="Tandai email sebagai belum dibaca">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                <span>Tandai Belum Dibaca</span>
                            </button>
                        @else
                            <button type="button" wire:click="toggleReadStatus('{{ $selectedEmail['uid'] }}')"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition cursor-pointer"
                                    title="Tandai email sebagai sudah dibaca">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                <span>Tandai Sudah Dibaca</span>
                            </button>
                        @endif

                        <button type="button" wire:click="closeEmail"
                                class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer"
                                title="Tutup">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Meta Info (From, To, Date) -->
                <div class="px-6 py-3.5 bg-[#f8fafc] border-b border-slate-200/80 text-xs space-y-1.5 shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-slate-500 font-medium">Dari:</span>
                            <span class="font-bold text-slate-900 ml-1">{{ $selectedEmail['from_name'] }}</span>
                            <span class="font-mono text-[11px] text-slate-500 ml-1">&lt;{{ $selectedEmail['from_email'] }}&gt;</span>
                        </div>
                        <div class="font-mono text-[11px] text-slate-500">
                            {{ $selectedEmail['date_display'] }}
                        </div>
                    </div>
                    @if (! empty($selectedEmail['to']))
                        <div class="text-[11px] text-slate-500">
                            <span>Kepada:</span>
                            <span class="font-mono text-slate-700">{{ $selectedEmail['to'] }}</span>
                        </div>
                    @endif
                </div>

                <!-- Banner Status Otomasi Email -->
                @php $selectedLog = $emailLogs[$selectedEmail['uid']] ?? null; @endphp
                @if ($selectedLog)
                    <div class="px-6 py-3.5 border-b {{ $selectedLog->status === 'success' ? 'bg-emerald-50/80 border-emerald-200' : ($selectedLog->status === 'partial_unmapped' ? 'bg-amber-50/80 border-amber-200' : ($selectedLog->status === 'data_already_exists' ? 'bg-amber-50/90 border-amber-300' : 'bg-rose-50/80 border-rose-200')) }} shrink-0">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                @if ($selectedLog->status === 'success')
                                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0">✓</span>
                                    <div>
                                        <div class="font-bold text-emerald-950">Berhasil Diimpor Otomatis oleh Sistem</div>
                                        <div class="text-[11px] text-emerald-700">
                                            Distributor: <b>{{ $selectedLog->distributor_code }}</b> &bull;
                                            Snapshot: <b>{{ $selectedLog->tanggal_snapshot ? $selectedLog->tanggal_snapshot->format('d M Y') : '-' }}</b> &bull;
                                            <b>{{ $selectedLog->imported_rows }} baris</b> tersimpan ke database.
                                        </div>
                                    </div>
                                @elseif ($selectedLog->status === 'partial_unmapped')
                                    <span class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs shrink-0">!</span>
                                    <div class="flex-1">
                                        <div class="font-bold text-amber-950">Terimpor Sebagian (Ada Produk Belum Terpetakan ke NetSuite)</div>
                                        <div class="text-[11px] text-amber-800">
                                            <b>{{ $selectedLog->imported_rows }}</b> baris masuk database, <b>{{ $selectedLog->skipped_rows }}</b> baris dilewati karena item belum di-mapping.
                                        </div>
                                        @if (! empty($selectedLog->details['unique_skipped_names']))
                                            <div class="mt-2 pt-2 border-t border-amber-200/70">
                                                <div class="text-[11px] font-bold text-amber-900 mb-1 flex items-center gap-1.5">
                                                    <span>Daftar Item Belum Ter-mapping ({{ count($selectedLog->details['unique_skipped_names']) }} Item):</span>
                                                </div>
                                                <div class="flex flex-wrap gap-1.5 mb-2.5">
                                                    @foreach ($selectedLog->details['unique_skipped_names'] as $unmappedName)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 border border-amber-300 font-mono text-[10px] font-medium shadow-2xs">
                                                            {{ $unmappedName }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                                @if ($selectedLog->distributor_id)
                                                    <button type="button" wire:click="openMappingForLog({{ $selectedLog->id }})" wire:loading.attr="disabled"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-2xs transition cursor-pointer disabled:opacity-50">
                                                        <span wire:loading.remove wire:target="openMappingForLog({{ $selectedLog->id }})">Mapping Item Distributor Ini Sekarang</span>
                                                        <span wire:loading wire:target="openMappingForLog({{ $selectedLog->id }})">Menyiapkan Antrean Mapping...</span>
                                                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @elseif ($selectedLog->status === 'data_already_exists')
                                    <span class="w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs shrink-0">!</span>
                                    <div class="flex-1">
                                        <div class="font-bold text-amber-950 flex items-center gap-2">
                                            <span>Ditolak: Data Sudah Ada</span>
                                            <span class="px-2 py-0.5 rounded bg-amber-200 text-amber-900 text-[10px] font-bold uppercase tracking-wider">Silakan Upload Manual</span>
                                        </div>
                                        <div class="text-[11px] text-amber-800 mt-0.5">{{ $selectedLog->error_message }}</div>
                                        @if ($selectedLog->distributor_id && $selectedLog->tanggal_snapshot)
                                            <div class="mt-2.5">
                                                <a href="{{ route('stock.upload', ['from_email_uid' => $selectedEmail['uid']]) }}"
                                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#0d6d5f] hover:bg-[#0b5c50] text-white text-xs font-bold shadow-2xs transition cursor-pointer">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                    <span>Buka Form Upload Manual dengan Lampiran Ini</span>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @elseif ($selectedLog->status === 'inactive_distributor')
                                    <span class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-xs shrink-0">✕</span>
                                    <div>
                                        <div class="font-bold text-rose-950">Gagal Diimpor: Distributor Non-Aktif</div>
                                        <div class="text-[11px] text-rose-700">{{ $selectedLog->error_message }}</div>
                                    </div>
                                @else
                                    <span class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-xs shrink-0">✕</span>
                                    <div>
                                        <div class="font-bold text-rose-950">Gagal Diimpor Otomatis: {{ ucfirst(str_replace('_', ' ', $selectedLog->status)) }}</div>
                                        <div class="text-[11px] text-rose-700">{{ $selectedLog->error_message }}</div>
                                    </div>
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-500 font-mono shrink-0">Diproses: {{ $selectedLog->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                @endif

                <!-- Section Lampiran (Excel / Files) -->
                @if (! empty($selectedEmail['attachments']))
                    <div class="px-6 py-3.5 bg-emerald-50/40 border-b border-emerald-200/60 shrink-0">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-emerald-950 flex items-center gap-1.5">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                <span>Lampiran Berkas ({{ count($selectedEmail['attachments']) }} berkas ditemukan):</span>
                            </span>
                        </div>

                        <div class="space-y-2.5 w-full">
                            @foreach ($selectedEmail['attachments'] as $att)
                                <div class="flex items-center justify-between p-3 rounded-xl border {{ $att['is_excel'] ? 'border-emerald-300 bg-white' : 'border-slate-200 bg-white' }} shadow-2xs w-full">
                                    <div class="flex items-center gap-3 min-w-0 flex-1 mr-3">
                                        <div class="w-8 h-8 rounded-lg {{ $att['is_excel'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }} flex items-center justify-center shrink-0 font-bold text-[10px] font-mono">
                                            @if ($att['is_excel'])
                                                XLS
                                            @else
                                                FILE
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-xs font-bold text-slate-900 truncate" title="{{ $att['name'] }}">
                                                {{ $att['name'] }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono">
                                                {{ $att['size_display'] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0 ml-2">
                                        @if ($att['is_excel'])
                                            <a href="{{ route('stock.upload', ['from_email_uid' => $selectedEmail['uid'], 'attachment_id' => $att['id']]) }}"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-white font-bold text-xs shadow-2xs transition cursor-pointer hover:brightness-110"
                                               style="background: #0d6d5f;"
                                               title="Proses berkas ini langsung ke Grid Upload Stock">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                                </svg>
                                                <span>Upload ke Grid</span>
                                            </a>
                                        @endif
                                        <a href="{{ route('emails.attachments.download', ['uid' => $selectedEmail['uid'], 'attachmentId' => $att['id']]) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs shadow-2xs transition cursor-pointer">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Unduh</span>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Email Body (Sanitized) -->
                <div class="p-6 overflow-y-auto flex-1 space-y-4 text-xs text-slate-800 leading-relaxed bg-white">
                    @if ($selectedEmail['sanitized_html'])
                        <div class="prose prose-xs max-w-none text-slate-800 space-y-2 border border-slate-100 p-4 rounded-xl bg-slate-50/30 overflow-x-auto">
                            {!! $selectedEmail['sanitized_html'] !!}
                        </div>
                    @elseif (! empty($selectedEmail['text_body']))
                        <div class="font-mono text-xs whitespace-pre-wrap bg-slate-50 p-4 rounded-xl border border-slate-200/80 text-slate-800">
                            {{ $selectedEmail['text_body'] }}
                        </div>
                    @else
                        <div class="text-slate-400 italic text-center py-8">
                            (Email ini tidak memiliki konten isi pesan teks)
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs shrink-0">
                    <span class="text-slate-500">
                        UID Pesan: <code class="font-mono text-[11px] text-slate-700">{{ $selectedEmail['uid'] }}</code>
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="closeEmail"
                                class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                            Tutup
                        </button>
                        @if (! empty($selectedEmail['attachments']))
                            @php
                                $firstExcelAtt = collect($selectedEmail['attachments'])->firstWhere('is_excel', true);
                                $targetAttId = $firstExcelAtt ? $firstExcelAtt['id'] : ($selectedEmail['attachments'][0]['id'] ?? null);
                            @endphp
                            <a href="{{ route('stock.upload', ['from_email_uid' => $selectedEmail['uid'], 'attachment_id' => $targetAttId]) }}"
                               class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-white font-bold transition shadow-xs cursor-pointer hover:brightness-110"
                               style="background: #0d6d5f;"
                               title="Langsung proses lampiran Excel ke halaman Upload Stock">
                                <span>Lanjut ke Upload Stock</span>
                                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL RIWAYAT LOG OTOMASI EMAIL -->
    @if ($showLogsModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs flex items-center justify-center z-50 p-4 animate-in fade-in duration-200"
             wire:click.self="closeLogsModal">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[85vh] flex flex-col border border-slate-200 overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/80 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shadow-xs" style="background: #0d6d5f;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Riwayat Log Eksekusi Otomasi Email</h3>
                            <p class="text-[11px] text-slate-500">Histori pemrosesan berkas laporan stok dari background worker</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeLogsModal" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer">✕</button>
                </div>

                <!-- Modal Body: Table -->
                <div class="flex-1 overflow-y-auto p-6">
                    @if ($recentLogs->isEmpty())
                        <div class="text-center py-12 text-slate-400">
                            <div class="text-3xl mb-2">📋</div>
                            <div class="font-bold text-slate-700 text-sm">Belum ada riwayat eksekusi otomasi</div>
                            <div class="text-xs text-slate-400 mt-0.5">Riwayat akan otomatis terisi setiap kali background worker memproses email laporan stok.</div>
                        </div>
                    @else
                        <div class="border border-slate-200 rounded-xl overflow-hidden text-xs">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="py-2.5 px-3">Waktu Eksekusi</th>
                                        <th class="py-2.5 px-3">Pengirim</th>
                                        <th class="py-2.5 px-3">Distributor & Tgl</th>
                                        <th class="py-2.5 px-3">Nama File</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-3 text-right">Baris Sukses</th>
                                        <th class="py-2.5 px-3">Catatan / Error</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($recentLogs as $log)
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                                {{ $log->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="py-2.5 px-3 font-medium text-slate-800">
                                                <div class="truncate max-w-[180px]" title="{{ $log->from_email }}">{{ $log->from_email }}</div>
                                            </td>
                                            <td class="py-2.5 px-3 font-medium text-slate-900 whitespace-nowrap">
                                                <b>{{ $log->distributor_code ?: '-' }}</b>
                                                @if ($log->tanggal_snapshot)
                                                    <span class="text-slate-400 font-mono text-[10px]">({{ $log->tanggal_snapshot->format('d/m/Y') }})</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-600 truncate max-w-[140px]" title="{{ $log->filename }}">
                                                {{ $log->filename ?: '-' }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                                @if ($log->status === 'success')
                                                    <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold">SUKSES</span>
                                                @elseif ($log->status === 'partial_unmapped')
                                                    <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[10px] font-bold">SEBAGIAN</span>
                                                @elseif ($log->status === 'inactive_distributor')
                                                    <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 text-[10px] font-bold">NON-AKTIF</span>
                                                @elseif ($log->status === 'data_already_exists')
                                                    <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-bold">DATA SUDAH ADA</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-800 text-[10px] font-bold">{{ strtoupper($log->status) }}</span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800">
                                                {{ number_format($log->imported_rows) }}
                                            </td>
                                            <td class="py-2.5 px-3 text-slate-500 text-[11px]">
                                                @if ($log->status === 'partial_unmapped' && ! empty($log->details['unique_skipped_names']))
                                                    <div class="space-y-1">
                                                        <div class="font-semibold text-amber-900 text-[11px]">
                                                            {{ count($log->details['unique_skipped_names']) }} Item Belum Ter-mapping:
                                                        </div>
                                                        <div class="flex flex-wrap gap-1 max-w-[280px]">
                                                            @foreach (array_slice($log->details['unique_skipped_names'], 0, 3) as $unmappedItem)
                                                                <span class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-900 border border-amber-200 text-[10px] font-mono truncate max-w-[130px]" title="{{ $unmappedItem }}">
                                                                    {{ $unmappedItem }}
                                                                </span>
                                                            @endforeach
                                                            @if (count($log->details['unique_skipped_names']) > 3)
                                                                <span class="text-[10px] text-amber-700 font-bold self-center">
                                                                    +{{ count($log->details['unique_skipped_names']) - 3 }} lainnya
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @if ($log->distributor_id)
                                                            <button type="button" wire:click="openMappingForLog({{ $log->id }})"
                                                                    class="inline-block text-[10px] text-emerald-700 hover:underline font-bold mt-0.5 cursor-pointer bg-transparent border-0 p-0 text-left">
                                                                &rarr; Buka Form Mapping Item
                                                            </button>
                                                        @endif
                                                    </div>
                                                @elseif ($log->status === 'data_already_exists')
                                                    <div class="space-y-0.5">
                                                        <div class="font-bold text-amber-900 text-[11px]">
                                                            Data sudah ada. Silakan upload manual.
                                                        </div>
                                                        <div class="text-[10px] text-slate-500 truncate max-w-[240px]" title="{{ $log->error_message }}">
                                                            {{ $log->error_message }}
                                                        </div>
                                                        @if ($log->email_uid && $log->email_uid !== 'unknown')
                                                            <a href="{{ route('stock.upload', ['from_email_uid' => $log->email_uid]) }}"
                                                               class="inline-block text-[10px] text-emerald-700 hover:underline font-bold mt-0.5 cursor-pointer">
                                                                &rarr; Upload Manual ke Grid
                                                            </a>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="truncate max-w-[220px]" title="{{ $log->error_message }}">
                                                        {{ $log->error_message ?: '-' }}
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex justify-end shrink-0">
                    <button type="button" wire:click="closeLogsModal" class="px-4 py-1.5 rounded-xl border border-slate-200 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-100 cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
