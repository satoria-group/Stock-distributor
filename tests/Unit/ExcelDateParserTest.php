<?php

namespace Tests\Unit;

use App\Livewire\Stock\Upload;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ExcelDateParserTest extends TestCase
{
    private function parseDate(mixed $value): ?string
    {
        $component = new Upload();
        $reflector = new ReflectionClass($component);
        $method = $reflector->getMethod('parseExcelDate');
        $method->setAccessible(true);

        return $method->invoke($component, $value);
    }

    public function test_parses_dd_mm_yyyy_format_correctly(): void
    {
        $this->assertEquals('2026-08-31', $this->parseDate('31-08-2026'));
        $this->assertEquals('2026-09-13', $this->parseDate('13-09-2026'));
        $this->assertEquals('2027-12-31', $this->parseDate('31-12-2027'));

        // Support standard day-first with slash as well
        $this->assertEquals('2026-08-31', $this->parseDate('31/08/2026'));
        $this->assertEquals('2026-09-13', $this->parseDate('13/09/2026'));
    }

    public function test_rejects_invalid_impossible_dates(): void
    {
        // 31 Februari tidak ada di kalender
        $this->assertNull($this->parseDate('31-02-2026'));
        $this->assertNull($this->parseDate('32-01-2026'));
        $this->assertNull($this->parseDate('00-00-0000'));
    }

    public function test_converts_excel_numeric_serial_to_standard_date(): void
    {
        // 46278 in Excel (1900 date system) corresponds to 2026-09-13
        $this->assertEquals('2026-09-13', $this->parseDate(46278));
        // 46568 corresponds to 2027-06-30
        $this->assertEquals('2027-06-30', $this->parseDate(46568));
        // 46522 corresponds to 2027-05-15
        $this->assertEquals('2027-05-15', $this->parseDate(46522));
    }

    public function test_parses_fallback_formats_such_as_excel_formatted_dates(): void
    {
        // When Excel outputs formatted date strings like m/d/Y or n/j/Y
        $this->assertEquals('2027-06-30', $this->parseDate('6/30/2027'));
        $this->assertEquals('2027-05-15', $this->parseDate('5/15/2027'));
        $this->assertEquals('2026-09-13', $this->parseDate('9/13/2026'));
        $this->assertEquals('2027-06-30', $this->parseDate('2027/06/30'));
    }
}

