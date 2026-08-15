@extends('admin.layouts.app')

@section('title', 'Reviews')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Reviews',
        'breadcrumbs' => ['Reviews'],
    ])

    <div class="card mb-3">
        <div class="card-body">
            <form id="reviewFilters" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="rating">Rating</label>
                    <select id="rating" class="form-select">
                        <option value="">All</option>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}">{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" class="form-select">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="applyReviewFilters" class="btn btn-outline-primary">Apply</button>
                    <button type="button" id="resetReviewFilters" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="reviewsTable" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="replyForm" action="#">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="replyModalLabel">Reply to review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="admin_reply">Reply</label>
                        <textarea name="admin_reply" id="admin_reply" rows="4" class="form-control" required maxlength="2000"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save reply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#reviewsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.reviews.datatable') }}',
            data: function (d) {
                d.status = $('#status').val();
                d.rating = $('#rating').val();
            }
        },
        columns: [
            { data: 'product_name', name: 'product_name', orderable: false },
            { data: 'customer', name: 'customer', orderable: false },
            { data: 'rating', name: 'rating' },
            { data: 'comment', name: 'comment' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[5, 'desc']],
        language: {
            search: '',
            searchPlaceholder: 'Search reviews…',
            emptyTable: 'No reviews found',
        },
    });

    $('#applyReviewFilters').on('click', function () { table.ajax.reload(); });
    $('#resetReviewFilters').on('click', function () {
        $('#status, #rating').val('');
        table.ajax.reload();
    });

    const modal = new bootstrap.Modal(document.getElementById('replyModal'));
    $(document).on('click', '.btn-reply-review', function () {
        const id = $(this).data('id');
        $('#replyForm').attr('action', @json(url('admin/reviews')).replace(/\/?$/, '') + '/' + id + '/reply');
        $('#admin_reply').val('');
        modal.show();
    });
});
</script>
@endpush
