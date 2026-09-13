<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;

class ImapService
{
    protected ?ClientManager $clientManager = null;

    /**
     * Check if IMAP settings are configured in .env.
     */
    public function isConfigured(): bool
    {
        $host = config('imap.host');
        $user = config('imap.username');
        $pass = config('imap.password');

        return ! empty($host) && ! empty($user) && ! empty($pass) && $pass !== 'change_me';
    }

    /**
     * Get a connected client instance.
     */
    protected function getClient()
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Konfigurasi mail server IMAP belum lengkap. Silakan lengkapi IMAP_HOST, IMAP_USERNAME, dan IMAP_PASSWORD pada file .env.');
        }

        $config = [
            'default' => 'company_mailbox',
            'accounts' => [
                'company_mailbox' => [
                    'host' => config('imap.host'),
                    'port' => config('imap.port', 993),
                    'encryption' => config('imap.encryption', 'ssl'),
                    'validate_cert' => config('imap.validate_cert', true),
                    'username' => config('imap.username'),
                    'password' => config('imap.password'),
                    'protocol' => 'imap',
                    'timeout' => config('imap.timeout', 10),
                ],
            ],
            'options' => [
                'fetch' => \Webklex\PHPIMAP\IMAP::FT_PEEK, // PEEK mode: read-only, never mark unseen as seen
                'fetch_body' => false, // Fetch body on demand, not in list
                'fetch_flags' => true,
                'soft_fail' => true,
            ],
        ];

        $clientManager = new ClientManager($config);
        $client = $clientManager->account('company_mailbox');
        $client->connect();

        return $client;
    }

    /**
     * Fetch paginated inbox list with short-term caching.
     *
     * @return array{success: bool, data: array, total: int, current_page: int, per_page: int, last_page: int, error?: string}
     */
    public function getInbox(int $page = 1, int $perPage = 20, bool $forceRefresh = false): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'data' => [],
                'total' => 0,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => 1,
                'error' => 'unconfigured',
                'message' => 'Koneksi mail server belum dikonfigurasi. Silakan tambahkan IMAP_HOST, IMAP_USERNAME, dan IMAP_PASSWORD pada file .env.',
            ];
        }

        $perPage = min(max($perPage, 5), 50);
        $cacheKey = "mail:inbox:page:{$page}:per-page:{$perPage}";

        if ($forceRefresh) {
            $this->clearCache();
        }

        return Cache::remember($cacheKey, now()->addSeconds(config('imap.cache_ttl', 30)), function () use ($page, $perPage) {
            try {
                $client = $this->getClient();
                $mailboxName = config('imap.mailbox', 'INBOX');
                $folder = $client->getFolder($mailboxName);

                if (! $folder) {
                    throw new \RuntimeException("Mailbox folder '{$mailboxName}' tidak ditemukan.");
                }

                // Query messages descending by newest first
                // Use PEEK mode (leaveUnread) to never alter read status
                $query = $folder->query()->all()->leaveUnread()->setFetchOrder('desc');
                $paginator = $query->paginate($perPage, $page, 'page');

                $items = [];
                foreach ($paginator as $message) {
                    $fromData = $message->getFrom();
                    $fromObj = method_exists($fromData, 'first') ? $fromData->first() : (is_array($fromData) ? ($fromData[0] ?? null) : null);

                    $fromName = $fromObj?->personal ?? '';
                    $fromEmail = $fromObj?->mail ?? '';

                    $subject = (string) $message->getSubject();
                    $date = $message->getDate();
                    $dateCarbon = $date ? Carbon::parse($date->first() ?? $date) : null;

                    $uid = (string) $message->getUid();
                    $hasAttachments = (bool) $message->hasAttachments();

                    $flags = $message->getFlags();
                    $isSeen = $flags ? ($flags->has('seen') || $flags->contains('Seen') || $flags->contains('\\Seen')) : false;

                    $items[] = [
                        'uid' => $uid,
                        'from_name' => $fromName ?: ($fromEmail ?: 'Pengirim Tidak Dikenal'),
                        'from_email' => $fromEmail,
                        'subject' => $subject ?: '(Tanpa Subjek)',
                        'is_daily_stock' => $this->isDailyStockSubject($subject),
                        'date' => $dateCarbon?->toIso8601String(),
                        'date_display' => $dateCarbon ? $dateCarbon->translatedFormat('d M Y, H:i') : '—',
                        'is_read' => $isSeen,
                        'has_attachments' => $hasAttachments,
                        'attachment_count' => $hasAttachments ? count($message->getAttachments()) : 0,
                    ];
                }

                // Urutkan menurun (descending): email paling baru selalu di urutan teratas
                usort($items, function (array $a, array $b): int {
                    $timeA = ! empty($a['date']) ? strtotime($a['date']) : 0;
                    $timeB = ! empty($b['date']) ? strtotime($b['date']) : 0;
                    if ($timeA === $timeB) {
                        return (int) $b['uid'] <=> (int) $a['uid'];
                    }

                    return $timeB <=> $timeA;
                });

                return [
                    'success' => true,
                    'data' => $items,
                    'total' => $paginator->total(),
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'last_page' => $paginator->lastPage(),
                ];
            } catch (\Throwable $e) {
                Log::warning('mail.imap.connection_failed', [
                    'host' => config('imap.host'),
                    'port' => config('imap.port'),
                    'user' => config('imap.username'),
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'data' => [],
                    'total' => 0,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'last_page' => 1,
                    'error' => 'connection_failed',
                    'message' => 'Gagal terhubung ke mail server: '.$e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get a specific message detail by UID.
     */
    public function getMessage(string|int $uid): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $client = $this->getClient();
            $folder = $client->getFolder(config('imap.mailbox', 'INBOX'));
            if (! $folder) {
                return null;
            }

            $message = $folder->query()->leaveUnread()->getMessageByUid($uid);
            if (! $message) {
                return null;
            }

            $fromData = $message->getFrom();
            $fromObj = method_exists($fromData, 'first') ? $fromData->first() : (is_array($fromData) ? ($fromData[0] ?? null) : null);

            $toData = $message->getTo();
            $toList = [];
            if ($toData) {
                foreach ($toData as $to) {
                    $toList[] = $to->full ?? ($to->mail ?? (string) $to);
                }
            }

            $ccData = $message->getCc();
            $ccList = [];
            if ($ccData) {
                foreach ($ccData as $cc) {
                    $ccList[] = $cc->full ?? ($cc->mail ?? (string) $cc);
                }
            }

            $date = $message->getDate();
            $dateCarbon = $date ? Carbon::parse($date->first() ?? $date) : null;

            $htmlBody = $message->hasHTMLBody() ? $message->getHTMLBody() : null;
            $textBody = $message->getTextBody() ?: '';

            $attachments = [];
            if ($message->hasAttachments()) {
                foreach ($message->getAttachments() as $idx => $att) {
                    $name = $att->getName() ?: "attachment_{$idx}";
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $isExcel = in_array($ext, ['xlsx', 'xls', 'csv'], true);

                    $attachments[] = [
                        'id' => (string) ($att->getPartNumber() ?: $idx),
                        'name' => $name,
                        'size' => (int) $att->getSize(),
                        'size_display' => $this->formatBytes((int) $att->getSize()),
                        'mime_type' => $att->getMimeType() ?: 'application/octet-stream',
                        'extension' => $ext,
                        'is_excel' => $isExcel,
                    ];
                }
            }

            $flags = $message->getFlags();
            $isSeen = $flags ? ($flags->has('seen') || $flags->contains('Seen') || $flags->contains('\\Seen')) : false;
            $subjectStr = (string) $message->getSubject();

            return [
                'uid' => (string) $uid,
                'subject' => $subjectStr ?: '(Tanpa Subjek)',
                'is_daily_stock' => $this->isDailyStockSubject($subjectStr),
                'from_name' => $fromObj?->personal ?: ($fromObj?->mail ?? 'Pengirim Tidak Dikenal'),
                'from_email' => $fromObj?->mail ?? '',
                'to' => implode(', ', array_filter($toList)),
                'cc' => implode(', ', array_filter($ccList)),
                'date' => $dateCarbon?->toIso8601String(),
                'date_display' => $dateCarbon ? $dateCarbon->translatedFormat('d M Y, H:i') : '—',
                'is_read' => $isSeen,
                'html_body' => $htmlBody,
                'text_body' => $textBody,
                'attachments' => $attachments,
            ];
        } catch (\Throwable $e) {
            Log::warning('mail.message.fetch_failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download attachment by email UID and attachment ID.
     *
     * @return array{filename: string, mime_type: string, content: string, size: int}|null
     */
    public function getAttachment(string|int $uid, string $attachmentId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $client = $this->getClient();
            $folder = $client->getFolder(config('imap.mailbox', 'INBOX'));
            if (! $folder) {
                return null;
            }

            $message = $folder->query()->leaveUnread()->getMessageByUid($uid);
            if (! $message || ! $message->hasAttachments()) {
                return null;
            }

            $targetAttachment = null;
            foreach ($message->getAttachments() as $idx => $att) {
                $id = (string) ($att->getPartNumber() ?: $idx);
                if ($id === $attachmentId || $att->getName() === $attachmentId) {
                    $targetAttachment = $att;
                    break;
                }
            }

            if (! $targetAttachment) {
                return null;
            }

            $size = (int) $targetAttachment->getSize();
            $maxMb = config('imap.max_attachment_mb', 25);
            if ($size > ($maxMb * 1024 * 1024)) {
                throw new \RuntimeException("Ukuran berkas ({$this->formatBytes($size)}) melebihi batas maksimal yang diizinkan ({$maxMb} MB).");
            }

            $content = $targetAttachment->getContent();
            $name = $targetAttachment->getName() ?: "attachment_{$attachmentId}";

            return [
                'filename' => $name,
                'mime_type' => $targetAttachment->getMimeType() ?: 'application/octet-stream',
                'content' => $content,
                'size' => strlen($content),
            ];
        } catch (\Throwable $e) {
            Log::warning('mail.attachment.fetch_failed', [
                'uid' => $uid,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download an Excel attachment by email UID and optional attachment ID.
     * If attachment ID is omitted, it automatically finds the first Excel attachment (.xlsx/.xls/.csv).
     *
     * @return array{filename: string, mime_type: string, content: string, size: int}|null
     */
    public function getExcelAttachment(string|int $uid, ?string $attachmentId = null): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $client = $this->getClient();
            $folder = $client->getFolder(config('imap.mailbox', 'INBOX'));
            if (! $folder) {
                return null;
            }

            $message = $folder->query()->leaveUnread()->getMessageByUid($uid);
            if (! $message || ! $message->hasAttachments()) {
                return null;
            }

            $targetAttachment = null;
            $attachments = $message->getAttachments();

            if ($attachmentId !== null && $attachmentId !== '') {
                foreach ($attachments as $idx => $att) {
                    $id = (string) ($att->getPartNumber() ?: $idx);
                    if ($id === (string) $attachmentId || $att->getName() === $attachmentId) {
                        $targetAttachment = $att;
                        break;
                    }
                }
            }

            // If not found by ID or no ID provided, look for the first Excel attachment (.xlsx / .xls / .csv)
            if (! $targetAttachment) {
                foreach ($attachments as $att) {
                    $name = $att->getName() ?: '';
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
                        $targetAttachment = $att;
                        break;
                    }
                }
            }

            // Fallback to the very first attachment if no specific Excel extension was matched
            if (! $targetAttachment) {
                foreach ($attachments as $att) {
                    $targetAttachment = $att;
                    break;
                }
            }

            if (! $targetAttachment) {
                return null;
            }

            $size = (int) $targetAttachment->getSize();
            $maxMb = config('imap.max_attachment_mb', 25);
            if ($size > ($maxMb * 1024 * 1024)) {
                throw new \RuntimeException("Ukuran berkas ({$this->formatBytes($size)}) melebihi batas maksimal yang diizinkan ({$maxMb} MB).");
            }

            $content = $targetAttachment->getContent();
            $name = $targetAttachment->getName() ?: 'attachment.xlsx';

            return [
                'filename' => $name,
                'mime_type' => $targetAttachment->getMimeType() ?: 'application/octet-stream',
                'content' => $content,
                'size' => strlen($content),
            ];
        } catch (\Throwable $e) {
            Log::warning('mail.excel_attachment.fetch_failed', [
                'uid' => $uid,
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Mark an email as read (Seen) on the mail server.
     */
    public function markAsRead(string|int $uid): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $client = $this->getClient();
            $folder = $client->getFolder(config('imap.mailbox', 'INBOX'));
            if (! $folder) {
                return false;
            }

            $message = $folder->query()->leaveUnread()->getMessageByUid($uid);
            if ($message) {
                $flags = $message->getFlags();
                $isAlreadySeen = $flags ? ($flags->has('seen') || $flags->contains('Seen') || $flags->contains('\\Seen')) : false;

                if (! $isAlreadySeen) {
                    $message->setFlag(['Seen']);
                    $this->clearCache();
                }

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('mail.mark_as_read_failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mark an email as unread (remove Seen flag) on the mail server.
     */
    public function markAsUnread(string|int $uid): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $client = $this->getClient();
            $folder = $client->getFolder(config('imap.mailbox', 'INBOX'));
            if (! $folder) {
                return false;
            }

            $message = $folder->query()->leaveUnread()->getMessageByUid($uid);
            if ($message) {
                $message->unsetFlag(['Seen']);
                $this->clearCache();

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('mail.mark_as_unread_failed', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clear all cached inbox pages.
     */
    public function clearCache(): void
    {
        for ($page = 1; $page <= 20; $page++) {
            foreach ([10, 15, 20, 25, 50] as $perPage) {
                Cache::forget("mail:inbox:page:{$page}:per-page:{$perPage}");
            }
        }
    }

    /**
     * Check if a subject matches the Daily Stock keyword patterns.
     */
    public function isDailyStockSubject(?string $subject): bool
    {
        if (! $subject) {
            return false;
        }

        $keywords = config('imap.stock_subject_keywords', ['Satoria Daily Stock']);
        $subjectLower = mb_strtolower($subject);

        foreach ($keywords as $keyword) {
            if ($keyword !== '' && str_contains($subjectLower, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format bytes into human readable string.
     */
    private function formatBytes(int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $power), $precision).' '.($units[$power] ?? 'B');
    }
}
