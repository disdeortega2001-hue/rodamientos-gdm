<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\CounterOrderRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\QuoteService;
use App\Support\Phone;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(Request $request): View
    {
        $quotes = Quote::with(['customer', 'items'])
            ->when($request->query('q'), fn ($q, $s) => $q->where('number', 'like', "%{$s}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$s}%")))
            ->latest('id')->paginate(30)->withQueryString();

        return view('panel.quotes.index', compact('quotes'));
    }

    public function create(): View
    {
        return view('panel.quotes.create', ['products' => Product::orderBy('code')->get()]);
    }

    public function store(CounterOrderRequest $request, QuoteService $quotes): RedirectResponse
    {
        $data = $request->validated();
        $quote = $quotes->create($data['items'], Customer::fromStaff($data['name'], $data['phone']), $data['note'] ?? null);

        return redirect('/panel/cotizaciones/'.$quote->id)->with('status', 'Cotización creada.');
    }

    public function show(Quote $quote): View
    {
        $quote->load(['customer', 'items', 'order']);
        $link = url('/cotizacion/'.$quote->public_token);
        $business = Setting::get('business_name', 'Rodamiento GDM');

        return view('panel.quotes.show', [
            'quote' => $quote,
            'link' => $link,
            'whatsappUrl' => WhatsApp::urlTo(
                Phone::international($quote->customer->phone),
                "Hola, {$quote->customer->name}. Aquí tienes tu cotización {$quote->number} de {$business}: {$link}"
            ),
        ]);
    }

    public function convert(Request $request, Quote $quote, QuoteService $quotes): RedirectResponse
    {
        $order = $quotes->convert($quote, $request->user());

        return redirect('/panel/pedidos/'.$order->id)->with('status', 'Pedido '.$order->number.' creado desde la cotización.');
    }
}
