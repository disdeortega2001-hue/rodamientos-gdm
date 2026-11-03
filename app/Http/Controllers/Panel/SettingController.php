<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const KEYS = ['business_name', 'whatsapp_number', 'city', 'rif', 'address', 'hours', 'payment_methods', 'home_text'];

    public function edit(): View
    {
        $values = collect([...self::KEYS, 'usd_rate'])->mapWithKeys(fn (string $key) => [$key => Setting::get($key)]);

        return view('panel.settings.edit', ['values' => $values, 'autoRate' => Setting::get('usd_rate_auto', '1') === '1']);
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

        $auto = $request->boolean('usd_rate_auto');
        Setting::put('usd_rate_auto', $auto ? '1' : '0');

        if (! $auto) {
            Setting::put('usd_rate', $data['usd_rate'] ?? null);
        }

        if (config('gdm.portal_private')) {
            Setting::put('portal_public', $request->boolean('portal_public') ? '1' : '0');
        }

        return back()->with('status', 'Ajustes guardados.');
    }
}
