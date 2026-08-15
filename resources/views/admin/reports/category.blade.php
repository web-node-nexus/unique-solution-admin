@extends('admin.layouts.app')

@section('title', 'Category Report')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Category sales',
        'breadcrumbs' => ['Reports', 'Category'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.category') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="from">From</label>
                    <input type="date" name="from" id="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="to">To</label>
                    <input type="date" name="to" id="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
                </div>
                <div class="col-md-6 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    @can('reports.export')
                        <a href="{{ route('admin.reports.category', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'export' => 'pdf']) }}" class="btn btn-outline-danger">Export PDF</a>
                    @endcan
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">Chart</div>
                <div class="card-body"><div class="chart-wrap"><canvas id="categoryChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card table-card h-100">
                <div class="card-body p-0">
                    @if ($rows->isEmpty())
                        @include('admin.partials.empty-state', ['title' => 'No data', 'message' => 'No category sales in this range.'])
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Category</th><th>Units</th><th>Revenue</th></tr></thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        <tr>
                                            <td>{{ $row->name }}</td>
                                            <td>{{ number_format($row->units) }}</td>
                                            <td>{{ format_money($row->revenue) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chart = @json($chart);
    if (document.getElementById('categoryChart') && typeof Chart !== 'undefined') {
        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: chart.labels || [],
                datasets: [{ data: chart.data || [], backgroundColor: ['#0d9488','#0284c7','#d97706','#059669','#64748b','#dc2626'] }],
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }
});
</script>
@endpush
