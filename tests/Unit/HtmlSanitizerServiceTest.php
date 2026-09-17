<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizerService;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerServiceTest extends TestCase
{
    protected HtmlSanitizerService $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new HtmlSanitizerService();
    }

    public function test_strips_script_tags(): void
    {
        $dirty = '<p>Hello</p><script>alert("XSS");</script><span>World</span>';
        $clean = $this->sanitizer->sanitize($dirty);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert', $clean);
        $this->assertStringContainsString('Hello', $clean);
        $this->assertStringContainsString('World', $clean);
    }

    public function test_strips_inline_event_handlers(): void
    {
        $dirty = '<img src="valid.jpg" onerror="alert(1)" onload="evil()"><a href="https://example.com" onclick="steal()">Click</a>';
        $clean = $this->sanitizer->sanitize($dirty);

        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('onload', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('https://example.com', $clean);
    }

    public function test_strips_javascript_href(): void
    {
        $dirty = '<a href="javascript:alert(\'pwned\')">Malicious Link</a>';
        $clean = $this->sanitizer->sanitize($dirty);

        $this->assertStringNotContainsString('javascript:', $clean);
    }

    public function test_adds_target_blank_and_safe_rel_to_links(): void
    {
        $dirty = '<a href="https://satoria.co.id">Satoria</a>';
        $clean = $this->sanitizer->sanitize($dirty);

        $this->assertStringContainsString('target="_blank"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $clean);
    }

    public function test_strips_iframes_and_objects(): void
    {
        $dirty = '<div><iframe src="https://evil.com"></iframe><object data="bad.swf"></object></div>';
        $clean = $this->sanitizer->sanitize($dirty);

        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringNotContainsString('<object', $clean);
    }

    public function test_preserves_safe_html_elements_and_tables(): void
    {
        $dirty = '<table border="1"><thead><tr><th>Item</th><th>Qty</th></tr></thead><tbody><tr><td>Dextrose 5%</td><td>100</td></tr></tbody></table>';
        $clean = $this->sanitizer->sanitize($dirty);

        $this->assertStringContainsString('<table', $clean);
        $this->assertStringContainsString('<th>Item</th>', $clean);
        $this->assertStringContainsString('<td>Dextrose 5%</td>', $clean);
    }

    public function test_handles_null_and_empty_gracefully(): void
    {
        $this->assertSame('', $this->sanitizer->sanitize(null));
        $this->assertSame('', $this->sanitizer->sanitize('   '));
    }
    // ---------------------------------------------------------------
    // Regresi: atribut terlewat karena DOMNamedNodeMap bersifat live
    // ---------------------------------------------------------------

    public function test_event_handler_after_href_is_still_removed_on_anchor(): void
    {
        // href diproses lebih dulu dan memicu setAttribute(target/rel).
        // Bila peta atribut diiterasi secara live, onmouseover terlewat.
        $dirty = '<a href="https://example.com" onmouseover="alert(1)" onfocus="evil()">Klik</a>';
        $clean = $this->sanitizer->sanitize($dirty);

        $this->assertStringNotContainsString('onmouseover', $clean);
        $this->assertStringNotContainsString('onfocus', $clean);
        $this->assertStringNotContainsString('alert', $clean);
        $this->assertStringContainsString('https://example.com', $clean);
    }

    public function test_disallowed_attribute_after_href_is_still_removed(): void
    {
        $dirty = '<a href="https://example.com" formaction="javascript:alert(1)" srcdoc="<script>x</script>">Klik</a>';
        $clean = $this->sanitizer->sanitize($dirty);

        $this->assertStringNotContainsString('formaction', $clean);
        $this->assertStringNotContainsString('srcdoc', $clean);
    }

    // ---------------------------------------------------------------
    // Regresi: skema URL yang dikaburkan karakter kontrol
    // ---------------------------------------------------------------

    /**
     * Browser mengabaikan karakter kontrol di dalam skema URL; PHP tidak.
     * Entity sudah di-decode oleh parser DOM sebelum sampai ke pemeriksaan.
     */
    public function test_strips_javascript_href_obfuscated_with_control_characters(): void
    {
        foreach ([
            'jav&#x09;ascript:alert(1)',   // tab
            'jav&#x0A;ascript:alert(1)',   // newline
            'jav&#x0D;ascript:alert(1)',   // carriage return
            ' javascript:alert(1)',        // spasi di depan
            'java script:alert(1)',        // spasi di tengah
        ] as $payload) {
            $clean = $this->sanitizer->sanitize('<a href="'.$payload.'">Klik</a>');

            $this->assertStringNotContainsString(
                'script:',
                str_replace(["\t", "\n", "\r", ' '], '', strtolower($clean)),
                "Skema berbahaya lolos untuk payload: {$payload}"
            );
        }
    }

    public function test_keeps_ordinary_links_and_safe_data_images(): void
    {
        $clean = $this->sanitizer->sanitize(
            '<a href="https://satoria.co.id/laporan">Laporan</a>'
            .'<img src="data:image/png;base64,iVBORw0KGgo=" alt="grafik">'
        );

        $this->assertStringContainsString('https://satoria.co.id/laporan', $clean);
        $this->assertStringContainsString('data:image/png;base64', $clean);
    }
}