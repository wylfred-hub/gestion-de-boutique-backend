<?php

namespace Tests\Unit;

use App\Models\Sale;
use PHPUnit\Framework\TestCase;

class SaleStatusTest extends TestCase
{
    public function test_legacy_status_constants_are_available(): void
    {
        $this->assertSame(Sale::STATUS_ENCOURS, Sale::STATUS_BROUILLON);
        $this->assertSame(Sale::STATUS_CONFIRMEE, Sale::STATUS_EN_PREPARATION);
    }
}
