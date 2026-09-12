<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AppNotificationController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\ResetPasswordController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->name('login.submit');

        Route::get('password/forgot', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.view')
            ->name('dashboard');
        Route::post('orders/{order}/quick-status', [DashboardController::class, 'quickUpdateOrderStatus'])
            ->middleware('permission:orders.update')
            ->name('orders.quick-status');

        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */
        Route::get('categories/datatable', [CategoryController::class, 'datatable'])
            ->middleware('permission:categories.view')->name('categories.datatable');
        Route::get('categories/create', [CategoryController::class, 'create'])
            ->middleware('permission:categories.create')->name('categories.create');
        Route::post('categories/reorder', [CategoryController::class, 'reorder'])
            ->middleware('permission:categories.update')->name('categories.reorder');
        Route::post('categories/{category}/move', [CategoryController::class, 'move'])
            ->middleware('permission:categories.update')->name('categories.move');
        Route::get('categories', [CategoryController::class, 'index'])
            ->middleware('permission:categories.view')->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])
            ->middleware('permission:categories.create')->name('categories.store');
        Route::get('categories/{category}/edit', [CategoryController::class, 'edit'])
            ->middleware('permission:categories.update')->name('categories.edit');
        Route::get('categories/{category}', [CategoryController::class, 'show'])
            ->middleware('permission:categories.view')->name('categories.show');
        Route::put('categories/{category}', [CategoryController::class, 'update'])
            ->middleware('permission:categories.update')->name('categories.update');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
            ->middleware('permission:categories.delete')->name('categories.destroy');

        /*
        |--------------------------------------------------------------------------
        | App Banners (carousel)
        |--------------------------------------------------------------------------
        */
        Route::get('banners/datatable', [BannerController::class, 'datatable'])
            ->middleware('permission:banners.view')->name('banners.datatable');
        Route::post('banners/reorder', [BannerController::class, 'reorder'])
            ->middleware('permission:banners.update')->name('banners.reorder');
        Route::get('banners/create', [BannerController::class, 'create'])
            ->middleware('permission:banners.create')->name('banners.create');
        Route::get('banners', [BannerController::class, 'index'])
            ->middleware('permission:banners.view')->name('banners.index');
        Route::post('banners', [BannerController::class, 'store'])
            ->middleware('permission:banners.create')->name('banners.store');
        Route::get('banners/{banner}/edit', [BannerController::class, 'edit'])
            ->middleware('permission:banners.update')->name('banners.edit');
        Route::put('banners/{banner}', [BannerController::class, 'update'])
            ->middleware('permission:banners.update')->name('banners.update');
        Route::delete('banners/{banner}', [BannerController::class, 'destroy'])
            ->middleware('permission:banners.delete')->name('banners.destroy');

        /*
        |--------------------------------------------------------------------------
        | Brands
        |--------------------------------------------------------------------------
        */
        Route::get('brands/datatable', [BrandController::class, 'datatable'])
            ->middleware('permission:brands.view')->name('brands.datatable');
        Route::get('brands/create', [BrandController::class, 'create'])
            ->middleware('permission:brands.create')->name('brands.create');
        Route::get('brands', [BrandController::class, 'index'])
            ->middleware('permission:brands.view')->name('brands.index');
        Route::post('brands', [BrandController::class, 'store'])
            ->middleware('permission:brands.create')->name('brands.store');
        Route::get('brands/{brand}/warranty', [BrandController::class, 'warranty'])
            ->middleware('permission:brands.view')->name('brands.warranty');
        Route::get('brands/{brand}/edit', [BrandController::class, 'edit'])
            ->middleware('permission:brands.update')->name('brands.edit');
        Route::put('brands/{brand}', [BrandController::class, 'update'])
            ->middleware('permission:brands.update')->name('brands.update');
        Route::delete('brands/{brand}', [BrandController::class, 'destroy'])
            ->middleware('permission:brands.delete')->name('brands.destroy');

        /*
        |--------------------------------------------------------------------------
        | Attributes
        |--------------------------------------------------------------------------
        */
        Route::get('attributes/datatable', [AttributeController::class, 'datatable'])
            ->middleware('permission:attributes.view')->name('attributes.datatable');
        Route::get('attributes/create', [AttributeController::class, 'create'])
            ->middleware('permission:attributes.create')->name('attributes.create');
        Route::get('attributes', [AttributeController::class, 'index'])
            ->middleware('permission:attributes.view')->name('attributes.index');
        Route::post('attributes', [AttributeController::class, 'store'])
            ->middleware('permission:attributes.create')->name('attributes.store');
        Route::get('attributes/{attribute}/edit', [AttributeController::class, 'edit'])
            ->middleware('permission:attributes.update')->name('attributes.edit');
        Route::get('attributes/{attribute}', [AttributeController::class, 'show'])
            ->middleware('permission:attributes.view')->name('attributes.show');
        Route::put('attributes/{attribute}', [AttributeController::class, 'update'])
            ->middleware('permission:attributes.update')->name('attributes.update');
        Route::delete('attributes/{attribute}', [AttributeController::class, 'destroy'])
            ->middleware('permission:attributes.delete')->name('attributes.destroy');
        Route::post('attributes/{attribute}/values', [AttributeController::class, 'storeValue'])
            ->middleware('permission:attributes.update')->name('attributes.values.store');
        Route::put('attributes/{attribute}/values/{value}', [AttributeController::class, 'updateValue'])
            ->middleware('permission:attributes.update')->name('attributes.values.update');
        Route::delete('attributes/{attribute}/values/{value}', [AttributeController::class, 'destroyValue'])
            ->middleware('permission:attributes.update')->name('attributes.values.destroy');

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */
        Route::get('products/datatable', [ProductController::class, 'datatable'])
            ->middleware('permission:products.view')->name('products.datatable');
        Route::get('products/create', [ProductController::class, 'create'])
            ->middleware('permission:products.create')->name('products.create');
        Route::get('products/category/{category}/attributes', [ProductController::class, 'getCategoryAttributes'])
            ->middleware('permission:products.view')->name('products.category-attributes');
        Route::get('products-export', [ProductController::class, 'export'])
            ->middleware('permission:products.export')->name('products.export');
        Route::post('products-import', [ProductController::class, 'import'])
            ->middleware('permission:products.create')->name('products.import');
        Route::get('products-import-template', [ProductController::class, 'importTemplate'])
            ->middleware('permission:products.create')->name('products.import-template');
        Route::post('products/bulk-action', [ProductController::class, 'bulkAction'])
            ->middleware('permission:products.update')->name('products.bulk-action');
        Route::get('products', [ProductController::class, 'index'])
            ->middleware('permission:products.view')->name('products.index');
        Route::post('products', [ProductController::class, 'store'])
            ->middleware('permission:products.create')->name('products.store');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])
            ->middleware('permission:products.update')->name('products.edit');
        Route::post('products/{product}/clone', [ProductController::class, 'clone'])
            ->middleware('permission:products.create')->name('products.clone');
        Route::patch('products/{product}/quick-update', [ProductController::class, 'quickUpdate'])
            ->middleware('permission:products.update')->name('products.quick-update');
        Route::get('products/{product}', [ProductController::class, 'show'])
            ->middleware('permission:products.view')->name('products.show');
        Route::put('products/{product}', [ProductController::class, 'update'])
            ->middleware('permission:products.update')->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])
            ->middleware('permission:products.delete')->name('products.destroy');

        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */
        Route::get('inventory/datatable', [InventoryController::class, 'datatable'])
            ->middleware('permission:inventory.view')->name('inventory.datatable');
        Route::get('inventory/adjust', [InventoryController::class, 'adjustForm'])
            ->middleware('permission:inventory.create')->name('inventory.adjust');
        Route::post('inventory/adjust', [InventoryController::class, 'adjustStore'])
            ->middleware('permission:inventory.create')->name('inventory.adjust.store');
        Route::get('inventory', [InventoryController::class, 'index'])
            ->middleware('permission:inventory.view')->name('inventory.index');
        Route::get('inventory/variants/{variant}/history', [InventoryController::class, 'history'])
            ->middleware('permission:inventory.view')->name('inventory.history');
        Route::patch('inventory/variants/{variant}/threshold', [InventoryController::class, 'updateThreshold'])
            ->middleware('permission:inventory.update')->name('inventory.threshold');

        /*
        |--------------------------------------------------------------------------
        | Orders
        |--------------------------------------------------------------------------
        */
        Route::get('orders/datatable', [OrderController::class, 'datatable'])
            ->middleware('permission:orders.view')->name('orders.datatable');
        Route::get('orders', [OrderController::class, 'index'])
            ->middleware('permission:orders.view')->name('orders.index');
        Route::get('orders/status/{status}', [OrderController::class, 'byStatus'])
            ->whereIn('status', ['pending', 'confirmed', 'assigned', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'])
            ->middleware('permission:orders.view')->name('orders.status');
        Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])
            ->middleware('permission:orders.view')->name('orders.invoice');
        Route::get('orders/{order}/packing-slip', [OrderController::class, 'packingSlip'])
            ->middleware('permission:orders.view')->name('orders.packing-slip');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->middleware('permission:orders.update')->name('orders.update-status');
        Route::get('orders/{order}', [OrderController::class, 'show'])
            ->middleware('permission:orders.view')->name('orders.show');

        /*
        |--------------------------------------------------------------------------
        | Payments
        |--------------------------------------------------------------------------
        */
        Route::get('payments/datatable', [PaymentController::class, 'datatable'])
            ->middleware('permission:payments.view')->name('payments.datatable');
        Route::get('payments/reconciliation', [PaymentController::class, 'reconciliation'])
            ->middleware('permission:payments.view')->name('payments.reconciliation');
        Route::get('payments', [PaymentController::class, 'index'])
            ->middleware('permission:payments.view')->name('payments.index');
        Route::patch('payments/{payment}/status', [PaymentController::class, 'updateStatus'])
            ->middleware('permission:payments.update')->name('payments.update-status');

        /*
        |--------------------------------------------------------------------------
        | Refunds
        |--------------------------------------------------------------------------
        */
        Route::get('refunds/datatable', [RefundController::class, 'datatable'])
            ->middleware('permission:refunds.view')->name('refunds.datatable');
        Route::get('refunds-export', [RefundController::class, 'export'])
            ->middleware('permission:refunds.view')->name('refunds.export');
        Route::get('refunds', [RefundController::class, 'index'])
            ->middleware('permission:refunds.view')->name('refunds.index');
        Route::get('refunds/{refund}', [RefundController::class, 'show'])
            ->middleware('permission:refunds.view')->name('refunds.show');
        Route::post('refunds/{refund}/approve', [RefundController::class, 'approve'])
            ->middleware('permission:refunds.approve')->name('refunds.approve');
        Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])
            ->middleware('permission:refunds.approve')->name('refunds.reject');

        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */
        Route::get('customers/datatable', [CustomerController::class, 'datatable'])
            ->middleware('permission:customers.view')->name('customers.datatable');
        Route::get('customers', [CustomerController::class, 'index'])
            ->middleware('permission:customers.view')->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])
            ->middleware('permission:customers.view')->name('customers.show');
        Route::post('customers/{customer}/toggle-block', [CustomerController::class, 'toggleBlock'])
            ->middleware('permission:customers.update')->name('customers.toggle-block');

        /*
        |--------------------------------------------------------------------------
        | Staff
        |--------------------------------------------------------------------------
        */
        Route::get('staff/datatable', [StaffController::class, 'datatable'])
            ->middleware('permission:staff.view')->name('staff.datatable');
        Route::get('staff/create', [StaffController::class, 'create'])
            ->middleware('permission:staff.create')->name('staff.create');
        Route::get('staff', [StaffController::class, 'index'])
            ->middleware('permission:staff.view')->name('staff.index');
        Route::post('staff', [StaffController::class, 'store'])
            ->middleware('permission:staff.create')->name('staff.store');
        Route::get('staff/{staff}/edit', [StaffController::class, 'edit'])
            ->middleware('permission:staff.update')->name('staff.edit');
        Route::put('staff/{staff}', [StaffController::class, 'update'])
            ->middleware('permission:staff.update')->name('staff.update');
        Route::delete('staff/{staff}', [StaffController::class, 'destroy'])
            ->middleware('permission:staff.delete')->name('staff.destroy');

        /*
        |--------------------------------------------------------------------------
        | Coupons
        |--------------------------------------------------------------------------
        */
        Route::get('coupons/datatable', [CouponController::class, 'datatable'])
            ->middleware('permission:coupons.view')->name('coupons.datatable');
        Route::get('coupons/create', [CouponController::class, 'create'])
            ->middleware('permission:coupons.create')->name('coupons.create');
        Route::get('coupons', [CouponController::class, 'index'])
            ->middleware('permission:coupons.view')->name('coupons.index');
        Route::post('coupons', [CouponController::class, 'store'])
            ->middleware('permission:coupons.create')->name('coupons.store');
        Route::get('coupons/{coupon}/usage', [CouponController::class, 'usage'])
            ->middleware('permission:coupons.view')->name('coupons.usage');
        Route::get('coupons/{coupon}/edit', [CouponController::class, 'edit'])
            ->middleware('permission:coupons.update')->name('coupons.edit');
        Route::put('coupons/{coupon}', [CouponController::class, 'update'])
            ->middleware('permission:coupons.update')->name('coupons.update');
        Route::delete('coupons/{coupon}', [CouponController::class, 'destroy'])
            ->middleware('permission:coupons.delete')->name('coupons.destroy');

        /*
        |--------------------------------------------------------------------------
        | Sales / Promotions
        |--------------------------------------------------------------------------
        */
        Route::get('sales/datatable', [SaleController::class, 'datatable'])
            ->middleware('permission:sales.view')->name('sales.datatable');
        Route::get('sales/create', [SaleController::class, 'create'])
            ->middleware('permission:sales.create')->name('sales.create');
        Route::get('sales', [SaleController::class, 'index'])
            ->middleware('permission:sales.view')->name('sales.index');
        Route::post('sales', [SaleController::class, 'store'])
            ->middleware('permission:sales.create')->name('sales.store');
        Route::get('sales/{sale}/edit', [SaleController::class, 'edit'])
            ->middleware('permission:sales.update')->name('sales.edit');
        Route::put('sales/{sale}', [SaleController::class, 'update'])
            ->middleware('permission:sales.update')->name('sales.update');
        Route::delete('sales/{sale}', [SaleController::class, 'destroy'])
            ->middleware('permission:sales.delete')->name('sales.destroy');

        /*
        |--------------------------------------------------------------------------
        | App Notifications
        |--------------------------------------------------------------------------
        */
        Route::get('notifications/datatable', [AppNotificationController::class, 'datatable'])
            ->middleware('permission:notifications.view')->name('notifications.datatable');
        Route::get('notifications/create', [AppNotificationController::class, 'create'])
            ->middleware('permission:notifications.create')->name('notifications.create');
        Route::get('notifications', [AppNotificationController::class, 'index'])
            ->middleware('permission:notifications.view')->name('notifications.index');
        Route::post('notifications', [AppNotificationController::class, 'store'])
            ->middleware('permission:notifications.create')->name('notifications.store');
        Route::get('notifications/{notification}/edit', [AppNotificationController::class, 'edit'])
            ->middleware('permission:notifications.update')->name('notifications.edit');
        Route::put('notifications/{notification}', [AppNotificationController::class, 'update'])
            ->middleware('permission:notifications.update')->name('notifications.update');
        Route::post('notifications/{notification}/send', [AppNotificationController::class, 'send'])
            ->middleware('permission:notifications.update')->name('notifications.send');
        Route::delete('notifications/{notification}', [AppNotificationController::class, 'destroy'])
            ->middleware('permission:notifications.delete')->name('notifications.destroy');

        /*
        |--------------------------------------------------------------------------
        | Reviews
        |--------------------------------------------------------------------------
        */
        Route::get('reviews/datatable', [ReviewController::class, 'datatable'])
            ->middleware('permission:reviews.view')->name('reviews.datatable');
        Route::get('reviews', [ReviewController::class, 'index'])
            ->middleware('permission:reviews.view')->name('reviews.index');
        Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])
            ->middleware('permission:reviews.approve')->name('reviews.approve');
        Route::post('reviews/{review}/reject', [ReviewController::class, 'reject'])
            ->middleware('permission:reviews.approve')->name('reviews.reject');
        Route::post('reviews/{review}/reply', [ReviewController::class, 'reply'])
            ->middleware('permission:reviews.update')->name('reviews.reply');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])
            ->middleware('permission:reviews.delete')->name('reviews.destroy');

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */
        Route::middleware('permission:reports.view')->prefix('reports')->name('reports.')->group(function () {
            Route::get('/', fn () => redirect()->route('admin.reports.sales'))->name('index');
            Route::get('sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('category', [ReportController::class, 'category'])->name('category');
            Route::get('brand', [ReportController::class, 'brand'])->name('brand');
            Route::get('best-selling', [ReportController::class, 'bestSelling'])->name('best-selling');
            Route::get('refunds', [ReportController::class, 'refunds'])->name('refunds');
            Route::get('stock-valuation', [ReportController::class, 'stockValuation'])->name('stock-valuation');
        });

        /*
        |--------------------------------------------------------------------------
        | Settings & Activity logs
        |--------------------------------------------------------------------------
        */
        Route::get('settings', [SettingController::class, 'edit'])
            ->middleware('permission:settings.view')->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])
            ->middleware('permission:settings.update')->name('settings.update');

        Route::get('activity-logs/datatable', [ActivityLogController::class, 'datatable'])
            ->middleware('permission:activity-logs.view')->name('activity-logs.datatable');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])
            ->middleware('permission:activity-logs.view')->name('activity-logs.index');
    });
});
