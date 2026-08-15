@extends('admin.layouts.app')

@section('title', 'Stock History')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Stock history',
        'breadcrumbs' => [
            'Inventory' => route('admin.inventory.index'),
            'History',
        ],
        'actions' => auth()->user()?->can('inventory.create')
            ? '<a href="'.route('admin.inventory.adjust', ['variant_id' => $variant->id]).'" class="btn btn-primary">Adjust</a>'
            : null,
    ])

    <div class="card mb-3">
        <div class="card-body">
            <div class="fw-semibold">{{ $variant->display_name }}</div>
            <div class="small text-muted">SKU: {{ $variant->sku }} · Current: {{ $variant->stock_quantity }} · Threshold: {{ $variant->low_stock_threshold }}</div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            @if ($logs->isEmpty())
                @include('admin.partials.empty-state', [
                    'icon' => 'bi-clock-history',
                    'title' => 'No movements yet',
                    'message' => 'Stock adjustments for this variant will appear here.',
                ])
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Reason</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td class="small text-muted">{{ $log->created_at?->format('d M Y, h:i A') }}</td>
                                    <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $log->type) }}</span></td>
                                    <td>{{ $log->quantity }}</td>
                                    <td>{{ $log->reason }}</td>
                                    <td>{{ $log->creator?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
