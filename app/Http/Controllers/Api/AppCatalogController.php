<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppCatalogController extends Controller
{
    /**
     * Shop info for app bootstrap.
     */
    public function shop(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'shop_name' => Setting::get('shop_name', 'Unique Solution'),
                'shop_tagline' => Setting::get('shop_tagline', 'आपकी अपनी दुकान'),
                'shop_address' => Setting::get('shop_address', 'Kargil Chowk, Megha Road, Kurud - 493663'),
                'contact_number' => Setting::get('contact_number'),
                'contact_email' => Setting::get('contact_email'),
                'currency_symbol' => Setting::get('currency_symbol', '₹'),
                'tax_percentage' => (float) Setting::get('tax_percentage', 18),
                'default_shipping_charge' => (float) Setting::get('default_shipping_charge', 99),
            ],
        ]);
    }

    /**
     * Categories ordered by admin sort_order (app home / menu).
     * Admin can rearrange anytime — app always gets latest order.
     */
    public function categories(Request $request): JsonResponse
    {
        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;

        $categories = Category::query()
            ->where('status', true)
            ->when(
                $request->boolean('roots_only'),
                fn ($q) => $q->whereNull('parent_id'),
                fn ($q) => $request->has('parent_id')
                    ? $q->where('parent_id', $parentId)
                    : $q
            )
            ->with(['children' => fn ($q) => $q->where('status', true)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => $this->transformCategory($category));

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Active carousel banners for the app home screen.
     */
    public function banners(): JsonResponse
    {
        $banners = Banner::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'image_url' => $banner->image_url,
                'sort_order' => $banner->sort_order,
                'link' => $banner->deepLink(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $banners,
        ]);
    }

    /**
     * Home feed payload: shop + banners + top-level categories.
     */
    public function home(): JsonResponse
    {
        $banners = Banner::query()->active()->ordered()->get()->map(fn (Banner $banner) => [
            'id' => $banner->id,
            'title' => $banner->title,
            'subtitle' => $banner->subtitle,
            'image_url' => $banner->image_url,
            'sort_order' => $banner->sort_order,
            'link' => $banner->deepLink(),
        ]);

        $categories = Category::query()
            ->where('status', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => $this->transformCategory($category, false));

        $featured = Product::query()
            ->where('status', 'active')
            ->where('is_featured', true)
            ->with(['brand:id,name', 'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')])
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (Product $product) => $this->transformProductCard($product));

        $categorySales = Category::query()
            ->where('status', true)
            ->where('sale_active', true)
            ->whereNotNull('sale_banner')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_slug' => $category->slug,
                'title' => $category->sale_title,
                'subtitle' => $category->sale_subtitle,
                'banner_url' => $category->sale_banner_url,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'shop' => [
                    'shop_name' => Setting::get('shop_name', 'Unique Solution'),
                    'shop_tagline' => Setting::get('shop_tagline', 'आपकी अपनी दुकान'),
                    'shop_address' => Setting::get('shop_address'),
                ],
                'banners' => $banners,
                'categories' => $categories,
                'category_sales' => $categorySales,
                'sales' => Sale::query()->active()->ordered()->get()->map(fn (Sale $sale) => $this->transformSale($sale)),
                'coupons' => Coupon::query()->active()->latest('id')->limit(20)->get()->map(fn (Coupon $coupon) => $this->transformCoupon($coupon)),
                'featured_products' => $featured,
            ],
        ]);
    }

    public function sales(): JsonResponse
    {
        $sales = Sale::query()->active()->ordered()->get()->map(fn (Sale $sale) => $this->transformSale($sale));

        return response()->json(['success' => true, 'data' => $sales]);
    }

    public function coupons(): JsonResponse
    {
        $coupons = Coupon::query()->active()->latest('id')->get()->map(fn (Coupon $coupon) => $this->transformCoupon($coupon));

        return response()->json(['success' => true, 'data' => $coupons]);
    }

    public function notifications(): JsonResponse
    {
        $items = AppNotification::query()
            ->where('status', 'sent')
            ->latest('sent_at')
            ->limit(50)
            ->get()
            ->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'image_url' => $n->image_url,
                'link_type' => $n->link_type,
                'link_value' => $n->link_value,
                'sent_at' => optional($n->sent_at)?->toIso8601String(),
            ]);

        return response()->json(['success' => true, 'data' => $items]);
    }

    /**
     * Categories that currently have an active sale banner (for app sale strips).
     */
    public function categorySales(): JsonResponse
    {
        $sales = Category::query()
            ->where('status', true)
            ->where('sale_active', true)
            ->whereNotNull('sale_banner')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_slug' => $category->slug,
                'title' => $category->sale_title,
                'subtitle' => $category->sale_subtitle,
                'banner_url' => $category->sale_banner_url,
            ]);

        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }

    public function brands(): JsonResponse
    {
        $brands = Brand::query()
            ->where('status', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'warranty' => $brand->warranty,
                'logo_url' => $brand->logo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo)
                    : null,
            ]);

        return response()->json(['success' => true, 'data' => $brands]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCategory(Category $category, bool $withChildren = true): array
    {
        $hasSale = $category->hasActiveSaleBanner();

        $data = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'image_url' => $category->image_url,
            'sort_order' => $category->sort_order,
            'has_sale' => $hasSale,
            'sale' => $hasSale ? [
                'active' => true,
                'title' => $category->sale_title,
                'subtitle' => $category->sale_subtitle,
                'banner_url' => $category->sale_banner_url,
            ] : null,
        ];

        if ($withChildren && $category->relationLoaded('children')) {
            $data['children'] = $category->children->map(
                fn (Category $child) => $this->transformCategory($child, false)
            )->values();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformSale(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'title' => $sale->title,
            'subtitle' => $sale->subtitle,
            'description' => $sale->description,
            'image_url' => $sale->image_url,
            'starts_at' => optional($sale->starts_at)?->toIso8601String(),
            'ends_at' => optional($sale->ends_at)?->toIso8601String(),
            'link_type' => $sale->link_type,
            'link_value' => $sale->link_value,
            'sort_order' => $sale->sort_order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCoupon(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'title' => $coupon->title,
            'description' => $coupon->description,
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
            'min_order_value' => (float) $coupon->min_order_value,
            'start_date' => optional($coupon->start_date)?->toDateString(),
            'expiry_date' => optional($coupon->expiry_date)?->toDateString(),
            'image_url' => $coupon->image_url,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProductCard(Product $product): array
    {
        $primary = $product->images->first();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'base_price' => (float) $product->base_price,
            'brand' => $product->brand?->name,
            'image_url' => $primary
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($primary->image_path)
                : null,
        ];
    }
}
