<?php

namespace Tests\Unit;

use App\Services\SlaCalculationService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SlaCalculationTest extends TestCase
{
    public function test_sorting_delay_is_classified(): void
    {
        $result = (new SlaCalculationService())->calculate(Carbon::parse('2026-01-01 08:00'), Carbon::parse('2026-01-01 14:30'), Carbon::parse('2026-01-02 14:30'));
        $this->assertTrue($result['sorting_breached']);
        $this->assertSame('sorting_delay', $result['status']);
    }

    public function test_final_mile_delay_is_classified(): void
    {
        $result = (new SlaCalculationService())->calculate(Carbon::parse('2026-01-01 08:00'), Carbon::parse('2026-01-01 10:00'), Carbon::parse('2026-01-04 11:00'));
        $this->assertFalse($result['sorting_breached']);
        $this->assertTrue($result['final_mile_breached']);
        $this->assertSame('final_mile_delay', $result['status']);
    }
}