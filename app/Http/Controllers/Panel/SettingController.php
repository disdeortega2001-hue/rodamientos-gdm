<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const KEYS = ['usd_rate', 'business_name', 'whatsapp_number', 'city', 'rif', 'address', 'hours', 'payment_methods', 'home_text'];

    public function edit(): View
    {
        $values = collect(self::KEYS)->mapWithKeys(fn (string $key) => [$key => Setting::get($key)]);

        return view('panel.settings.edit', ['values' => $values]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'usd_rate' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'business_name' => ['required', 'string', 'max:100'],
            'whatsapp_number' => ['required', 'regex:/^[1-9][0-9]{9,14}$/'],
            'city' => ['required', 'string', 'max:100'],
            'rif' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:255'],
            'payment_methods' => ['nullable', 'string', 'max:255'],
            'home_text' => ['nullable', 'string', 'max:500'],
        ], [
            'usd_rate.gt' => 'La tasa debe ser mayor que cero.',
            'usd_rate.numeric' => 'La tasa debe ser un número, por ejemplo 186.42.',
            'whatsapp_number.regex' => 'Escribe el número solo con dígitos y código de país, por ejemplo 584129477195.',
        ]);

        foreach (self::KEYS as $key) {
            Setting::put($key, $data[$key] ?? null);
        }

        return back()->with('status', 'Ajustes guardados.');
    }
}
