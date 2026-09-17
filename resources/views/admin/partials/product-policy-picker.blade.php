@php
    $selectedIds = collect($selectedIds ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();
    $initialBrandId = (int) ($initialBrandId ?? 0);
    $autoSelectAll = (bool) ($autoSelectAll ?? false);
@endphp

<div class="policy-picker" data-policy-picker
     data-warranty-url-base="{{ url('/admin/brands') }}"
     data-initial-brand="{{ $initialBrandId }}"
     data-auto-select="{{ $autoSelectAll ? '1' : '0' }}"
     data-selected='@json($selectedIds)'>
    <div class="policy-picker-head">
        <div>
            <div class="fw-semibold">Brand policies for this product</div>
            <div class="small text-muted mb-0">
                Policies are created on the <strong>brand</strong> only. Choose a brand above — then select which cards this product should show in the app. Uncheck or remove any policy you do not want for this product.
            </div>
        </div>
    </div>

    <div class="policy-picker-empty text-muted small" data-policy-picker-empty>
        Select a brand to load its policies.
    </div>

    <div class="policy-picker-list d-none" data-policy-picker-list></div>

    <div class="policy-picker-none text-muted small d-none" data-policy-picker-none>
        This brand has no policies yet. Add them under <strong>Catalog → Brands</strong>.
    </div>
</div>
