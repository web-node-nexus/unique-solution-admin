@extends('admin.layouts.app')

@section('title', $attribute->name)

@section('content')
    @include('admin.partials.page-header', [
        'title' => $attribute->name,
        'breadcrumbs' => [
            'Catalog' => null,
            'Attributes' => route('admin.attributes.index'),
            $attribute->name,
        ],
        'actions' => auth()->user()?->can('attributes.update')
            ? '<a href="'.route('admin.attributes.edit', $attribute).'" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>'
            : null,
    ])

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Type</dt>
                <dd class="col-sm-9">{{ $attribute->type }}</dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    <span class="badge bg-{{ $attribute->status ? 'success' : 'secondary' }}">
                        {{ $attribute->status ? 'Active' : 'Inactive' }}
                    </span>
                </dd>
                <dt class="col-sm-3">Values</dt>
                <dd class="col-sm-9">
                    @forelse ($attribute->values as $value)
                        @if ($attribute->type === 'color-swatch')
                            <span class="d-inline-flex align-items-center gap-1 badge text-bg-light border me-1 mb-1">
                                @if ($value->image_url)
                                    <img src="{{ $value->image_url }}" alt=""
                                         style="width: 16px; height: 16px; object-fit: cover; border-radius: 3px;">
                                @elseif (! empty($value->extra_data['hex'] ?? null))
                                    <span class="rounded-circle d-inline-block"
                                          style="width: 12px; height: 12px; background: {{ $value->extra_data['hex'] }};"></span>
                                @endif
                                {{ $value->value }}
                            </span>
                        @else
                            <span class="badge text-bg-light border me-1">{{ $value->value }}</span>
                        @endif
                    @empty
                        —
                    @endforelse
                </dd>
            </dl>
        </div>
    </div>
@endsection
