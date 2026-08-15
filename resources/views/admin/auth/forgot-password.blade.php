<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot password — {{ setting('shop_name', 'Unique Solution') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">
    <link href="{{ asset('assets/admin/css/admin.css') }}" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="brand-mark">US</div>
            <h1>Forgot password</h1>
            <p class="tagline">We'll email you a reset link</p>
        </div>

        <div class="auth-body">
            <form method="POST" action="{{ route('admin.password.email') }}" id="forgotForm">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email"
                           name="email"
                           id="email"
                           value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="you@example.com"
                           required
                           autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" id="forgotBtn">
                    <i class="bi bi-envelope me-1"></i> Send reset link
                </button>

                <div class="text-center">
                    <a href="{{ route('admin.login') }}" class="small">
                        <i class="bi bi-arrow-left"></i> Back to sign in
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
    <script src="{{ asset('assets/admin/js/admin.js') }}"></script>
    <script>
        @if (session('status'))
            toastr.success(@json(session('status')));
        @endif
        @if ($errors->any())
            toastr.error(@json($errors->first()));
        @endif
        document.getElementById('forgotForm')?.addEventListener('submit', function () {
            setButtonLoading('#forgotBtn', true);
        });
    </script>
</body>
</html>
