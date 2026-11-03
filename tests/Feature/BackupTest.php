<?php

namespace Tests\Feature;

use Tests\TestCase;

class BackupTest extends TestCase
{
    public function test_backup_creates_dated_copy(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'db');
        file_put_contents($source, 'contenido');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $source]);
        $dir = storage_path('framework/testing/backups-'.uniqid());
        config(['backup.path' => $dir]);

        $this->artisan('gdm:backup')->assertSuccessful();

        $files = glob($dir.'/gdm-*.sqlite');
        $this->assertCount(1, $files);
        $this->assertGreaterThan(0, filesize($files[0]));
        $this->assertMatchesRegularExpression('/gdm-\d{4}-\d{2}-\d{2}-\d{6}\.sqlite$/', $files[0]);

        array_map('unlink', $files);
        rmdir($dir);
        unlink($source);
    }

    public function test_backup_fails_for_memory_database(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        $this->artisan('gdm:backup')->assertFailed();
    }
}
