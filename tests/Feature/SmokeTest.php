<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_responds(): void
    {
        $this->get('/')->assertOk()->assertSee('Rodamiento GDM');
    }
}
