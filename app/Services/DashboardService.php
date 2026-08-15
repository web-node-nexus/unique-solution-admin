<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $revenueQuery = Order::query()
            ->whereNotIn('order_status', ['cancelled', 'returned']);

        return [
            'total_orders' => Order::query()->count(),
            'orders_today' => Order::query()->whereDate('created_at', $today)->count(),
            'orders_this_month' => Order::query()->where('created_at', '>=', $startOfMonth)->count(),
            'pending_orders' => Order::query()->where('order_status', 'pending')->count(),
            'processing_orders' => Order::query()
                ->whereIn('order_status', ['confirmed', 'processing', 'shipped'])
                ->count(),
            'total_revenue' => (float) (clone $revenueQuery)->sum('total_amount'),
            'revenue_today' => (float) (clone $revenueQuery)
                ->whereDate('created_at', $today)
                ->sum('total_amount'),
            'revenue_this_month' => (float) (clone $revenueQuery)
                ->where('created_at', '>=', $startOfMonth)
                ->sum('total_amount'),
            'total_products' => Product::query()->count(),
            'active_products' => Product::query()->where('status', 'active')->count(),
            'low_stock_variants' => ProductVariant::query()
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->count(),
            'out_of_stock_variants' => ProductVariant::query()
                ->where('stock_quantity', '<=', 0)
                ->count(),
            'total_customers' => User::query()->customers()->count(),
            'pending_refunds' => Refund::query()->where('status', 'pending')->count(),
        ];
    }

    /**
     * Sales totals for the last 30 days (including today).
     *
     * @return array{labels: list<string>, data: list<float>}
     */
    public function salesLast30Days(): array
    {
        $start = Carbon::today()->subDays(29);
        $end = Carbon::today()->endOfDay();

        $rows = Order::query()
            ->selectRaw('DATE(created_at) as sale_date, SUM(total_amount) as total')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end])
            ->whereNotIn('order_status', ['cancelled', 'returned'])
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $labels = [];
        $data = [];

        for ($day = $start->copy(); $day->lte(Carbon::today()); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $day->format('d M');
            $data[] = (float) ($rows[$key] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Revenue grouped by product category.
     *
     * @return array{labels: list<string>, data: list<float>}
     */
    public function categorySales(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= Carbon::now()->subDays(30)->startOfDay();
        $to ??= Carbon::now()->endOfDay();

        $rows = OrderItem::query()
            ->selectRaw('categories.name as category_name, SUM(order_items.subtotal) as total')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.order_status', ['cancelled', 'returned'])
            ->whereNull('orders.deleted_at')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('category_name')->map(fn ($name) => (string) $name)->values()->all(),
            'data' => $rows->pluck('total')->map(fn ($total) => (float) $total)->values()->all(),
        ];
    }

    /**
     * Top selling products by quantity / revenue.
     *
     * @return Collection<int, object>
     */
    public function topProducts(int $limit = 10, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= Carbon::now()->subDays(30)->startOfDay();
        $to ??= Carbon::now()->endOfDay();

        return OrderItem::query()
            ->selectRaw('
                products.id as product_id,
                products.name as product_name,
                SUM(order_items.quantity) as units_sold,
                SUM(order_items.subtotal) as revenue
            ')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.order_status', ['cancelled', 'returned'])
            ->whereNull('orders.deleted_at')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units_sold')
            ->limit($limit)
            ->get();
    }

    /**
     * Aggregate payload for dashboard cards + charts.
     *
     * @return array<string, mixed>
     */
    public function dashboardPayload(): array
    {
        return [
            'stats' => $this->getStats(),
            'sales_last_30_days' => $this->salesLast30Days(),
            'category_sales' => $this->categorySales(),
            'top_products' => $this->topProducts(),
            'recent_orders' => Order::query()
                ->with('user:id,name,email')
                ->latest()
                ->limit(8)
                ->get(),
            'low_stock' => ProductVariant::query()
                ->with('product:id,name')
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                ->orderBy('stock_quantity')
                ->limit(8)
                ->get(),
        ];
    }
}
