<?php

namespace App\Http\Controllers\Panel;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ReportService $reports): View
    {
        return view('panel.dashboard', [
            'summary' => auth()->user()->can('manage-all') ? $reports->summary(now()->startOfMonth(), now()) : null,
            'newOrders' => Order::with(['customer', 'items'])->where('status', OrderStatus::New)->latest('id')->limit(10)->get(),
            'lowStock' => Product::lowStock()->orderBy('stock')->limit(10)->get(),
        ]);
    }
}
