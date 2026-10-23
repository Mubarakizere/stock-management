<?php

namespace Tests\Unit;

use App\Services\UnitService;
use PHPUnit\Framework\TestCase;

class UnitServiceTest extends TestCase
{
    public function test_weight_conversions(): void
    {
        // 250 g to kg = 0.25 kg
        $this->assertEquals(0.25, UnitService::convert(250, 'g', 'kg'));

        // 1.5 kg to g = 1500 g
        $this->assertEquals(1500, UnitService::convert(1.5, 'kg', 'g'));

        // 500 mg to g = 0.5 g
        $this->assertEquals(0.5, UnitService::convert(500, 'mg', 'g'));
    }

    public function test_volume_conversions(): void
    {
        // 500 ml to l = 0.5 l
        $this->assertEquals(0.5, UnitService::convert(500, 'ml', 'l'));

        // 2 l to ml = 2000 ml
        $this->assertEquals(2000, UnitService::convert(2, 'l', 'ml'));

        // 75 cl to l = 0.75 l
        $this->assertEquals(0.75, UnitService::convert(75, 'cl', 'l'));
    }

    public function test_count_conversions(): void
    {
        // Discrete items convert 1:1
        $this->assertEquals(10, UnitService::convert(10, 'bottle', 'pcs'));
        $this->assertEquals(5, UnitService::convert(5, 'can', 'unit'));
    }

    public function test_family_detection(): void
    {
        $this->assertEquals('weight', UnitService::getFamily('kg'));
        $this->assertEquals('weight', UnitService::getFamily('g'));
        $this->assertEquals('volume', UnitService::getFamily('ml'));
        $this->assertEquals('volume', UnitService::getFamily('l'));
        $this->assertEquals('count', UnitService::getFamily('bottle'));
        $this->assertEquals('count', UnitService::getFamily('pcs'));
    }

    public function test_formatting(): void
    {
        $this->assertEquals('250 g', UnitService::format(250, 'g'));
        $this->assertEquals('1.5 kg', UnitService::format(1.5, 'kg'));
        $this->assertEquals('100 bottle', UnitService::format(100, 'bottle'));
    }
}
