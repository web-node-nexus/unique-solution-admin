@php
    $owner = $owner ?? 'brand'; // brand|product
    $policies = collect($policies ?? []);
    $field = $field ?? 'policies';
    $heading = $heading ?? 'Policy manager';
    $hint = $hint ?? 'Add warranty, return, delivery and support cards shown on the product page.';
    $palette = ['#EEF2FF', '#ECFDF5', '#FFF7ED', '#FDF2F8', '#EFF6FF', '#F0FDF4'];
@endphp

<div class="policy-manager" data-policy-manager data-field="{{ $field }}">
    <div class="policy-manager-head">
        <div>
            <div class="fw-semibold">{{ $heading }}</div>
            <div class="small text-muted">{{ $hint }}</div>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-policy-add>
            <i class="bi bi-plus-lg me-1"></i>Add policy
        </button>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="policy-list" data-policy-list>
                @forelse ($policies as $index => $policy)
                    @php
                        $bg = $palette[$index % count($palette)];
                        $iconUrl = method_exists($policy, 'iconUrl') ? $policy->iconUrl() : ($policy->icon ? asset('storage/'.$policy->icon) : null);
                    @endphp
                    <div class="policy-card" data-policy-card data-index="{{ $index }}" style="--policy-tint: {{ $bg }}">
                        <input type="hidden" name="{{ $field }}[{{ $index }}][id]" value="{{ $policy->id }}" data-policy-id>
                        <input type="hidden" name="{{ $field }}[{{ $index }}][remove]" value="0" data-policy-remove>
                        <input type="hidden" name="{{ $field }}[{{ $index }}][title]" value="{{ $policy->title }}" data-policy-title-input>
                        <input type="hidden" name="{{ $field }}[{{ $index }}][description]" value="{{ $policy->description }}" data-policy-description-input>

                        <div class="policy-card-icon" data-policy-icon-wrap>
                            @if ($iconUrl)
                                <img src="{{ $iconUrl }}" alt="" data-policy-icon-img>
                            @else
                                <i class="bi bi-shield-check" data-policy-icon-fallback></i>
                            @endif
                        </div>
                        <div class="policy-card-body">
                            <div class="policy-card-title" data-policy-title-text>{{ $policy->title }}</div>
                            <div class="policy-card-desc" data-policy-description-text>{{ \Illuminate\Support\Str::limit(strip_tags((string) $policy->description), 90) }}</div>
                        </div>
                        <div class="policy-card-actions">
                            <button type="button" class="btn btn-link btn-sm text-decoration-none" data-policy-edit>Edit</button>
                            <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none" data-policy-delete>Delete</button>
                        </div>
                    </div>
                @empty
                    <div class="policy-empty text-muted small" data-policy-empty>
                        No policies yet. Click <strong>Add policy</strong> to create the first card.
                    </div>
                @endforelse
            </div>

            <button type="button" class="policy-add-dashed mt-3" data-policy-add>
                <i class="bi bi-plus-circle me-1"></i>Add more policy
            </button>
        </div>

        <div class="col-lg-5">
            <div class="policy-editor card border-0 shadow-sm" data-policy-editor hidden>
                <div class="card-body">
                    <div class="text-uppercase small fw-bold text-muted mb-3" data-policy-editor-label>Editing policy</div>
                    <div class="mb-3">
                        <label class="form-label">Policy title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" data-policy-editor-title maxlength="120" placeholder="e.g. 1 Year Warranty">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload PNG icon</label>
                        <input type="file" class="form-control" accept="image/png,image/jpeg,image/webp" data-policy-editor-icon>
                        <div class="form-text">Square PNG works best. Max 2MB.</div>
                        <img src="" alt="" class="policy-editor-preview d-none mt-2" data-policy-editor-preview>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="4" data-policy-editor-description placeholder="Short details shown when customer taps See more"></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary flex-grow-1" data-policy-save>Update</button>
                        <button type="button" class="btn btn-outline-secondary" data-policy-cancel>Cancel</button>
                    </div>
                </div>
            </div>
            <div class="policy-editor-idle text-muted small" data-policy-editor-idle>
                Select a policy card to edit, or add a new one.
            </div>
        </div>
    </div>
</div>
