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
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(): View
    {
        $this->authorize('viewAny', Order::class);

        return $this->boardView(null);
    }

    public function byStatus(string $status): View
    {
        $this->authorize('viewAny', Order::class);

        // Accept legacy statuses + board tabs (assigned)
        $tab = in_array($status, OrderService::BOARD_TABS, true)
            ? $status
            : (in_array($status, OrderService::STATUSES, true)
                ? OrderService::boardTabForStatus($status)
                : null);

        abort_unless($tab !== null, 404);

        return $this->boardView($tab);
    }

    protected function boardView(?string $lockedTab): View
    {
        $meta = OrderService::boardMeta();
        $activeKey = $lockedTab ?? 'all';
        $activeMeta = $meta[$activeKey] ?? $meta['all'];

        $counts = Order::query()
            ->select('order_status', DB::raw('count(*) as aggregate'))
            ->groupBy('order_status')
            ->pluck('aggregate', 'order_status');

        $tabCounts = [];
        foreach (OrderService::BOARD_TABS as $tab) {
            $tabCounts[$tab] = 0;
            foreach (OrderService::statusesForBoardTab($tab) as $status) {
                $tabCounts[$tab] += (int) ($counts[$status] ?? 0);
            }
        }
        $tabCounts['all'] = (int) array_sum($tabCounts);

        return view('admin.orders.index', [
            'statuses' => OrderService::STATUSES,
            'boardTabs' => OrderService::BOARD_TABS,
            'lockedTab' => $lockedTab,
            'activeMeta' => $activeMeta,
            'tabCounts' => $tabCounts,
            'boardMeta' => $meta,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()
            ->with([
                'user:id,name,email,phone',
                'items',
                'payments' => fn ($q) => $q->latest('id'),
            ])
            ->latest();

        $boardTab = $request->input('board_tab');
        if ($boardTab && in_array($boardTab, OrderService::BOARD_TABS, true)) {
            $query->whereIn('order_status', OrderService::statusesForBoardTab($boardTab));
        } elseif ($request->filled('order_status')) {
            $status = (string) $request->input('order_status');
            if ($status === 'assigned') {
                $query->whereIn('order_status', OrderService::statusesForBoardTab('assigned'));
            } elseif (in_array($status, OrderService::STATUSES, true)) {
                $query->where('order_status', $status);
            }
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
            $query->where(function ($q) use ($customer) {
                $q->where('order_number', 'like', "%{$customer}%")
                    ->orWhere('shipping_address', 'like', "%{$customer}%")
                    ->orWhereHas('user', function ($uq) use ($customer) {
                        $uq->where('name', 'like', "%{$customer}%")
                            ->orWhere('email', 'like', "%{$customer}%")
                            ->orWhere('phone', 'like', "%{$customer}%");
                    });
            });
        }

        $canUpdate = auth()->user()?->can('orders.update') ?? false;

        return DataTables::of($query)
            ->addColumn('order_block', function (Order $order) {
                $date = $order->created_at?->format('d/m/Y') ?? '—';

                return '<div class="ord-id">'.e($order->order_number).'</div>'
                    .'<div class="ord-sub">'.e($date).'</div>';
            })
            ->addColumn('customer_block', function (Order $order) {
                $name = e($order->user?->name ?? 'Guest');
                $phone = e($order->user?->phone ?: ($order->user?->email ?? '—'));

                return '<div class="ord-name">'.$name.'</div>'
                    .'<div class="ord-sub">'.$phone.'</div>';
            })
            ->addColumn('address_block', function (Order $order) {
                $address = trim((string) ($order->shipping_address ?: '—'));

                return '<div class="ord-address">'
                    .'<i class="bi bi-geo-alt-fill"></i>'
                    .'<span>'.e($address).'</span>'
                    .'</div>';
            })
            ->addColumn('product_block', function (Order $order) {
                $items = $order->items;
                $first = $items->first();
                $label = $first?->product_name_snapshot ?: '—';
                $extra = $items->count() > 1 ? ' +'.($items->count() - 1).' more' : '';

                return '<div class="ord-product">'.e($label).e($extra).'</div>'
                    .'<div class="ord-price">'.e(format_money($order->total_amount)).'</div>';
            })
            ->addColumn('payment_block', function (Order $order) {
                $payment = $order->payments->first();
                $method = strtolower((string) ($payment?->payment_method ?? 'cod'));
                $date = ($payment?->paid_at ?? $order->created_at)?->format('d/m/Y') ?? '—';

                [$cls, $icon, $label] = match (true) {
                    in_array($method, ['upi', 'phonepe'], true) => ['pay-upi', 'bi-lightning-charge-fill', 'PhonePe'],
                    $method === 'razorpay' => ['pay-online', 'bi-credit-card-2-front-fill', 'Razorpay'],
                    default => ['pay-cod', 'bi-cash-stack', 'COD'],
                };

                return '<span class="ord-pay '.$cls.'"><i class="bi '.$icon.'"></i> '.e($label).'</span>'
                    .'<div class="ord-sub mt-1">'.e($date).'</div>';
            })
            ->addColumn('action', function (Order $order) use ($canUpdate) {
                $view = '<a href="'.route('admin.orders.show', $order).'" class="ord-btn-view" title="View">'
                    .'<i class="bi bi-eye"></i></a>';

                if (! $canUpdate || $order->order_status !== 'pending') {
                    return '<div class="ord-actions">'.$view.'</div>';
                }

                $confirmUrl = route('admin.orders.update-status', $order);
                $confirm = '<button type="button" class="ord-btn ord-btn-confirm" data-status-url="'.e($confirmUrl).'" data-status="confirmed">'
                    .'<i class="bi bi-check-lg"></i> कन्फर्म</button>';
                $cancel = '<button type="button" class="ord-btn ord-btn-cancel" data-status-url="'.e($confirmUrl).'" data-status="cancelled">'
                    .'<i class="bi bi-slash-circle"></i> कैंसिल</button>';

                return '<div class="ord-actions">'.$confirm.$cancel.$view.'</div>';
            })
            ->rawColumns(['order_block', 'customer_block', 'address_block', 'product_block', 'payment_block', 'action'])
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
