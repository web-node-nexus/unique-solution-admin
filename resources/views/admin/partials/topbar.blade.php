@php
    $shopName = setting('shop_name', 'Unique Solution');
    $shopAddress = setting('shop_address', 'Kargil Chowk, Megha Road, Kurud - 493663');
    $user = auth()->user();
    $roleName = $user?->roles?->first()?->name ?? ($user->role ?? 'Staff');
    $userInitials = collect(explode(' ', $user->name ?? 'A'))
        ->map(fn ($w) => mb_substr($w, 0, 1))
        ->take(2)
        ->implode('');

    $lowStockCount = $lowStockCount
        ?? ($stats['low_stock_variants'] ?? null)
        ?? \App\Models\ProductVariant::query()
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();
@endphp

<header class="admin-topbar">
    <div class="topbar-left">
        <button type="button" class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="topbar-shop">
            <div class="topbar-shop-name">{{ $shopName }}</div>
            <div class="topbar-shop-address" title="{{ $shopAddress }}">{{ $shopAddress }}</div>
        </div>
    </div>

    <div class="topbar-right">
        <div class="dropdown">
            <button type="button"
                    class="btn-icon"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Notifications"
                    title="Notifications">
                <i class="bi bi-bell fs-5"></i>
                @if ($lowStockCount > 0)
                    <span class="badge-dot"></span>
                @endif
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 280px;">
                <li class="px-3 py-2">
                    <strong class="small text-uppercase text-muted" style="letter-spacing: 0.04em;">Notifications</strong>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                @if ($lowStockCount > 0)
                    <li>
                        <a class="dropdown-item d-flex align-items-start gap-2"
                           href="{{ Route::has('admin.inventory.index') ? route('admin.inventory.index') : '#' }}">
                            <i class="bi bi-exclamation-triangle text-warning mt-1"></i>
                            <span>
                                <strong>{{ $lowStockCount }}</strong> product variant{{ $lowStockCount === 1 ? '' : 's' }} low on stock
                                <span class="d-block small text-muted">Review inventory soon</span>
                            </span>
                        </a>
                    </li>
                @else
                    <li>
                        <span class="dropdown-item-text text-muted small px-3 py-2">No new notifications</span>
                    </li>
                @endif
            </ul>
        </div>

        <div class="dropdown">
            <button type="button" class="profile-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="profile-avatar">{{ strtoupper($userInitials) }}</span>
                <span class="profile-meta d-none d-md-inline-block">
                    <span class="name">{{ $user->name ?? 'Admin' }}</span>
                    <span class="role">{{ $roleName }}</span>
                </span>
                <i class="bi bi-chevron-down small text-muted d-none d-md-inline"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 200px;">
                <li class="px-3 py-2">
                    <div class="fw-semibold">{{ $user->name ?? 'Admin' }}</div>
                    <div class="small text-muted">{{ $roleName }}</div>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                @if (Route::has('admin.settings.edit'))
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.settings.edit') }}">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a>
                    </li>
                @endif
                <li>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
