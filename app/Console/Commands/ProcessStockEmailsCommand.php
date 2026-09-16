<?php

namespace App\Console\Commands;

use App\Models\StockEmailLog;
use App\Services\ImapService;
use App\Services\StockImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessStockEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:process-emails
                            {--limit=10 : Maksimal email yang diproses per eksekusi}
                            {--dry-run : Uji coba validasi dan parsing tanpa menyimpan perubahan ke database}
                            {--force : Paksa proses ulang email meskipun sudah pernah diproses di log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membaca email laporan stok harian distributor secara otomatis dan mengimpor lampiran Excel ke database.';

    public function handle(ImapService $imapService, StockImportService $importService): int
    {
        $this->info('=== Memulai Otomasi Pembacaan Email Laporan Stok Distributor ===');

        if (! $imapService->isConfigured()) {
            $this->warn('Koneksi mail server IMAP belum dikonfigurasi pada .env. Proses dilewati.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($dryRun) {
            $this->warn('MODE DRY-RUN: Data tidak akan disimpan ke database stock_entries.');
        }

        $this->comment("Memeriksa email belum dibaca (maks {$limit} email)...");
        $unreadMessages = $imapService->getUnreadMessages($limit);

        if (empty($unreadMessages)) {
            $this->info('Tidak ditemukan email baru yang belum dibaca.');

            return self::SUCCESS;
        }

        $this->info('Ditemukan '.count($unreadMessages).' email belum dibaca. Memfilter subject laporan stok...');

        $processedCount = 0;
        $successCount = 0;
        $failedCount = 0;
        $summaryTable = [];

        foreach ($unreadMessages as $msg) {
            $uid = $msg['uid'];
            $subject = $msg['subject'];
            $fromEmail = $msg['from_email'];
            $fromName = $msg['from_name'];
            $messageId = $msg['message_id'];
            $isDailyStock = $msg['is_daily_stock'];
            $hasAttachments = $msg['has_attachments'];

            // Hanya proses email yang memiliki subject laporan stok harian
            if (! $isDailyStock) {
                continue;
            }

            $processedCount++;
            $this->line("--------------------------------------------------");
            $this->line("Memproses Email UID #{$uid}: \"{$subject}\" dari {$fromEmail}");

            // Anti-duplikasi / Idempotency check: jika sudah pernah sukses diproses sebelumnya
            if (! $force) {
                $alreadyLogged = StockEmailLog::query()
                    ->where('status', 'success')
                    ->where(function ($q) use ($uid, $messageId) {
                        $q->where('email_uid', $uid);
                        if ($messageId) {
                            $q->orWhere('message_id', $messageId);
                        }
                    })
                    ->first();

                if ($alreadyLogged) {
                    $this->warn("Email UID #{$uid} sudah pernah sukses diproses pada {$alreadyLogged->created_at->format('d/m/Y H:i')}. Dilewati.");
                    $summaryTable[] = [$uid, $fromEmail, $subject, 'SKIPPED (Already Processed)', 0, 0];
                    continue;
                }
            }

            if (! $hasAttachments) {
                $this->error("Email UID #{$uid} tidak memiliki berkas lampiran.");
                StockEmailLog::create([
                    'email_uid' => (string) $uid,
                    'message_id' => $messageId,
                    'from_email' => $fromEmail,
                    'from_name' => $fromName,
                    'subject' => $subject,
                    'status' => 'failed',
                    'error_message' => 'Email tidak memiliki berkas lampiran Excel.',
                ]);
                $imapService->markAsRead($uid);
                $failedCount++;
                $summaryTable[] = [$uid, $fromEmail, $subject, 'FAILED (No Attachment)', 0, 0];
                continue;
            }

            // Download lampiran Excel
            $attachment = $imapService->getExcelAttachment($uid);
            if (! $attachment || empty($attachment['content'])) {
                $this->error("Tidak ditemukan berkas Excel (.xlsx / .xls / .csv) pada lampiran email UID #{$uid}.");
                StockEmailLog::create([
                    'email_uid' => (string) $uid,
                    'message_id' => $messageId,
                    'from_email' => $fromEmail,
                    'from_name' => $fromName,
                    'subject' => $subject,
                    'status' => 'failed',
                    'error_message' => 'Lampiran bukan berkas spreadsheet Excel yang didukung.',
                ]);
                $imapService->markAsRead($uid);
                $failedCount++;
                $summaryTable[] = [$uid, $fromEmail, $subject, 'FAILED (Not Excel)', 0, 0];
                continue;
            }

            $filename = $attachment['filename'] ?? 'attachment.xlsx';
            $this->comment("Mengunduh lampiran '{$filename}' (".strlen($attachment['content'])." bytes)...");

            // Proses import via StockImportService
            $result = $importService->processEmailAttachment(
                binaryContent: $attachment['content'],
                filename: $filename,
                fromEmail: $fromEmail,
                fromName: $fromName,
                subject: $subject,
                emailUid: $uid,
                messageId: $messageId,
                dryRun: $dryRun
            );

            if ($result['success']) {
                $distCode = $result['distributor_code'];
                $tanggal = $result['tanggal'];
                $imported = $result['imported_rows'];
                $skipped = $result['skipped_rows'];

                $this->info("BERHASIL: Diimpor {$imported} baris untuk {$distCode} pada tanggal {$tanggal} (Skipped unmapped: {$skipped}).");
                if (! empty($result['details']['unique_skipped_names'])) {
                    $unmappedList = implode(', ', $result['details']['unique_skipped_names']);
                    $this->warn("   -> Item belum ter-mapping: {$unmappedList}");
                }

                // Tandai email sebagai sudah dibaca di server IMAP
                if (! $dryRun) {
                    $imapService->markAsRead($uid);
                }

                $successCount++;
                $statusText = $skipped > 0 ? "PARTIAL (Unmapped {$skipped})" : "SUCCESS";
                $summaryTable[] = [$uid, $fromEmail, "{$distCode} ({$tanggal})", $statusText, $imported, $skipped];
            } else {
                $err = $result['error'] ?? 'Terjadi kesalahan tidak dikenal saat memproses Excel.';
                if ($result['status'] === 'data_already_exists') {
                    $this->warn("DITOLAK (data_already_exists): {$err}");
                } else {
                    $this->error("GAGAL ({$result['status']}): {$err}");
                }

                // Tandai email sebagai sudah dibaca agar tidak terjadi infinite loop per menit
                if (! $dryRun) {
                    $imapService->markAsRead($uid);
                }

                $failedCount++;
                $label = $result['status'] === 'data_already_exists' ? 'REJECTED (Data Sudah Ada)' : "FAILED ({$result['status']})";
                $summaryTable[] = [$uid, $fromEmail, $subject, $label, 0, 0];
            }
        }

        $this->line("==================================================");
        if ($processedCount === 0) {
            $this->info('Tidak ada email dengan subject laporan stok harian yang perlu diproses.');
        } else {
            $this->table(
                ['UID', 'Pengirim', 'Info / Target', 'Status', 'Imported Rows', 'Skipped Rows'],
                $summaryTable
            );
            $this->info("Selesai! Total diproses: {$processedCount} | Sukses: {$successCount} | Gagal: {$failedCount}");
        }

        return self::SUCCESS;
    }
}
