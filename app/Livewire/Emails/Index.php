<?php

namespace App\Livewire\Emails;

use App\Models\DistributorItem;
use App\Models\StockEmailLog;
use App\Services\HtmlSanitizerService;
use App\Services\ImapService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public int $page = 1;

    public int $perPage = 15;

    public string $search = '';

    public bool $onlyWithAttachments = false;

    public bool $onlyDailyStock = false;

    public ?string $selectedUid = null;

    public ?array $selectedEmail = null;

    public bool $showLogsModal = false;

    public function resetFilters(): void
    {
        $this->search = '';
        $this->onlyDailyStock = false;
        $this->onlyWithAttachments = false;
        $this->perPage = 15;
        $this->page = 1;
    }

    public function openLogsModal(): void
    {
        $this->showLogsModal = true;
    }

    public function closeLogsModal(): void
    {
        $this->showLogsModal = false;
    }

    public function runAutomationNow(ImapService $imapService): void
    {
        if (! $imapService->isConfigured()) {
            session()->flash('error', 'Koneksi mail server belum dikonfigurasi pada file .env.');

            return;
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('stock:process-emails', ['--limit' => 10]);
            $imapService->clearCache();
            $this->page = 1;
            session()->flash('status', 'Otomasi pembacaan email berhasil dijalankan! Data lampiran yang valid telah diproses.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal menjalankan otomasi: '.$e->getMessage());
        }
    }

    /**
     * Memastikan seluruh item unmapped dari log email terdaftar / ter-restore kembali
     * ke antrean DistributorItem, lalu mengarahkan ke halaman Mapping Item Distributor.
     */
    public function openMappingForLog(int $logId)
    {
        $log = StockEmailLog::find($logId);
        if ($log && ! empty($log->details['unique_skipped_names']) && $log->distributor_id) {
            $distributor = \App\Models\Distributor::find($log->distributor_id);

            foreach ($log->details['unique_skipped_names'] as $itemName) {
                // Satu pintu untuk semua jalur yang menemukan item baru: antrean
                // milik grup bila distributornya bergrup, dan baris yang pernah
                // dihapus dipulihkan TANPA pemetaan lamanya.
                DistributorItem::queueFor($distributor, (string) $itemName);
            }
        }

        return redirect()->route('distributor-items.index', [
            'ownerFilter' => $log?->distributor_id ? 'd:'.$log->distributor_id : '',
            'mappingFilter' => 'unmapped',
        ]);
    }

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        if (! $user->can('emails.view')) {
            abort(403, 'Anda tidak memiliki akses ke Inbox Email Distributor.');
        }
    }

    public function selectEmail(string $uid, ImapService $imapService, HtmlSanitizerService $sanitizer): void
    {
        $this->selectedUid = $uid;

        // Ambil detail sekaligus tandai sudah dibaca dalam SATU sesi IMAP.
        // Sebelumnya getMessage() + markAsRead() membuka dua koneksi terpisah.
        $detail = $imapService->getMessage($uid, markRead: true);

        if ($detail) {
            $detail['is_read'] = true;

            // Sanitize HTML body to prevent XSS
            if (! empty($detail['html_body'])) {
                $detail['sanitized_html'] = $sanitizer->sanitize($detail['html_body']);
            } else {
                $detail['sanitized_html'] = null;
            }
            $this->selectedEmail = $detail;
        } else {
            $this->selectedEmail = null;
            session()->flash('error', 'Gagal memuat detail email atau email tidak ditemukan di server.');
        }

    }

    public function toggleReadStatus(string $uid, ImapService $imapService): void
    {
        if ($this->selectedEmail && (string) $this->selectedEmail['uid'] === (string) $uid) {
            if ($this->selectedEmail['is_read']) {
                $imapService->markAsUnread($uid);
                $this->selectedEmail['is_read'] = false;
            } else {
                $imapService->markAsRead($uid);
                $this->selectedEmail['is_read'] = true;
            }
        }
    }

    public function closeEmail(): void
    {
        $this->selectedUid = null;
        $this->selectedEmail = null;
    }

    public function refreshInbox(ImapService $imapService): void
    {
        $imapService->clearCache();
        $this->page = 1;
        $this->closeEmail();
        session()->flash('status', 'Kotak masuk berhasil diperbarui langsung dari mail server.');
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
        $this->closeEmail();
    }

    public function updatedSearch(): void
    {
        $this->page = 1;
    }

    public function updatedOnlyWithAttachments(): void
    {
        $this->page = 1;
    }

    public function updatedOnlyDailyStock(): void
    {
        $this->page = 1;
    }

    public function render(ImapService $imapService)
    {
        $isConfigured = $imapService->isConfigured();
        $inboxResult = $imapService->getInbox($this->page, $this->perPage);

        $emails = collect($inboxResult['data'] ?? []);

        // Filter subject pattern "Satoria Daily Stock"
        if ($this->onlyDailyStock) {
            $emails = $emails->where('is_daily_stock', true);
        }

        // Filter emails with attachments
        if ($this->onlyWithAttachments) {
            $emails = $emails->where('has_attachments', true);
        }

        // Client-side quick filter on current page
        if ($this->search !== '') {
            $term = mb_strtolower(trim($this->search));
            $emails = $emails->filter(function ($e) use ($term) {
                return str_contains(mb_strtolower($e['from_name'] ?? ''), $term)
                    || str_contains(mb_strtolower($e['from_email'] ?? ''), $term)
                    || str_contains(mb_strtolower($e['subject'] ?? ''), $term);
            });
        }

        $uids = $emails->pluck('uid')->map(fn ($u) => (string) $u)->all();
        // Satu email bisa punya beberapa log (mis. otomasi menolak, lalu
        // operator mengimpor manual). Diurutkan naik supaya keyBy() menyisakan
        // log TERBARU untuk tiap email.
        $emailLogs = \App\Models\StockEmailLog::whereIn('email_uid', $uids)->orderBy('id')->get()->keyBy('email_uid');

        // Satu query agregat menggantikan 5 query terpisah (3x COUNT berkondisi,
        // 1x COUNT total, 1x ambil baris terakhir) yang sebelumnya dijalankan
        // pada SETIAP render halaman ini.
        $agg = \App\Models\StockEmailLog::query()
            ->selectRaw('COUNT(*) as total')
            // manual_import = email yang akhirnya diimpor operator; bukan gagal.
            ->selectRaw("SUM(CASE WHEN status IN ('success', 'manual_import') THEN 1 ELSE 0 END) as success")
            ->selectRaw("SUM(CASE WHEN status = 'partial_unmapped' THEN 1 ELSE 0 END) as partial")
            ->selectRaw("SUM(CASE WHEN status NOT IN ('success', 'partial_unmapped', 'manual_import') THEN 1 ELSE 0 END) as failed")
            ->selectRaw('MAX(created_at) as last_run')
            ->first();

        $automationStats = [
            'total_processed' => (int) ($agg->total ?? 0),
            'success_count' => (int) ($agg->success ?? 0),
            'partial_count' => (int) ($agg->partial ?? 0),
            'failed_count' => (int) ($agg->failed ?? 0),
            // Blade memanggil ->diffForHumans(), jadi tipenya harus tetap Carbon.
            'last_run' => ! empty($agg?->last_run) ? \Illuminate\Support\Carbon::parse($agg->last_run) : null,
        ];
        $recentLogs = $this->showLogsModal ? \App\Models\StockEmailLog::latest()->take(50)->get() : collect();

        return view('livewire.emails.index', [
            'isConfigured' => $isConfigured,
            'emails' => $emails->values(),
            'emailLogs' => $emailLogs,
            'automationStats' => $automationStats,
            'recentLogs' => $recentLogs,
            'total' => $inboxResult['total'] ?? 0,
            'currentPage' => $inboxResult['current_page'] ?? 1,
            'lastPage' => $inboxResult['last_page'] ?? 1,
            'hasError' => ! empty($inboxResult['error']),
            'errorMessage' => $inboxResult['message'] ?? null,
            'stockKeywords' => config('imap.stock_subject_keywords', ['Satoria Daily Stock']),
        ])->layout('layouts.app', ['title' => 'Inbox Email Distributor']);
    }
}
