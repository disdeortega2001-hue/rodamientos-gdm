<?php

namespace App\Http\Controllers\Panel;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class PaymentController extends Controller
{
    public function store(Request $request, Order $order, PaymentService $payments): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'paid_at' => ['required', 'date'],
        ], ['amount.gt' => 'El monto debe ser mayor que cero.']);

        try {
            $payments->record($order, PaymentMethod::from($data['method']), Money::parseCents($data['amount']), $data['paid_at'], $data['reference'] ?? null, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Pago registrado.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('status', 'Pago eliminado.');
    }
}
