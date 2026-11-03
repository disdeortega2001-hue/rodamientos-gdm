<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::withCount('orders')
            ->when($request->query('q'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
            ->orderBy('name')->paginate(30)->withQueryString();

        return view('panel.customers.index', compact('customers'));
    }

    public function show(Customer $customer): View
    {
        return view('panel.customers.show', ['customer' => $customer, 'orders' => $customer->orders()->with(['items', 'payments'])->paginate(30)]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]));

        return back()->with('status', 'Cliente actualizado.');
    }
}
