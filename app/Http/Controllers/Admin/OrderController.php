<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Order::class);

        return view('admin.orders.index', [
            'statuses' => OrderService::STATUSES,
            'lockedStatus' => null,
        ]);
    }

    public function byStatus(string $status): View
    {
        $this->authorize('viewAny', Order::class);

        abort_unless(in_array($status, OrderService::STATUSES, true), 404);

        return view('admin.orders.index', [
            'statuses' => OrderService::STATUSES,
            'lockedStatus' => $status,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()
            ->with('user:id,name,email')
            ->latest();

        if ($request->filled('order_status')) {
            $query->where('order_status', $request->input('order_status'));
        }

        if ($request->filled('locked_status')) {
            $query->where('order_status', $request->input('locked_status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        if ($request->filled('customer')) {
            $customer = $request->input('customer');
            $query->whereHas('user', function ($q) use ($customer) {
                $q->where('name', 'like', "%{$customer}%")
                    ->orWhere('email', 'like', "%{$customer}%")
                    ->orWhere('phone', 'like', "%{$customer}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('customer', fn (Order $order) => $order->user?->name ?? 'Guest')
            ->addColumn('status', function (Order $order) {
                $map = [
                    'pending' => 'warning',
                    'confirmed' => 'info',
                    'processing' => 'primary',
                    'shipped' => 'info',
                    'delivered' => 'success',
                    'cancelled' => 'danger',
                    'returned' => 'secondary',
                ];
                $badge = $map[$order->order_status] ?? 'secondary';

                return '<span class="badge bg-'.$badge.'">'.e(ucfirst($order->order_status)).'</span>';
            })
            ->addColumn('payment', function (Order $order) {
                $map = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'info'];
                $badge = $map[$order->payment_status] ?? 'secondary';

                return '<span class="badge bg-'.$badge.'">'.e(ucfirst($order->payment_status)).'</span>';
            })
            ->addColumn('total_formatted', fn (Order $order) => format_money($order->total_amount))
            ->addColumn('action', function (Order $order) {
                return '<a href="'.route('admin.orders.show', $order).'" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>';
            })
            ->rawColumns(['action', 'status', 'payment'])
            ->make(true);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load([
            'user',
            'items.variant.product',
            'items.variant.attributeValues',
            'statusHistory.changedBy',
            'payments',
            'refunds',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => OrderService::STATUSES,
        ]);
    }

    public function updateStatus(OrderStatusRequest $request, Order $order): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $order);

        try {
            $updated = $this->orderService->updateStatus(
                $order,
                $request->validated('order_status'),
                $request->validated('remarks'),
                $request->user()
            );
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Order status updated.',
                'order_status' => $updated->order_status,
            ]);
        }

        return back()->with('success', 'Order status updated.');
    }

    public function invoice(Order $order): Response
    {
        $this->authorize('view', $order);

        $order->load([
            'user',
            'items.variant.product',
            'items.variant.attributeValues',
        ]);

        $pdf = Pdf::loadView('admin.orders.invoice', [
            'order' => $order,
            'shopName' => shop_name(),
            'shopAddress' => shop_address(),
        ]);

        return $pdf->download('invoice-'.$order->order_number.'.pdf');
    }

    public function packingSlip(Order $order): View|Response
    {
        $this->authorize('view', $order);

        $order->load([
            'user',
            'items.variant.product',
            'items.variant.attributeValues',
        ]);

        if (request()->boolean('pdf')) {
            $pdf = Pdf::loadView('admin.orders.packing-slip', compact('order'));

            return $pdf->download('packing-slip-'.$order->order_number.'.pdf');
        }

        return view('admin.orders.packing-slip', compact('order'));
    }
}
