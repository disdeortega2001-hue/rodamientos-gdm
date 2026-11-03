<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\QuoteService;
use App\Services\ReportService;
use App\Support\Money;
use App\Support\Phone;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesCatalog;
use Tests\TestCase;

class ReviewFixesTest extends TestCase
{
    use CreatesCatalog, RefreshDatabase;

    private function order($product, int $qty = 1): Order
    {
        return app(OrderService::class)->create([['product_id' => $product->id, 'quantity' => $qty]], $this->customer(), 'web', null);
    }

    public function test_report_query_never_subtracts_unsigned_columns(): void
    {
        DB::enableQueryLog();
        app(ReportService::class)->byProduct(Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31'));
        $sql = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        $this->assertStringNotContainsString('unit_price_cents" - ', $sql);
        $this->assertStringNotContainsString('unit_price_cents - ', $sql);
    }

    public function test_below_cost_sale_reports_negative_profit(): void
    {
        $order = $this->order($this->product(['code' => 'A', 'price_cents' => 100, 'avg_cost_cents' => 600]));
        app(OrderService::class)->confirm($order, $this->admin());
        $range = [now()->subDay(), now()->addDay()];
        $this->assertSame(-500, (int) app(ReportService::class)->byProduct(...$range)[0]->profit_cents);
        $this->assertSame(-500, app(ReportService::class)->summary(...$range)['profit_cents']);
        $this->actingAs($this->admin())->get('/panel/reportes?from='.$range[0]->format('Y-m-d').'&to='.$range[1]->format('Y-m-d'))->assertOk()->assertSee('-$5');
    }

    public function test_negative_money_format(): void
    {
        $this->assertSame('-$5', Money::usd(-500));
        $this->assertSame('-$8,50', Money::usd(-850));
    }

    public function test_phone_forms_normalize_to_one_customer_key(): void
    {
        foreach (['412-947-7195', '+58 0412-947-7195', '0058 412 947 7195', '0412 9477195', '584129477195'] as $form) {
            $this->assertSame('04129477195', Phone::normalize($form), $form);
            $this->assertSame('584129477195', Phone::international($form), $form);
        }
    }

    public function test_app_uses_venezuela_time(): void
    {
        $this->assertSame('America/Caracas', config('app.timezone'));
        $this->assertSame('America/Caracas', now()->timezoneName);
    }

    public function test_decimal_comma_is_accepted_in_panel(): void
    {
        $product = $this->product(['code' => 'COMA', 'stock' => 0, 'avg_cost_cents' => 0, 'price_cents' => 5000]);
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post("/panel/inventario/{$product->id}/entradas", ['quantity' => 1, 'unit_cost' => '4,20'])->assertSessionHasNoErrors();
        $this->assertSame(420, $product->refresh()->avg_cost_cents);

        $order = $this->order($product);
        $this->post("/panel/pedidos/{$order->id}/pagos", ['method' => 'cash', 'amount' => '12,50', 'paid_at' => '2026-11-02'])->assertSessionHasNoErrors();
        $this->assertSame(1250, Payment::first()->amount_cents);

        $this->put("/panel/pedidos/{$order->id}/lineas/{$order->items->first()->id}", ['price' => '1.234,50'])->assertSessionHasNoErrors();
        $this->assertSame(123450, $order->items->first()->refresh()->unit_price_cents);

        $this->put('/panel/ajustes', ['business_name' => 'X', 'whatsapp_number' => '584129477195', 'city' => 'Y', 'usd_rate' => '186,42'])->assertSessionHasNoErrors();
        $this->assertSame('186.42', Setting::usdRate());
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $order = $this->order($this->product());
        $this->actingAs($this->seller())
            ->post("/panel/pedidos/{$order->id}/pagos", ['method' => 'cash', 'amount' => 'abc', 'paid_at' => ''])
            ->assertSessionHasErrors(['amount' => 'El campo monto debe ser un número.', 'paid_at' => 'El campo fecha es obligatorio.']);
    }

    public function test_form_token_prevents_double_counter_order(): void
    {
        $p = $this->product();
        $this->actingAs($this->seller());
        $data = ['name' => 'Luis Rojas', 'phone' => '04145551234', 'form_token' => 'token-pedido', 'items' => [['product_id' => $p->id, 'quantity' => 1]]];
        $this->post('/panel/pedidos', $data)->assertRedirect();
        $this->post('/panel/pedidos', $data)->assertRedirect();
        $this->assertSame(1, Order::count());
    }

    public function test_form_token_prevents_double_quote(): void
    {
        $p = $this->product();
        $this->actingAs($this->seller());
        $data = ['name' => 'Luis Rojas', 'phone' => '04145551234', 'form_token' => 'token-cot', 'items' => [['product_id' => $p->id, 'quantity' => 1]]];
        $this->post('/panel/cotizaciones', $data);
        $this->post('/panel/cotizaciones', $data);
        $this->assertSame(1, Quote::count());
    }

    public function test_form_token_prevents_double_payment(): void
    {
        $order = $this->order($this->product(['price_cents' => 5000]));
        $this->actingAs($this->seller());
        $data = ['method' => 'cash', 'amount' => '10', 'paid_at' => '2026-11-02', 'form_token' => 'token-pago'];
        $this->post("/panel/pedidos/{$order->id}/pagos", $data);
        $this->post("/panel/pedidos/{$order->id}/pagos", $data);
        $this->assertSame(1, Payment::count());
    }

    public function test_forms_render_a_form_token(): void
    {
        $order = $this->order($this->product());
        $this->actingAs($this->seller());
        foreach (['/panel/pedidos/nuevo', '/panel/cotizaciones/nueva', "/panel/pedidos/{$order->id}"] as $url) {
            $this->get($url)->assertSee('name="form_token"', false);
        }
    }

    public function test_cancel_restores_only_what_was_taken_when_tracking_turned_on(): void
    {
        $p = $this->product(['track_stock' => false, 'stock' => 0]);
        $order = $this->order($p, 2);
        $user = $this->seller();
        app(OrderService::class)->confirm($order, $user);
        $p->update(['track_stock' => true]);
        app(OrderService::class)->cancel($order, $user);
        $this->assertSame(0, $p->refresh()->stock);
    }

    public function test_cancel_restores_what_was_taken_when_tracking_turned_off(): void
    {
        $p = $this->product(['track_stock' => true, 'stock' => 5]);
        $order = $this->order($p, 2);
        $user = $this->seller();
        app(OrderService::class)->confirm($order, $user);
        $p->update(['track_stock' => false]);
        app(OrderService::class)->cancel($order, $user);
        $this->assertSame(5, $p->refresh()->stock);
    }

    public function test_staff_name_corrects_existing_customer_on_quote(): void
    {
        $p = $this->product();
        Customer::create(['name' => 'Pedro Falso', 'phone' => '04145551234']);
        $this->actingAs($this->seller())->post('/panel/cotizaciones', [
            'name' => 'Juan Real', 'phone' => '0414-555-1234', 'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ]);
        $this->assertSame(1, Customer::count());
        $this->get('/cotizacion/'.Quote::first()->public_token)->assertSee('Juan Real')->assertDontSee('Pedro Falso');
    }

    public function test_staff_name_corrects_existing_customer_on_counter_order(): void
    {
        $p = $this->product();
        Customer::create(['name' => 'Pedro Falso', 'phone' => '04145551234']);
        $this->actingAs($this->seller())->post('/panel/pedidos', [
            'name' => 'Juan Real', 'phone' => '0414-555-1234', 'items' => [['product_id' => $p->id, 'quantity' => 1]],
        ]);
        $this->assertSame('Juan Real', Customer::first()->name);
    }

    public function test_cart_script_handles_stale_cart_and_expired_session(): void
    {
        $script = file_get_contents(public_path('js/cart.js'));
        $this->assertStringContainsString('res.status === 422', $script);
        $this->assertStringContainsString('res.status === 419', $script);
    }

    public function test_composer_lock_supports_php_83(): void
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $tooNew = collect($lock['packages'])->filter(fn ($p) => isset($p['require']['php']) && preg_match('/>=\s*8\.[4-9]/', $p['require']['php']))->pluck('name');
        $this->assertSame([], $tooNew->values()->all());
    }
}
