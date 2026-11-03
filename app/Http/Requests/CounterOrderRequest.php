<?php

namespace App\Http\Requests;

use App\Support\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class CounterOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:30', function (string $attr, mixed $value, Closure $fail) {
                $length = strlen(Phone::normalize((string) $value));
                if ($length < 10 || $length > 15) {
                    $fail('Escribe un teléfono válido, por ejemplo 0412-123-4567.');
                }
            }],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Agrega al menos una pieza.',
            'items.*.product_id.required' => 'Elige un producto en cada línea.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))->filter(fn ($i) => is_array($i) && ! empty($i['product_id']))->values()->all();
        $this->merge(['items' => $items]);
    }
}
