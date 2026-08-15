<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RefundsExport;
use App\Exports\SalesReportExport;
use App\Exports\StockValuationExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Services\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function sales(Request $request): View|BinaryFileResponse|Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        [$from, $to] = $this->dateRange($request);

        $orders = Order::query()
            ->with('user:id,name,email')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('order_status', ['cancelled'])
            ->orderByDesc('created_at')
            ->get();

        $summary = [
            'orders_count' => $orders->count(),
            'revenue' => (float) $orders->sum('total_amount'),
            'avg_order' => $orders->count() > 0 ? (float) $orders->avg('total_amount') : 0,
        ];

        if ($request->input('export') === 'excel') {
            abort_unless(auth()->user()?->can('reports.export'), 403);

            return Excel::download(new SalesReportExport($from, $to), 'sales-report.xlsx');
        }

        if ($request->input('export') === 'pdf') {
            abort_unless(auth()->user()?->can('reports.export'), 403);
            $pdf = Pdf::loadView('admin.reports.sales-pdf', compact('orders', 'summary', 'from', 'to'));

            return $pdf->download('sales-report.pdf');
        }

        return view('admin.reports.sales', compact('orders', 'summary', 'from', 'to'));
    }

    public function category(Request $request): View|BinaryFileResponse|Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        [$from, $to] = $this->dateRange($request);
        $chart = $this->dashboardService->categorySales($from, $to);

        $rows = OrderItem::query()
            ->selectRaw('categories.id, categories.name, SUM(order_items.quantity) as units, SUM(order_items.subtotal) as revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.order_status', ['cancelled', 'returned'])
            ->whereNull('orders.deleted_at')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->get();

        if ($request->input('export') === 'pdf') {
            abort_unless(auth()->user()?->can('reports.export'), 403);
            $pdf = Pdf::loadView('admin.reports.category-pdf', compact('rows', 'from', 'to'));

            return $pdf->download('category-report.pdf');
        }

        return view('admin.reports.category', compact('rows', 'chart', 'from', 'to'));
    }

    public function brand(Request $request): View|Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        [$from, $to] = $this->dateRange($request);

        $rows = OrderItem::query()
            ->selectRaw('brands.id, brands.name, SUM(order_items.quantity) as units, SUM(order_items.subtotal) as revenue')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.order_status', ['cancelled', 'returned'])
            ->whereNull('orders.deleted_at')
            ->groupBy('brands.id', 'brands.name')
            ->orderByDesc('revenue')
            ->get();

        if ($request->input('export') === 'pdf') {
            abort_unless(auth()->user()?->can('reports.export'), 403);
            $pdf = Pdf::loadView('admin.reports.brand-pdf', compact('rows', 'from', 'to'));

            return $pdf->download('brand-report.pdf');
        }

        return view('admin.reports.brand', compact('rows', 'from', 'to'));
    }

    public function bestSelling(Request $request): View|Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        [$from, $to] = $this->dateRange($request);
        $limit = (int) $request->input('limit', 20);
        $products = $this->dashboardService->topProducts($limit, $from, $to);

        if ($request->input('export') === 'pdf') {
            abort_unless(auth()->user()?->can('reports.export'), 403);
            $pdf = Pdf::loadView('admin.reports.best-selling-pdf', compact('products', 'from', 'to'));

            return $pdf->download('best-selling-report.pdf');
        }

        return view('admin.reports.best-selling', compact('products', 'from', 'to'));
    }

    public function refunds(Request $request): View|BinaryFileResponse|Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        [$from, $to] = $this->dateRange($request);

        $refunds = Refund::query()
            ->with(['order:id,order_number', 'requestedBy:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $summary = [
            'count' => $refunds->count(),
            'approved_amount' => (float) $refunds->where('status', 'approved')->sum('refund_amount'),
            'pending_count' => $refunds->where('status', 'pending')->count(),
        ];

        if ($request->input('export') === 'excel') {
            abort_unless(auth()->user()?->can('reports.export'), 403);

            return Excel::download(new RefundsExport, 'refunds-report.xlsx');
        }

        if ($request->input('export') === 'pdf') {
            abort_unless(auth()->user()?->can('reports.export'), 403);
            $pdf = Pdf::loadView('admin.reports.refunds-pdf', compact('refunds', 'summary', 'from', 'to'));

            return $pdf->download('refunds-report.pdf');
        }

        return view('admin.reports.refunds', compact('refunds', 'summary', 'from', 'to'));
    }

    public function stockValuation(Request $request): View|BinaryFileResponse|Response
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $variants = ProductVariant::query()
            ->with(['product:id,name,status', 'attributeValues'])
            ->orderBy('sku')
            ->get();

        $summary = [
            'sku_count' => $variants->count(),
            'total_units' => (int) $variants->sum('stock_quantity'),
            'total_value' => (float) $variants->sum(function (ProductVariant $variant) {
                $unit = (float) ($variant->discount_price ?? $variant->price);

                return $unit * (int) $variant->stock_quantity;
            }),
        ];

        if ($request->input('export') === 'excel') {
            abort_unless(auth()->user()?->can('reports.export'), 403);

            return Excel::download(new StockValuationExport, 'stock-valuation.xlsx');
        }

        if ($request->input('export') === 'pdf') {
            abort_unless(auth()->user()?->can('reports.export'), 403);
            $pdf = Pdf::loadView('admin.reports.stock-valuation-pdf', compact('variants', 'summary'));

            return $pdf->download('stock-valuation.pdf');
        }

        return view('admin.reports.stock-valuation', compact('variants', 'summary'));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::now()->endOfDay();

        return [$from, $to];
    }
}
