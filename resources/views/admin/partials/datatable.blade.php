@php
    $id = $id ?? 'dataTable';
    $ajax = $ajax ?? null;
    $order = $order ?? [[0, 'desc']];
@endphp

{{-- Optional DataTables bootstrapping helper.
     Pass $ajax as a URL string for server-side tables. --}}
@once
    @push('scripts')
        <script>
            window.initAdminDataTable = function (selector, options) {
                if (!window.jQuery || !jQuery.fn.DataTable) {
                    return null;
                }
                const defaults = {
                    pageLength: 25,
                    responsive: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Search…',
                        lengthMenu: '_MENU_ per page',
                        emptyTable: 'No records found',
                    },
                };
                return jQuery(selector).DataTable(Object.assign({}, defaults, options || {}));
            };
        </script>
    @endpush
@endonce

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const opts = {
            order: @json($order),
        };
        @if ($ajax)
            opts.processing = true;
            opts.serverSide = true;
            opts.ajax = @json($ajax);
        @endif
        if (typeof window.initAdminDataTable === 'function') {
            window.initAdminDataTable('#{{ $id }}', opts);
        }
    });
</script>
@endpush
