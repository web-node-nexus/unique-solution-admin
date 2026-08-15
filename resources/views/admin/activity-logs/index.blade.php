@extends('admin.layouts.app')

@section('title', 'Activity Logs')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Activity logs',
        'breadcrumbs' => ['Activity logs'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form id="activityFilters" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label" for="user_id">User ID</label>
                    <input type="number" id="user_id" class="form-control" min="1" placeholder="Optional">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="module">Module</label>
                    <select id="module" class="form-select">
                        <option value="">All</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module }}">{{ $module }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="action">Action</label>
                    <select id="action" class="form-select">
                        <option value="">All</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="from">From</label>
                    <input type="date" id="from" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="to">To</label>
                    <input type="date" id="to" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="button" id="applyActivityFilters" class="btn btn-outline-primary">Apply</button>
                    <button type="button" id="resetActivityFilters" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="activityLogsTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#activityLogsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.activity-logs.datatable') }}',
            data: function (d) {
                d.user_id = $('#user_id').val();
                d.module = $('#module').val();
                d.action = $('#action').val();
                d.from = $('#from').val();
                d.to = $('#to').val();
            }
        },
        columns: [
            { data: 'user_name', name: 'user_name', orderable: false },
            { data: 'module', name: 'module' },
            { data: 'action_label', name: 'action', orderable: false },
            { data: 'description', name: 'description' },
            { data: 'created_formatted', name: 'created_at' },
        ],
        order: [[4, 'desc']],
        language: {
            search: '',
            searchPlaceholder: 'Search logs…',
            emptyTable: 'No activity logs found',
        },
    });

    $('#applyActivityFilters').on('click', function () { table.ajax.reload(); });
    $('#resetActivityFilters').on('click', function () {
        $('#user_id, #module, #action, #from, #to').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
