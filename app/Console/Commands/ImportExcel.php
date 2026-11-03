<?php

namespace App\Console\Commands;

use App\Services\ExcelImporter;
use Illuminate\Console\Command;
use RuntimeException;

class ImportExcel extends Command
{
    protected $signature = 'gdm:import-excel {path}';

    protected $description = 'Carga precios, costos, existencias y ventas previas desde el Excel de cotización';

    public function handle(ExcelImporter $importer): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error('No se encontró el archivo.');

            return self::FAILURE;
        }

        try {
            $totals = $importer->import($path);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Carga completa: {$totals['products']} productos, {$totals['stock']} unidades en existencia.");

        return self::SUCCESS;
    }
}
