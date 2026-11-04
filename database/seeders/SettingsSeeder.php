<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'business_name' => 'Rodamiento GDM',
            'whatsapp_number' => '584129477195',
            'city' => 'Valle de la Pascua, Guárico',
        ] as $key => $value) {
            Setting::put($key, $value);
        }
    }
}
