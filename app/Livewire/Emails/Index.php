<?php

namespace App\Livewire\Emails;

use App\Models\User;
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

    public ?string $selectedUid = null;

    public ?array $selectedEmail = null;

    public bool $isLoadingDetail = false;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $isAuthorized = $user->hasRole(User::ROLE_ADMIN)
            || $user->hasRole(User::ROLE_LOGISTIK)
            || $user->can('stock.upload')
            || $user->can('stock.view');

        if (! $isAuthorized) {
            abort(403, 'Anda tidak memiliki akses ke Inbox Email Distributor.');
        }
    }

    public function selectEmail(string $uid, ImapService $imapService, HtmlSanitizerService $sanitizer): void
    {
        $this->selectedUid = $uid;
        $this->isLoadingDetail = true;

        $detail = $imapService->getMessage($uid);

        if ($detail) {
            // Tandai email sebagai sudah dibaca di server mail
            $imapService->markAsRead($uid);
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

        $this->isLoadingDetail = false;
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
        $this->isLoadingDetail = false;
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

    public function render(ImapService $imapService)
    {
        $isConfigured = $imapService->isConfigured();
        $inboxResult = $imapService->getInbox($this->page, $this->perPage);

        $emails = collect($inboxResult['data'] ?? []);

        // Client-side quick filter on current page
        if ($this->search !== '') {
            $term = mb_strtolower(trim($this->search));
            $emails = $emails->filter(function ($e) use ($term) {
                return str_contains(mb_strtolower($e['from_name'] ?? ''), $term)
                    || str_contains(mb_strtolower($e['from_email'] ?? ''), $term)
                    || str_contains(mb_strtolower($e['subject'] ?? ''), $term);
            });
        }

        if ($this->onlyWithAttachments) {
            $emails = $emails->where('has_attachments', true);
        }

        return view('livewire.emails.index', [
            'isConfigured' => $isConfigured,
            'emails' => $emails->values(),
            'total' => $inboxResult['total'] ?? 0,
            'currentPage' => $inboxResult['current_page'] ?? 1,
            'lastPage' => $inboxResult['last_page'] ?? 1,
            'hasError' => ! empty($inboxResult['error']),
            'errorMessage' => $inboxResult['message'] ?? null,
        ])->layout('layouts.app', ['title' => 'Inbox Email Distributor']);
    }
}
