@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Settings',
        'breadcrumbs' => ['Settings'],
    ])

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header">General</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="shop_name">Shop name <span class="text-danger">*</span></label>
                        <input type="text" name="shop_name" id="shop_name" class="form-control @error('shop_name') is-invalid @enderror"
                               value="{{ old('shop_name', $settings['shop_name'] ?? 'Unique Solution') }}" required>
                        @error('shop_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="shop_tagline">Tagline</label>
                        <input type="text" name="shop_tagline" id="shop_tagline" class="form-control"
                               value="{{ old('shop_tagline', $settings['shop_tagline'] ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="shop_address">Address</label>
                        <textarea name="shop_address" id="shop_address" rows="2" class="form-control">{{ old('shop_address', $settings['shop_address'] ?? 'Kargil Chowk') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="shop_logo">Logo</label>
                        <input type="file" name="shop_logo" id="shop_logo" class="form-control @error('shop_logo') is-invalid @enderror" accept="image/*">
                        @error('shop_logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if (!empty($settings['shop_logo']))
                            <div class="mt-2"><img src="{{ asset('storage/'.$settings['shop_logo']) }}" alt="Logo" height="48"></div>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contact_number">Contact number</label>
                        <input type="text" name="contact_number" id="contact_number" class="form-control"
                               value="{{ old('contact_number', $settings['contact_number'] ?? '') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="contact_email">Contact email</label>
                        <input type="email" name="contact_email" id="contact_email" class="form-control"
                               value="{{ old('contact_email', $settings['contact_email'] ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Tax &amp; shipping</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="tax_percentage">Tax %</label>
                        <input type="number" step="0.01" min="0" max="100" name="tax_percentage" id="tax_percentage" class="form-control"
                               value="{{ old('tax_percentage', $settings['tax_percentage'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="default_shipping_charge">Default shipping charge</label>
                        <input type="number" step="0.01" min="0" name="default_shipping_charge" id="default_shipping_charge" class="form-control"
                               value="{{ old('default_shipping_charge', $settings['default_shipping_charge'] ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="currency_symbol">Currency symbol</label>
                        <input type="text" name="currency_symbol" id="currency_symbol" class="form-control"
                               value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '₹') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Payment gateways</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="razorpay_key">Razorpay key</label>
                        <input type="text" name="razorpay_key" id="razorpay_key" class="form-control"
                               value="{{ old('razorpay_key', $settings['razorpay_key'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="razorpay_secret">Razorpay secret</label>
                        <input type="password" name="razorpay_secret" id="razorpay_secret" class="form-control"
                               value="{{ old('razorpay_secret', $settings['razorpay_secret'] ?? '') }}" autocomplete="new-password">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="payu_key">PayU key</label>
                        <input type="text" name="payu_key" id="payu_key" class="form-control"
                               value="{{ old('payu_key', $settings['payu_key'] ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="payu_salt">PayU salt</label>
                        <input type="password" name="payu_salt" id="payu_salt" class="form-control"
                               value="{{ old('payu_salt', $settings['payu_salt'] ?? '') }}" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Notification templates</div>
            <div class="card-body">
                <p class="small text-muted">Placeholders: <code>{customer_name}</code>, <code>{order_number}</code>, <code>{order_total}</code>, <code>{status}</code></p>
                <div class="mb-3">
                    <label class="form-label" for="notification_order_confirmed">Order confirmed</label>
                    <textarea name="notification_order_confirmed" id="notification_order_confirmed" rows="2" class="form-control">{{ old('notification_order_confirmed', $settings['notification_order_confirmed'] ?? '') }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="notification_order_shipped">Order shipped</label>
                    <textarea name="notification_order_shipped" id="notification_order_shipped" rows="2" class="form-control">{{ old('notification_order_shipped', $settings['notification_order_shipped'] ?? '') }}</textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="notification_order_delivered">Order delivered</label>
                    <textarea name="notification_order_delivered" id="notification_order_delivered" rows="2" class="form-control">{{ old('notification_order_delivered', $settings['notification_order_delivered'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Firebase Cloud Messaging (FCM)</div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Required for push announcements &amp; sale alerts. Prefer HTTP v1 (project ID + service account JSON).
                    Legacy server key is an optional fallback.
                </p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="fcm_project_id">Firebase project ID</label>
                        <input type="text" name="fcm_project_id" id="fcm_project_id" class="form-control"
                               value="{{ old('fcm_project_id', $settings['fcm_project_id'] ?? '') }}" placeholder="unique-solution-app">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="fcm_credentials_path">Service account JSON path</label>
                        <input type="text" name="fcm_credentials_path" id="fcm_credentials_path" class="form-control"
                               value="{{ old('fcm_credentials_path', $settings['fcm_credentials_path'] ?? '') }}"
                               placeholder="firebase/service-account.json">
                        <div class="form-text">Store file at <code>storage/app/firebase/service-account.json</code>, then enter <code>firebase/service-account.json</code>.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="fcm_server_key">Legacy FCM server key (optional)</label>
                        <input type="password" name="fcm_server_key" id="fcm_server_key" class="form-control"
                               value="{{ old('fcm_server_key', $settings['fcm_server_key'] ?? '') }}" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        @can('settings.update')
            <button type="submit" class="btn btn-primary">Save settings</button>
        @endcan
    </form>
@endsection
