@extends('admin.layouts.app')

@section('title', 'Announcements')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Announcements',
        'subtitle' => 'Announce to all customers — in-app message + FCM push with image',
        'breadcrumbs' => ['Marketing' => null, 'Announcements'],
        'actions' => auth()->user()?->can('notifications.create')
            ? '<a href="'.route('admin.notifications.create').'" class="btn btn-primary"><i class="bi bi-megaphone me-1"></i>New announcement</a>'
            : null,
    ])

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="notificationsTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>FCM</th>
                            <th>Sent at</th>
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
$('#notificationsTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: @json(route('admin.notifications.datatable')),
    order: [[6, 'desc']],
    columns: [
        { data: 'preview', orderable: false, searchable: false },
        { data: 'title', name: 'title' },
        { data: 'type_label', orderable: false, searchable: false },
        { data: 'audience', name: 'audience' },
        { data: 'status', orderable: false, searchable: false },
        { data: 'fcm', orderable: false, searchable: false },
        { data: 'sent_at', name: 'sent_at', defaultContent: '—' },
        { data: 'action', orderable: false, searchable: false },
    ],
});
</script>
@endpush
