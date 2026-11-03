<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryNoteController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');

        $orders = Order::with(['customer', 'items', 'payments'])
            ->whereNotNull('delivery_note_number')
            ->when(is_string($search) && $search !== '', function ($q) use ($search) {
                $q->where(fn ($w) => $w->where('delivery_note_number', 'like', "%{$search}%")
                    ->orWhere('number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")));
            })
            ->orderByDesc('delivery_note_issued_at')->orderByDesc('id')
            ->paginate(30)->withQueryString();

        return view('panel.notes.index', compact('orders'));
    }
}
