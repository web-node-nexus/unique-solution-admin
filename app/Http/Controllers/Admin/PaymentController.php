<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('payments.view'), 403);

        return view('admin.payments.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('payments.view'), 403);

        $query = Payment::query()
            ->with('order:id,order_number,user_id')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        return DataTables::of($query)
            ->addColumn('order_number', fn (Payment $payment) => $payment->order?->order_number ?? '—')
            ->addColumn('amount_formatted', fn (Payment $payment) => format_money($payment->amount))
            ->addColumn('status', function (Payment $payment) {
                $map = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'info'];
                $badge = $map[$payment->status] ?? 'secondary';

                return '<span class="badge bg-'.$badge.'">'.e(ucfirst($payment->status)).'</span>';
            })
            ->addColumn('action', function (Payment $payment) {
                if (! auth()->user()?->can('payments.update')) {
                    return '';
                }

                return '<button type="button" class="btn btn-sm btn-outline-primary btn-update-payment" data-id="'.$payment->id.'">Update</button>';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function updateStatus(Request $request, Payment $payment): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->can('payments.update'), 403);

        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,failed,refunded'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($payment, $data) {
            $payment->update([
                'status' => $data['status'],
                'transaction_id' => $data['transaction_id'] ?? $payment->transaction_id,
                'paid_at' => $data['status'] === 'paid' ? ($payment->paid_at ?? now()) : $payment->paid_at,
            ]);

            if ($payment->order) {
                $orderStatus = match ($data['status']) {
                    'paid' => 'paid',
                    'failed' => 'failed',
                    'refunded' => 'refunded',
                    default => 'pending',
                };
                $payment->order->update(['payment_status' => $orderStatus]);
            }
        });

        activity_log('status_updated', 'payments', "Payment #{$payment->id} → {$data['status']}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Payment status updated.']);
        }

        return back()->with('success', 'Payment status updated.');
    }

    public function reconciliation(Request $request): View
    {
        abort_unless(auth()->user()?->can('payments.view'), 403);

        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $summary = Payment::query()
            ->selectRaw('status, COUNT(*) as count, SUM(amount) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->get();

        $ordersWithoutPayment = Order::query()
            ->whereDoesntHave('payments')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $mismatched = Order::query()
            ->where('payment_status', 'paid')
            ->whereDoesntHave('payments', fn ($q) => $q->where('status', 'paid'))
            ->whereBetween('created_at', [$from, $to])
            ->with('user:id,name')
            ->limit(50)
            ->get();

        return view('admin.payments.reconciliation', compact(
            'summary',
            'ordersWithoutPayment',
            'mismatched',
            'from',
            'to'
        ));
    }
}
