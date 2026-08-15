<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use App\Services\DashboardService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected OrderService $orderService
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('dashboard.view'), 403);

        $payload = $this->dashboardService->dashboardPayload();

        return view('admin.dashboard.index', [
            'stats' => $payload['stats'],
            'salesTrend' => $payload['sales_last_30_days'],
            'categorySales' => $payload['category_sales'],
            'topProducts' => $payload['top_products'],
            'recentOrders' => $payload['recent_orders'],
            'lowStockVariants' => $payload['low_stock'],
        ]);
    }

    public function quickUpdateOrderStatus(OrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('update', $order);

        try {
            $updated = $this->orderService->updateStatus(
                $order,
                $request->validated('order_status'),
                $request->validated('remarks'),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Order status updated.',
                'order_status' => $updated->order_status,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
