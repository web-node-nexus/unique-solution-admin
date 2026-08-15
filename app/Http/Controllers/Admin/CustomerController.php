<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('customers.view'), 403);

        return view('admin.customers.index');
    }

    public function datatable(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('customers.view'), 403);

        $query = User::query()
            ->customers()
            ->withCount('orders')
            ->withSum('orders as lifetime_spend', 'total_amount')
            ->withMax('orders as last_order_at', 'created_at')
            ->latest();

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        return DataTables::of($query)
            ->addColumn('lifetime_spend_formatted', fn (User $customer) => format_money($customer->lifetime_spend ?? 0))
            ->addColumn('last_order', fn (User $customer) => $customer->last_order_at
                ? \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('d M Y')
                : '—')
            ->addColumn('status', function (User $customer) {
                $badge = $customer->is_active ? 'success' : 'danger';
                $label = $customer->is_active ? 'Active' : 'Blocked';

                return '<span class="badge bg-'.$badge.'">'.$label.'</span>';
            })
            ->addColumn('action', function (User $customer) {
                $buttons = '<a href="'.route('admin.customers.show', $customer).'" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i></a>';
                if (auth()->user()?->can('customers.update')) {
                    $label = $customer->is_active ? 'Block' : 'Unblock';
                    $buttons .= '<form action="'.route('admin.customers.toggle-block', $customer).'" method="POST" class="d-inline" data-confirm="'.$label.' this customer?">'
                        .csrf_field()
                        .'<button type="submit" class="btn btn-sm btn-outline-warning">'.$label.'</button></form>';
                }

                return $buttons;
            })
            ->rawColumns(['action', 'status'])
            ->make(true);
    }

    public function show(User $customer): View
    {
        abort_unless(auth()->user()?->can('customers.view'), 403);
        abort_if($customer->isAdmin(), 404);

        $customer->load(['addresses', 'orders' => fn ($q) => $q->latest()->limit(20)]);

        return view('admin.customers.show', compact('customer'));
    }

    public function toggleBlock(User $customer): RedirectResponse|JsonResponse
    {
        abort_unless(auth()->user()?->can('customers.update'), 403);
        abort_if($customer->isAdmin(), 404);

        $customer->update(['is_active' => ! $customer->is_active]);

        $state = $customer->is_active ? 'unblocked' : 'blocked';
        activity_log($state, 'customers', "Customer #{$customer->id} {$state}");

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Customer {$state}.",
                'is_active' => $customer->is_active,
            ]);
        }

        return back()->with('success', "Customer {$state} successfully.");
    }
}
