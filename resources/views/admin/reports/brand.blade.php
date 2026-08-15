@extends('admin.layouts.app')

@section('title', 'Brand Report')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Brand sales',
        'breadcrumbs' => ['Reports', 'Brand'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.brand') }}" class="row g-3 align-items-end">
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
                        <a href="{{ route('admin.reports.brand', ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'export' => 'pdf']) }}" class="btn btn-outline-danger">Export PDF</a>
                    @endcan
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            @if ($rows->isEmpty())
                @include('admin.partials.empty-state', ['title' => 'No data', 'message' => 'No brand sales in this range.'])
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Brand</th><th>Units</th><th>Revenue</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td>{{ $row->name ?? 'Unbranded' }}</td>
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
@endsection
