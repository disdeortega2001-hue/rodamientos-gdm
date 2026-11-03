<?php

namespace Tests\Feature\Panel;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesCatalog;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function order(int $priceCents = 3700): Order
    {
        $p = $this->product(['price_cents' => $priceCents]);

        return app(OrderService::class)->create([['product_id' => $p->id, 'quantity' => 1]], $this->customer(), 'web', null);
    }

    private function pay(Order $order, string $amount): \Illuminate\Testing\TestResponse
    {
        return $this->post("/panel/pedidos/{$order->id}/pagos", ['method' => 'pago_movil', 'reference' => 'REF1', 'amount' => $amount, 'paid_at' => '2026-11-02']);
    }

    public function test_payment_status(): void
    {
        $order = $this->order();
        $this->actingAs($this->seller());
        $this->assertSame(PaymentStatus::Pending, $order->paymentStatus());
        $this->pay($order, '10')->assertSessionHasNoErrors();
        $this->assertSame(PaymentStatus::Partial, $order->fresh()->paymentStatus());
        $this->pay($order, '27')->assertSessionHasNoErrors();
        $this->assertSame(PaymentStatus::Paid, $order->fresh()->paymentStatus());
    }

    public function test_overpayment_rejected(): void
    {
        $order = $this->order();
        $this->actingAs($this->seller());
        $this->pay($order, '37.01')->assertSessionHasErrors('amount');
        $this->assertSame(0, Payment::count());
    }

    public function test_zero_payment_rejected(): void
    {
        $order = $this->order();
        $this->actingAs($this->seller());
        $this->pay($order, '0')->assertSessionHasErrors('amount');
    }

    public function test_payment_stores_method_reference_and_user(): void
    {
        $user = $this->seller();
        $order = $this->order();
        $this->actingAs($user);
        $this->pay($order, '5');
        $payment = Payment::firstOrFail();
        $this->assertSame(PaymentMethod::PagoMovil, $payment->method);
        $this->assertSame('REF1', $payment->reference);
        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame(500, $payment->amount_cents);
    }

    public function test_payment_on_cancelled_order_rejected(): void
    {
        $order = $this->order();
        $user = $this->seller();
        app(OrderService::class)->cancel($order, $user);
        $this->actingAs($user);
        $this->pay($order, '5')->assertSessionHasErrors('amount');
    }

    public function test_payment_on_unpriced_order_rejected(): void
    {
        $p = $this->product(['price_cents' => null]);
        $order = app(OrderService::class)->create([['product_id' => $p->id, 'quantity' => 1]], $this->customer(), 'web', null);
        $this->actingAs($this->seller());
        $this->pay($order, '5')->assertSessionHasErrors('amount');
    }

    public function test_only_admin_deletes_payment(): void
    {
        $order = $this->order();
        $this->actingAs($this->seller());
        $this->pay($order, '5');
        $payment = Payment::firstOrFail();
        $this->delete("/panel/pagos/{$payment->id}")->assertForbidden();
        $this->actingAs($this->admin())->delete("/panel/pagos/{$payment->id}")->assertRedirect();
        $this->assertSame(0, Payment::count());
    }
}
