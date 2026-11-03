<?php

namespace Tests\Unit;

use App\Support\AppKey;
use PHPUnit\Framework\TestCase;

class AppKeyTest extends TestCase
{
    public function test_explicit_key_wins(): void
    {
        $this->assertSame('base64:abc', AppKey::resolve('base64:abc', 'clave-db', 'production'));
    }

    public function test_derives_stable_valid_key_in_production(): void
    {
        $key = AppKey::resolve(null, 'clave-db', 'production');
        $this->assertSame($key, AppKey::resolve('', 'clave-db', 'production'));
        $this->assertStringStartsWith('base64:', $key);
        $this->assertSame(32, strlen(base64_decode(substr($key, 7))));
        $this->assertNotSame($key, AppKey::resolve(null, 'otra-clave', 'production'));
        $this->assertStringNotContainsString('clave-db', $key);
    }

    public function test_no_key_outside_production_or_without_database_password(): void
    {
        $this->assertNull(AppKey::resolve(null, 'clave-db', 'local'));
        $this->assertNull(AppKey::resolve(null, '', 'production'));
    }
}
