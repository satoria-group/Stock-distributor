<?php

namespace Tests\Feature;

use App\Services\ImapService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * Menjaga dua perilaku yang mudah hilang saat refactor:
 *
 *  1. Kegagalan koneksi mail server TIDAK BOLEH ikut ter-cache.
 *  2. Email belum dibaca disaring di SISI SERVER lewat kriteria IMAP SUBJECT,
 *     supaya email non-stok tidak pernah menghabiskan kuota pemrosesan.
 */
class ImapServiceResilienceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'imap.host' => 'mail.contoh.test',
            'imap.username' => 'stock@contoh.test',
            'imap.password' => 'rahasia',
            'imap.cache_ttl' => 300,
        ]);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // 1. Kegagalan koneksi tidak boleh di-cache
    // ---------------------------------------------------------------

    public function test_failed_inbox_fetch_is_not_cached_and_is_retried(): void
    {
        $service = new class extends ImapService
        {
            public int $attempts = 0;

            protected function getClient()
            {
                $this->attempts++;
                throw new \RuntimeException('koneksi putus');
            }
        };

        $first = $service->getInbox(1, 20);

        $this->assertFalse($first['success']);
        $this->assertSame('connection_failed', $first['error']);
        $this->assertSame(1, $service->attempts);

        // Inti pengujian: percobaan kedua harus BENAR-BENAR menghubungi server
        // lagi, bukan menyajikan ulang error dari cache selama 300 detik.
        $second = $service->getInbox(1, 20);

        $this->assertFalse($second['success']);
        $this->assertSame(2, $service->attempts, 'Hasil gagal ikut ter-cache: mail server tidak dihubungi ulang.');
    }

    public function test_successful_inbox_fetch_is_cached(): void
    {
        $service = new class extends ImapService
        {
            public int $attempts = 0;

            public function getInbox(int $page = 1, int $perPage = 20, bool $forceRefresh = false): array
            {
                return parent::getInbox($page, $perPage, $forceRefresh);
            }

            protected function getClient()
            {
                $this->attempts++;

                throw new \RuntimeException('tidak seharusnya dipanggil dua kali');
            }
        };

        // Tanam hasil sukses ke cache memakai kunci yang sama dengan service.
        $key = 'mail:inbox:v1:page:1:per-page:20';
        Cache::put($key, [
            'success' => true,
            'data' => [['uid' => '9', 'is_read' => false]],
            'total' => 1,
            'current_page' => 1,
            'per_page' => 20,
            'last_page' => 1,
        ], now()->addSeconds(300));

        $result = $service->getInbox(1, 20);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $service->attempts, 'Cache sukses diabaikan; mail server dihubungi tanpa perlu.');
    }

    // ---------------------------------------------------------------
    // 2. Penyaringan subject di sisi server (anti-starvation)
    // ---------------------------------------------------------------

    public function test_unread_query_filters_by_subject_on_the_server(): void
    {
        config(['imap.stock_subject_keywords' => ['Satoria Daily Stock', 'Laporan Stok Harian']]);

        $usedSubjects = [];

        $folder = $this->fakeFolder(function (string $subject) use (&$usedSubjects) {
            $usedSubjects[] = $subject;

            // Kedua kata kunci mengembalikan UID 50 -> harus ter-dedupe.
            return $subject === 'Satoria Daily Stock'
                ? [$this->fakeMessage(50), $this->fakeMessage(42)]
                : [$this->fakeMessage(50)];
        });

        $service = new ImapService();
        $result = $this->invokeCandidates($service, $folder, 10);

        $this->assertSame(['Satoria Daily Stock', 'Laporan Stok Harian'], $usedSubjects);
        $this->assertCount(2, $result, 'UID yang cocok dua kata kunci seharusnya ter-dedupe.');
        $this->assertSame([50, 42], array_keys($result), 'Hasil harus urut menurun (terbaru dulu).');
    }

    public function test_unread_query_falls_back_to_wide_scan_when_subject_search_unsupported(): void
    {
        config(['imap.stock_subject_keywords' => ['Satoria Daily Stock']]);

        $wideScanLimit = null;

        $folder = $this->fakeFolder(
            fn () => throw new \RuntimeException('SUBJECT tidak didukung'),
            function (int $limit) use (&$wideScanLimit) {
                $wideScanLimit = $limit;

                return [$this->fakeMessage(7)];
            }
        );

        $service = new ImapService();
        $result = $this->invokeCandidates($service, $folder, 10);

        $this->assertSame(100, $wideScanLimit, 'Jalur cadangan harus memindai jendela jauh lebih lebar (limit x10).');
        $this->assertCount(1, $result);
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    private function invokeCandidates(ImapService $service, $folder, int $limit): array
    {
        $method = new \ReflectionMethod($service, 'queryUnreadCandidates');
        $method->setAccessible(true);

        $out = $method->invoke($service, $folder, $limit);

        return is_array($out) ? $out : iterator_to_array($out);
    }

    private function fakeMessage(int $uid)
    {
        $msg = Mockery::mock();
        $msg->shouldReceive('getUid')->andReturn($uid);

        return $msg;
    }

    /**
     * Membuat folder tiruan yang meniru rantai query webklex:
     * query()->unseen()->subject($kw)->leaveUnread()->setFetchOrder()->limit()->get()
     */
    private function fakeFolder(callable $onSubject, ?callable $onWideScan = null)
    {
        $folder = Mockery::mock();

        $folder->shouldReceive('query')->andReturnUsing(function () use ($onSubject, $onWideScan) {
            $q = Mockery::mock();
            $state = new \stdClass();
            $state->subject = null;
            $state->limit = null;

            $state->result = null;

            $q->shouldReceive('unseen', 'leaveUnread', 'setFetchOrder')->andReturn($q);
            // $onSubject dipanggil TEPAT SEKALI di sini; get() hanya menyajikan
            // hasilnya. (Memanggilnya lagi di get() membuat hitungan ganda.)
            $q->shouldReceive('subject')->andReturnUsing(function ($kw) use ($q, $state, $onSubject) {
                $state->subject = $kw;
                $state->result = $onSubject($kw); // boleh melempar -> uji jalur cadangan

                return $q;
            });
            $q->shouldReceive('limit')->andReturnUsing(function ($n) use ($q, $state) {
                $state->limit = $n;

                return $q;
            });
            $q->shouldReceive('get')->andReturnUsing(function () use ($state, $onWideScan) {
                return $state->subject !== null
                    ? $state->result
                    : ($onWideScan ? $onWideScan($state->limit) : []);
            });

            return $q;
        });

        return $folder;
    }
}
