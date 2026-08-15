@extends('admin.layouts.app')

@section('title', 'Refund #'.$refund->id)

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Refund #'.$refund->id,
        'breadcrumbs' => [
            'Refunds' => route('admin.refunds.index'),
            '#'.$refund->id,
        ],
        'actions' => '<a href="'.route('admin.refunds.export').'" class="btn btn-outline-primary"><i class="bi bi-download me-1"></i>Export</a>',
    ])

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header">Refund details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="stat-label">Order</div>
                            <div>
                                @if ($refund->order)
                                    <a href="{{ route('admin.orders.show', $refund->order) }}">{{ $refund->order->order_number }}</a>
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-label">Amount</div>
                            <div class="fw-bold">{{ format_money($refund->refund_amount) }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-label">Status</div>
                            <div><span class="badge bg-secondary">{{ ucfirst($refund->status) }}</span></div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-label">Requested by</div>
                            <div>{{ $refund->requestedBy?->name ?? '—' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="stat-label">Reason</div>
                            <div>{{ $refund->reason }}</div>
                        </div>
                        @if ($refund->admin_remarks)
                            <div class="col-12">
                                <div class="stat-label">Admin remarks</div>
                                <div>{{ $refund->admin_remarks }}</div>
                            </div>
                        @endif
                        @if ($refund->processedBy)
                            <div class="col-md-6">
                                <div class="stat-label">Processed by</div>
                                <div>{{ $refund->processedBy->name }} · {{ $refund->processed_at?->format('d M Y, h:i A') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($refund->orderItem)
                <div class="card">
                    <div class="card-header">Related item</div>
                    <div class="card-body">
                        <div class="fw-semibold">{{ $refund->orderItem->product_name_snapshot }}</div>
                        <div class="small text-muted">Qty {{ $refund->orderItem->quantity }} · {{ format_money($refund->orderItem->subtotal) }}</div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            @if ($refund->status === 'pending')
                @can('refunds.approve')
                    <div class="card mb-3">
                        <div class="card-header">Approve</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="admin_remarks_approve">Remarks (optional)</label>
                                    <textarea name="admin_remarks" id="admin_remarks_approve" rows="2" class="form-control">{{ old('admin_remarks') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-success" data-confirm="Approve this refund?">Approve refund</button>
                            </form>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">Reject</div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label" for="admin_remarks_reject">Remarks <span class="text-danger">*</span></label>
                                    <textarea name="admin_remarks" id="admin_remarks_reject" rows="2" class="form-control @error('admin_remarks') is-invalid @enderror" required>{{ old('admin_remarks') }}</textarea>
                                    @error('admin_remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-outline-danger" data-confirm="Reject this refund?">Reject refund</button>
                            </form>
                        </div>
                    </div>
                @endcan
            @else
                <div class="card">
                    <div class="card-body">
                        <p class="mb-0 text-muted">This refund has already been {{ $refund->status }}.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
