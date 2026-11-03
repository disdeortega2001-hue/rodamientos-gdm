<?php

namespace App\Http\Requests;

use App\Support\Phone;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'token' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_visible', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe tu nombre.',
            'name.min' => 'Escribe tu nombre.',
            'phone.required' => 'Escribe tu teléfono.',
            'items.required' => 'Tu pedido está vacío.',
            'items.*.product_id.exists' => 'Uno de los productos ya no está disponible.',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1.',
            'items.*.quantity.max' => 'La cantidad máxima por producto es 999.',
        ];
    }
}
