<?php

namespace App\Http\Controllers\Admin;

use App\Exports\RefundsExport;
use App\Http\Controllers\Controller;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class RefundController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Refund::class);

        return view('admin.refunds.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Refund::class);

        $query = Refund::query()
            ->with(['order:id,order_number', 'requestedBy:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return DataTables::of($query)
            ->addColumn('order_number', fn (Refund $refund) => $refund->order?->order_number ?? '—')
            ->addColumn('customer', fn (Refund $refund) => $refund->requestedBy?->name ?? '—')
            ->addColumn('amount_formatted', fn (Refund $refund) => format_money($refund->refund_amount))
            ->addColumn('status', function (Refund $refund) {
                $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                $badge = $map[$refund->status] ?? 'secondary';

                return '<span class="badge bg-'.$badge.'">'.e(ucfirst($refund->status)).'</span>';
            })
            ->addColumn('action', function (Refund $refund) {
                return '<a href="'.route('admin.refunds.show', $refund).'" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>';
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function show(Refund $refund): View
    {
        $this->authorize('view', $refund);

        $refund->load([
            'order.items',
            'orderItem',
            'requestedBy',
            'processedBy',
        ]);

        return view('admin.refunds.show', compact('refund'));
    }

    public function approve(Request $request, Refund $refund): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $refund);

        $data = $request->validate([
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($refund->status !== 'pending') {
            $message = 'Only pending refunds can be approved.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        $refund->update([
            'status' => 'approved',
            'admin_remarks' => $data['admin_remarks'] ?? $refund->admin_remarks,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        activity_log('approved', 'refunds', "Approved refund #{$refund->id}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Refund approved.']);
        }

        return back()->with('success', 'Refund approved.');
    }

    public function reject(Request $request, Refund $refund): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $refund);

        $data = $request->validate([
            'admin_remarks' => ['required', 'string', 'max:1000'],
        ]);

        if ($refund->status !== 'pending') {
            $message = 'Only pending refunds can be rejected.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        $refund->update([
            'status' => 'rejected',
            'admin_remarks' => $data['admin_remarks'],
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        activity_log('rejected', 'refunds', "Rejected refund #{$refund->id}");

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Refund rejected.']);
        }

        return back()->with('success', 'Refund rejected.');
    }

    public function export(): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('refunds.view'), 403);

        return Excel::download(new RefundsExport, 'refunds-'.now()->format('Ymd-His').'.xlsx');
    }
}
