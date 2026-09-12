@php
    $shopName = setting('shop_name', 'Unique Solution');
    $shopAddress = setting('shop_address', 'Kargil Chowk, Megha Road, Kurud - 493663');
    $initials = collect(explode(' ', $shopName))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
@endphp

<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <div class="sidebar-brand">
        <span class="brand-mark" aria-hidden="true">{{ strtoupper($initials) ?: 'US' }}</span>
        <div class="brand-text">
            <span class="brand-name">{{ $shopName }}</span>
            <span class="brand-address" title="{{ $shopAddress }}">{{ \Illuminate\Support\Str::limit($shopAddress, 36) }}</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        @can('dashboard.view')
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span class="nav-label">Dashboard</span>
            </a>
        @endcan

        {{-- Catalog --}}
        @if (auth()->user()?->can('categories.view')
            || auth()->user()?->can('brands.view')
            || auth()->user()?->can('attributes.view')
            || auth()->user()?->can('products.view')
            || auth()->user()?->can('banners.view'))
            <button type="button"
                    class="nav-link {{ request()->routeIs('admin.categories*', 'admin.brands*', 'admin.attributes*', 'admin.products*', 'admin.banners*') ? 'active' : '' }}"
                    data-menu-toggle="menu-catalog"
                    aria-expanded="false">
                <i class="bi bi-grid-1x2"></i>
                <span class="nav-label">Catalog</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <ul class="submenu" id="menu-catalog">
                @can('categories.view')
                    <li>
                        <a href="{{ route('admin.categories.index') }}"
                           class="nav-link {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">
                            <span class="nav-label">Categories</span>
                        </a>
                    </li>
                @endcan
                @can('banners.view')
                    <li>
                        <a href="{{ route('admin.banners.index') }}"
                           class="nav-link {{ request()->routeIs('admin.banners*') ? 'active' : '' }}">
                            <span class="nav-label">App Banners</span>
                        </a>
                    </li>
                @endcan
                @can('brands.view')
                    <li>
                        <a href="{{ route('admin.brands.index') }}"
                           class="nav-link {{ request()->routeIs('admin.brands*') ? 'active' : '' }}">
                            <span class="nav-label">Brands</span>
                        </a>
                    </li>
                @endcan
                @can('attributes.view')
                    <li>
                        <a href="{{ route('admin.attributes.index') }}"
                           class="nav-link {{ request()->routeIs('admin.attributes*') ? 'active' : '' }}">
                            <span class="nav-label">Attributes</span>
                        </a>
                    </li>
                @endcan
                @can('products.view')
                    <li>
                        <a href="{{ route('admin.products.index') }}"
                           class="nav-link {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                            <span class="nav-label">Products</span>
                        </a>
                    </li>
                @endcan
            </ul>
        @endif

        @can('inventory.view')
            <a href="{{ route('admin.inventory.index') }}"
               class="nav-link {{ request()->routeIs('admin.inventory*') ? 'active' : '' }}">
                <i class="bi bi-boxes"></i>
                <span class="nav-label">Inventory</span>
            </a>
        @endcan

        {{-- Orders --}}
        @if (auth()->user()?->can('orders.view')
            || auth()->user()?->can('payments.view')
            || auth()->user()?->can('refunds.view'))
            <button type="button"
                    class="nav-link {{ request()->routeIs('admin.orders*', 'admin.payments*', 'admin.refunds*') ? 'active' : '' }}"
                    data-menu-toggle="menu-orders"
                    aria-expanded="false">
                <i class="bi bi-bag-check"></i>
                <span class="nav-label">Orders</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <ul class="submenu" id="menu-orders">
                @can('orders.view')
                    <li>
                        <a href="{{ route('admin.orders.index') }}"
                           class="nav-link {{ request()->routeIs('admin.orders.index') || request()->routeIs('admin.orders.show') || request()->routeIs('admin.orders.invoice') || request()->routeIs('admin.orders.packing-slip') ? 'active' : '' }}">
                            <span class="nav-label">All orders</span>
                        </a>
                    </li>
                    @foreach (\App\Services\OrderService::BOARD_TABS as $orderStatus)
                        <li>
                            <a href="{{ route('admin.orders.status', $orderStatus) }}"
                               class="nav-link {{ request()->routeIs('admin.orders.status') && request()->route('status') === $orderStatus ? 'active' : '' }}">
                                <span class="nav-label">{{ \App\Services\OrderService::tabLabel($orderStatus) }}</span>
                            </a>
                        </li>
                    @endforeach
                @endcan
                @can('payments.view')
                    <li>
                        <a href="{{ route('admin.payments.index') }}"
                           class="nav-link {{ request()->routeIs('admin.payments*') ? 'active' : '' }}">
                            <span class="nav-label">Payments</span>
                        </a>
                    </li>
                @endcan
                @can('refunds.view')
                    <li>
                        <a href="{{ route('admin.refunds.index') }}"
                           class="nav-link {{ request()->routeIs('admin.refunds*') ? 'active' : '' }}">
                            <span class="nav-label">Refunds</span>
                        </a>
                    </li>
                @endcan
            </ul>
        @endif

        @can('customers.view')
            <a href="{{ route('admin.customers.index') }}"
               class="nav-link {{ request()->routeIs('admin.customers*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span class="nav-label">Customers</span>
            </a>
        @endcan

        {{-- Marketing --}}
        @if (auth()->user()?->can('coupons.view')
            || auth()->user()?->can('sales.view')
            || auth()->user()?->can('notifications.view')
            || auth()->user()?->can('reviews.view'))
            <button type="button"
                    class="nav-link {{ request()->routeIs('admin.coupons*', 'admin.sales*', 'admin.notifications*', 'admin.reviews*') ? 'active' : '' }}"
                    data-menu-toggle="menu-marketing"
                    aria-expanded="false">
                <i class="bi bi-megaphone"></i>
                <span class="nav-label">Marketing</span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <ul class="submenu" id="menu-marketing">
                @can('coupons.view')
                    <li>
                        <a href="{{ route('admin.coupons.index') }}"
                           class="nav-link {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}">
                            <span class="nav-label">Coupons</span>
                        </a>
                    </li>
                @endcan
                @can('sales.view')
                    <li>
                        <a href="{{ route('admin.sales.index') }}"
                           class="nav-link {{ request()->routeIs('admin.sales*') ? 'active' : '' }}">
                            <span class="nav-label">Sales</span>
                        </a>
                    </li>
                @endcan
                @can('notifications.view')
                    <li>
                        <a href="{{ route('admin.notifications.index') }}"
                           class="nav-link {{ request()->routeIs('admin.notifications*') ? 'active' : '' }}">
                            <span class="nav-label">Announcements</span>
                        </a>
                    </li>
                @endcan
                @can('reviews.view')
                    <li>
                        <a href="{{ route('admin.reviews.index') }}"
                           class="nav-link {{ request()->routeIs('admin.reviews*') ? 'active' : '' }}">
                            <span class="nav-label">Reviews</span>
                        </a>
                    </li>
                @endcan
            </ul>
        @endif

        <div class="nav-section-label">System</div>

        @can('reports.view')
            @php $reportsOpen = request()->routeIs('admin.reports*'); @endphp
            <a href="#submenu-reports" class="nav-link {{ $reportsOpen ? 'active' : '' }}"
               data-bs-toggle="collapse" aria-expanded="{{ $reportsOpen ? 'true' : 'false' }}">
                <i class="bi bi-bar-chart-line"></i>
                <span class="nav-label">Reports</span>
                <i class="bi bi-chevron-down ms-auto nav-caret"></i>
            </a>
            <ul class="nav-submenu collapse {{ $reportsOpen ? 'show' : '' }}" id="submenu-reports">
                <li>
                    <a href="{{ route('admin.reports.sales') }}"
                       class="nav-link {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}">
                        <span class="nav-label">Sales</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.reports.category') }}"
                       class="nav-link {{ request()->routeIs('admin.reports.category') ? 'active' : '' }}">
                        <span class="nav-label">Category-wise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.reports.brand') }}"
                       class="nav-link {{ request()->routeIs('admin.reports.brand') ? 'active' : '' }}">
                        <span class="nav-label">Brand-wise</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.reports.best-selling') }}"
                       class="nav-link {{ request()->routeIs('admin.reports.best-selling') ? 'active' : '' }}">
                        <span class="nav-label">Best Selling</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.reports.refunds') }}"
                       class="nav-link {{ request()->routeIs('admin.reports.refunds') ? 'active' : '' }}">
                        <span class="nav-label">Refunds</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.reports.stock-valuation') }}"
                       class="nav-link {{ request()->routeIs('admin.reports.stock-valuation') ? 'active' : '' }}">
                        <span class="nav-label">Stock Valuation</span>
                    </a>
                </li>
            </ul>
        @endcan

        @can('staff.view')
            <a href="{{ route('admin.staff.index') }}"
               class="nav-link {{ request()->routeIs('admin.staff*') ? 'active' : '' }}">
                <i class="bi bi-person-badge"></i>
                <span class="nav-label">Staff</span>
            </a>
        @endcan

        @can('settings.view')
            <a href="{{ route('admin.settings.edit') }}"
               class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                <span class="nav-label">Settings</span>
            </a>
        @endcan

        @can('activity-logs.view')
            <a href="{{ route('admin.activity-logs.index') }}"
               class="nav-link {{ request()->routeIs('admin.activity-logs*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                <span class="nav-label">Activity Logs</span>
            </a>
        @endcan
    </nav>
</aside>
