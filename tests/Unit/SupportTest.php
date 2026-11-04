<?php

namespace Tests\Unit;

use App\Support\CatalogSearch;
use App\Support\Money;
use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{
    public function test_usd_format(): void
    {
        $this->assertSame('$12', Money::usd(1200));
        $this->assertSame('$8,50', Money::usd(850));
        $this->assertSame('$1.250', Money::usd(125000));
    }

    public function test_bs_format(): void
    {
        $this->assertSame('Bs. 1.864,20', Money::bs(1000, '186.42'));
        $this->assertSame('Bs. 1.864,20', Money::bs(1000, '186.42'));
        $this->assertSame('Bs. 89.321.730,00', Money::bs(10000000, '893.2173'));
    }

    public function test_search_normalization(): void
    {
        $this->assertSame('uc207', CatalogSearch::normalize('UC 207'));
        $this->assertSame('2047', CatalogSearch::normalize('20 x 47'));
        $this->assertSame('2047', CatalogSearch::normalize('20X47'));
        $this->assertSame('rodamiento', CatalogSearch::normalize('Rodamíento'));
        $this->assertSame('kit', CatalogSearch::normalize('kit'));
    }

    public function test_phone_normalization(): void
    {
        $this->assertSame('04129477195', Phone::normalize('0412-947-7195'));
        $this->assertSame('04129477195', Phone::normalize('+58 412 947 7195'));
        $this->assertSame('04129477195', Phone::normalize('584129477195'));
        $this->assertSame('584129477195', Phone::international('0412-947-7195'));
    }
}
