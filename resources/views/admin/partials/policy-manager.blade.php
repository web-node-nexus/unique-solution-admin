@php
    $policies = collect($policies ?? []);
    $field = $field ?? 'policies';
    $heading = $heading ?? 'Policy details';
    $hint = $hint ?? 'Add warranty, replacement, delivery and support cards. Products only select which policies to show.';
@endphp

<div class="policy-manager policy-manager--accordion" data-policy-manager data-field="{{ $field }}">
    <div class="policy-manager-head">
        <div>
            <div class="policy-manager-kicker">Policies</div>
            <div class="fw-semibold fs-5">{{ $heading }}</div>
            <div class="small text-muted mt-1">{{ $hint }}</div>
        </div>
        <button type="button" class="btn btn-primary" data-policy-add>
            <i class="bi bi-plus-lg me-1"></i>Add policy
        </button>
    </div>

    <div class="policy-list" data-policy-list>
        @forelse ($policies as $index => $policy)
            @php
                $iconUrl = method_exists($policy, 'iconUrl') ? $policy->iconUrl() : ($policy->icon ? asset('storage/'.$policy->icon) : null);
                $title = old("{$field}.{$index}.title", $policy->title);
                $description = old("{$field}.{$index}.description", $policy->description);
            @endphp
            <div class="policy-item" data-policy-card data-index="{{ $index }}">
                <input type="hidden" name="{{ $field }}[{{ $index }}][id]" value="{{ $policy->id }}" data-policy-id>
                <input type="hidden" name="{{ $field }}[{{ $index }}][remove]" value="0" data-policy-remove>

                <div class="policy-item-summary" data-policy-summary>
                    <div class="policy-item-summary-main">
                        <div class="policy-item-icon" data-policy-icon-wrap>
                            @if ($iconUrl)
                                <img src="{{ $iconUrl }}" alt="" data-policy-icon-img>
                            @else
                                <i class="bi bi-shield-check" data-policy-icon-fallback></i>
                            @endif
                        </div>
                        <div class="policy-item-title" data-policy-title-text>{{ $title }}</div>
                    </div>
                    <div class="policy-item-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-policy-edit>
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-policy-delete>
                            <i class="bi bi-trash me-1"></i>Remove
                        </button>
                    </div>
                </div>

                <div class="policy-item-editor" data-policy-editor-panel hidden>
                    <div class="policy-item-editor-toolbar">
                        <div class="fw-semibold text-muted small text-uppercase">Editing policy</div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-primary" data-policy-done>
                                <i class="bi bi-check-lg me-1"></i>Done
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-policy-delete>
                                <i class="bi bi-trash me-1"></i>Remove
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tagline <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control"
                               name="{{ $field }}[{{ $index }}][title]"
                               value="{{ $title }}"
                               maxlength="160"
                               placeholder="e.g. 1 Year Manufacturer Warranty | Pan-India Service"
                               data-policy-title-input>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Policy icon / photo</label>
                        <div class="policy-icon-upload">
                            <div class="policy-icon-preview" data-policy-icon-wrap>
                                @if ($iconUrl)
                                    <img src="{{ $iconUrl }}" alt="" data-policy-icon-img>
                                @else
                                    <i class="bi bi-shield-check" data-policy-icon-fallback></i>
                                @endif
                            </div>
                            <div>
                                <label class="btn btn-sm btn-outline-secondary mb-0">
                                    <i class="bi bi-upload me-1"></i>Change photo
                                    <input type="file"
                                           class="d-none"
                                           name="{{ $field }}[{{ $index }}][icon]"
                                           accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                           data-policy-file-input>
                                </label>
                                <div class="form-text mt-1">PNG / JPG / WebP. Square icon works best.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Full policy details</label>
                        <textarea class="form-control"
                                  rows="10"
                                  name="{{ $field }}[{{ $index }}][description]"
                                  id="{{ $field }}_desc_{{ $index }}"
                                  data-policy-description-input
                                  placeholder="HTML table / coverage / terms…">{{ $description }}</textarea>
                        <div class="form-text">You can paste HTML tables (Coverage / Period / Terms) like the store sheet.</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="policy-empty text-muted" data-policy-empty>
                No policies yet. Click <strong>Add policy</strong> to create warranty, replacement, or delivery cards.
            </div>
        @endforelse
    </div>
</div>
