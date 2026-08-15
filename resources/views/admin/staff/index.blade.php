@extends('admin.layouts.app')

@section('title', 'Staff')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Staff',
        'breadcrumbs' => ['Staff'],
        'actions' => auth()->user()?->can('staff.create')
            ? '<a href="'.route('admin.staff.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add staff</a>'
            : null,
    ])

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="staffTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#staffTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('admin.staff.datatable') }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone', defaultContent: '—' },
            { data: 'role_name', name: 'role_name', orderable: false },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[0, 'asc']],
    });
});
</script>
@endpush
