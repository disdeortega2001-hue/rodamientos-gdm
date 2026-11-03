<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class Backup extends Command
{
    protected $signature = 'gdm:backup';

    protected $description = 'Guarda una copia fechada de la base de datos';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $dir = config('backup.path', storage_path('app/backups'));
        $stamp = now()->format('Y-m-d-His');
        File::ensureDirectoryExists($dir);

        if ($config['driver'] === 'sqlite') {
            if ($config['database'] === ':memory:' || ! is_file($config['database'])) {
                $this->error('No hay un archivo de base de datos para respaldar.');

                return self::FAILURE;
            }
            $target = "{$dir}/gdm-{$stamp}.sqlite";
            File::copy($config['database'], $target);
        } else {
            $target = "{$dir}/gdm-{$stamp}.sql";
            $result = Process::env(['MYSQL_PWD' => (string) $config['password']])->run([
                'mysqldump', '--host='.$config['host'], '--port='.$config['port'], '--user='.$config['username'],
                '--single-transaction', '--result-file='.$target, $config['database'],
            ]);

            if ($result->failed()) {
                $this->error('No se pudo crear el respaldo: '.$result->errorOutput());

                return self::FAILURE;
            }
        }

        $this->info('Respaldo guardado en '.$target);

        return self::SUCCESS;
    }
}
