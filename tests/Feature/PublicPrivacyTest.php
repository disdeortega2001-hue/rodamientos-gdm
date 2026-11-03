<?php

namespace Tests\Feature;

use App\Public\PublicProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class PublicPrivacyTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    public function test_public_pages_never_leak_cost_or_stock(): void
    {
        $p = $this->product(['code' => 'PRIV', 'avg_cost_cents' => 987654, 'stock' => 432198, 'is_featured' => true]);

        foreach (['/', '/catalogo', '/catalogo/priv'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('987654')->assertDontSee('9.876,54')->assertDontSee('432198');
        }
    }

    public function test_public_product_array_has_no_private_keys(): void
    {
        $array = PublicProduct::fromModel($this->product(), null)->toArray();

        foreach (['avg_cost_cents', 'stock', 'low_stock_threshold', 'track_stock'] as $key) {
            $this->assertArrayNotHasKey($key, $array);
        }
    }
}
