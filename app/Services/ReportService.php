<?php

namespace App\Services;

use App\Enums\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /** @return array{sales_cents:int, cost_cents:int, profit_cents:int} */
    public function summary(Carbon $from, Carbon $to): array
    {
        $row = $this->lines($from, $to)
            ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.unit_price_cents), 0) AS sales')
            ->selectRaw('COALESCE(SUM(order_items.quantity * order_items.unit_cost_cents), 0) AS cost')
            ->first();

        return [
            'sales_cents' => (int) $row->sales,
            'cost_cents' => (int) $row->cost,
            'profit_cents' => (int) $row->sales - (int) $row->cost,
        ];
    }

    public function byProduct(Carbon $from, Carbon $to): Collection
    {
        return $this->lines($from, $to)
            ->select('order_items.code')
            ->selectRaw('SUM(order_items.quantity) AS quantity')
            ->selectRaw('SUM(order_items.quantity * order_items.unit_price_cents) AS sales_cents')
            ->selectRaw('SUM(order_items.quantity * order_items.unit_cost_cents) AS cost_cents')
            ->groupBy('order_items.code')
            ->orderBy('order_items.code')
            ->get()
            ->each(function (object $row) {
                $row->profit_cents = (int) $row->sales_cents - (int) $row->cost_cents;
            });
    }

    private function lines(Carbon $from, Carbon $to): Builder
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', [OrderStatus::Confirmed->value, OrderStatus::Delivered->value])
            ->whereBetween('orders.confirmed_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }
}
