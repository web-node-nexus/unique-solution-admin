@extends('admin.layouts.app')

@section('title', 'App Banners')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'App Carousel Banners',
        'subtitle' => 'Images shown in the mobile/web app home carousel. Drag to set slide order.',
        'breadcrumbs' => ['Catalog' => null, 'Banners'],
        'actions' => auth()->user()?->can('banners.create')
            ? '<a href="'.route('admin.banners.create').'" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Banner</a>'
            : null,
    ])

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <select id="statusFilter" class="form-select form-select-sm" style="max-width:160px">
                    <option value="all">All status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <span class="text-muted small"><i class="bi bi-grip-vertical me-1"></i>Drag rows to change carousel order (app reads this instantly)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100" id="bannersTable">
                    <thead>
                        <tr>
                            <th style="width:40px"></th>
                            <th>Preview</th>
                            <th>Title</th>
                            <th>Link</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .reorder-handle { cursor: grab; font-size: 1.1rem; }
    .reorder-handle:active { cursor: grabbing; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const table = $('#bannersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('admin.banners.datatable')),
            data: function (d) {
                d.status = $('#statusFilter').val();
            }
        },
        order: [[4, 'asc']],
        columns: [
            { data: 'reorder', orderable: false, searchable: false, className: 'text-center' },
            { data: 'preview', orderable: false, searchable: false },
            { data: 'title', name: 'title' },
            { data: 'link_label', orderable: false, searchable: false },
            { data: 'sort_order', name: 'sort_order' },
            { data: 'status', name: 'status', orderable: false },
            { data: 'action', orderable: false, searchable: false },
        ],
        drawCallback: function () {
            initBannerSortable();
        }
    });

    $('#statusFilter').on('change', () => table.ajax.reload());

    function initBannerSortable() {
        const $tbody = $('#bannersTable tbody');
        if (!$tbody.length || typeof Sortable === 'undefined') return;
        if ($tbody.data('sortable')) {
            $tbody.data('sortable').destroy();
        }
        const sortable = Sortable.create($tbody[0], {
            handle: '.reorder-handle',
            animation: 150,
            onEnd: function () {
                const order = [];
                $tbody.find('tr').each(function () {
                    const id = table.row(this).data()?.id;
                    if (id) order.push(id);
                });
                if (!order.length) return;
                $.ajax({
                    url: @json(route('admin.banners.reorder')),
                    method: 'POST',
                    data: { order: order, _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function (res) {
                        toastr.success((res && res.message) || 'Carousel order saved');
                        table.ajax.reload(null, false);
                    },
                    error: function (xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Failed to reorder');
                        table.ajax.reload(null, false);
                    }
                });
            }
        });
        $tbody.data('sortable', sortable);
    }
});
</script>
@endpush
