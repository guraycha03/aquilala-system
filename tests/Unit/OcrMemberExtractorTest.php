<?php

namespace Tests\Unit;

use App\Services\OcrMemberExtractor;
use PHPUnit\Framework\TestCase;

class OcrMemberExtractorTest extends TestCase
{
    private OcrMemberExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extractor = new OcrMemberExtractor;
    }

    public function test_extracts_name_barangay_phone_and_email_from_member_lines(): void
    {
        $text = implode("\n", [
            'Juan Dela Cruz',
            'Impasugong',
            '09171234567',
            'juan.cruz@example.com',
        ]);

        $records = $this->extractor->extract($text);

        $this->assertCount(1, $records);
        $this->assertSame('Juan Dela', $records[0]['first_name']);
        $this->assertSame('Cruz', $records[0]['last_name']);
        $this->assertSame('Impasugong', $records[0]['barangay']);
        $this->assertSame('09171234567', $records[0]['phone']);
        $this->assertSame('juan.cruz@example.com', $records[0]['email']);
    }

    public function test_attaches_years_farming_to_the_current_record(): void
    {
        $text = implode("\n", [
            'Maria Santos',
            'Malaybalay',
            '5 years farming',
            '09179876543',
            'maria.santos@example.com',
        ]);

        $records = $this->extractor->extract($text);

        $this->assertCount(1, $records);
        $this->assertSame('Maria', $records[0]['first_name']);
        $this->assertSame('Santos', $records[0]['last_name']);
        $this->assertSame('5', $records[0]['years_farming']);
        $this->assertSame('maria.santos@example.com', $records[0]['email']);
    }

    public function test_skips_column_headers_and_page_footers(): void
    {
        $text = implode("\n", [
            'Name Email Contact Number',
            'Page 1 of 3',
        ]);

        $this->assertSame([], $this->extractor->extract($text));
    }

    public function test_ignores_farm_area_lines_without_a_name(): void
    {
        $text = implode("\n", [
            '1.5 hectares',
            '2 ha',
        ]);

        $this->assertSame([], $this->extractor->extract($text));
    }

    public function test_signal_line_before_any_name_still_becomes_a_candidate(): void
    {
        $records = $this->extractor->extract("maria.santos@example.com\n09179876543");

        $this->assertCount(1, $records);
        $this->assertSame('maria.santos@example.com', $records[0]['email']);
        $this->assertSame('09179876543', $records[0]['phone']);
    }

    public function test_starts_a_new_record_at_each_name_line(): void
    {
        $text = implode("\n", [
            'Juan Dela Cruz',
            '09171234567',
            'juan@example.com',
            'Maria Santos',
            '09179876543',
            'maria@example.com',
        ]);

        $records = $this->extractor->extract($text);

        $this->assertCount(2, $records);
        $this->assertSame('juan@example.com', $records[0]['email']);
        $this->assertSame('Cruz', $records[0]['last_name']);
        $this->assertSame('maria@example.com', $records[1]['email']);
        $this->assertSame('Santos', $records[1]['last_name']);
    }

    public function test_strips_list_markers_but_keeps_phone_digits_intact(): void
    {
        $text = "1. Juan Dela Cruz\n2) 0917 123 4567\n3. juan@example.com";

        $records = $this->extractor->extract($text);

        $this->assertCount(1, $records);
        $this->assertSame('Cruz', $records[0]['last_name']);
        $this->assertSame('0917 123 4567', $records[0]['phone']);
        $this->assertSame('juan@example.com', $records[0]['email']);
    }

    public function test_rejects_non_philippine_mobile_numbers(): void
    {
        $records = $this->extractor->extract('1234567890');

        $this->assertSame([], $records);
    }
}
