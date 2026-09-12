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
}
