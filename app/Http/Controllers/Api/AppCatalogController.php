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
use App\Services\PolicyService;
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
                'whatsapp_number' => Setting::get('whatsapp_number') ?: Setting::get('contact_number'),
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
            ->with(['brand:id,name,warranty', 'category:id,name', 'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')])
            ->withAvg(['reviews as rating_avg' => fn ($q) => $q->where('status', 'approved')], 'rating')
            ->withCount(['reviews as rating_count' => fn ($q) => $q->where('status', 'approved')])
            ->latest('id')
            ->limit(10)
            ->get();

        $latest = Product::query()
            ->where('status', 'active')
            ->with(['brand:id,name,warranty', 'category:id,name', 'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')])
            ->withAvg(['reviews as rating_avg' => fn ($q) => $q->where('status', 'approved')], 'rating')
            ->withCount(['reviews as rating_count' => fn ($q) => $q->where('status', 'approved')])
            ->latest('id')
            ->limit(12)
            ->get();

        $onSale = Product::query()
            ->where('status', 'active')
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'base_price')
            ->with(['brand:id,name,warranty', 'category:id,name', 'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')])
            ->withAvg(['reviews as rating_avg' => fn ($q) => $q->where('status', 'approved')], 'rating')
            ->withCount(['reviews as rating_count' => fn ($q) => $q->where('status', 'approved')])
            ->orderByRaw('(base_price - sale_price) / NULLIF(base_price, 0) desc')
            ->limit(10)
            ->get();

        $brands = Brand::query()
            ->where('status', true)
            ->orderBy('name')
            ->limit(16)
            ->get(['id', 'name', 'category_id', 'logo', 'warranty'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'category_id' => $brand->category_id,
                'warranty' => $brand->warranty,
                'logo_url' => $brand->logo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo)
                    : null,
            ]);

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

        $featuredCards = $featured->isNotEmpty()
            ? $featured
            : $latest->take(8);

        return response()->json([
            'success' => true,
            'data' => [
                'shop' => [
                    'shop_name' => Setting::get('shop_name', 'Unique Solution'),
                    'shop_tagline' => Setting::get('shop_tagline', 'आपकी अपनी दुकान'),
                    'shop_address' => Setting::get('shop_address'),
                    'contact_number' => Setting::get('contact_number'),
                    'whatsapp_number' => Setting::get('whatsapp_number') ?: Setting::get('contact_number'),
                    'contact_email' => Setting::get('contact_email'),
                ],
                'banners' => $banners,
                'categories' => $categories,
                'category_sales' => $categorySales,
                'sales' => Sale::query()->active()->ordered()->get()->map(fn (Sale $sale) => $this->transformSale($sale)),
                'coupons' => Coupon::query()->active()->latest('id')->limit(20)->get()->map(fn (Coupon $coupon) => $this->transformCoupon($coupon)),
                'brands' => $brands,
                'featured_products' => $featuredCards->map(fn (Product $product) => $this->transformProductCard($product))->values(),
                'new_arrivals' => $latest->map(fn (Product $product) => $this->transformProductCard($product))->values(),
                'sale_products' => $onSale->map(fn (Product $product) => $this->transformProductCard($product))->values(),
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

    public function brands(Request $request): JsonResponse
    {
        $brands = Brand::query()
            ->where('status', true)
            ->when($request->filled('category_id'), fn ($q) => $q->forCategory((int) $request->input('category_id')))
            ->orderBy('name')
            ->get()
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'category_id' => $brand->category_id,
                'warranty' => $brand->warranty,
                'logo_url' => $brand->logo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo)
                    : null,
            ]);

        return response()->json(['success' => true, 'data' => $brands]);
    }

    /**
     * Paginated product catalog with category / brand / attribute / price filters.
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('status', 'active')
            ->with([
                'brand:id,name,category_id,warranty',
                'category:id,name,slug',
                'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->withAvg(['reviews as rating_avg' => fn ($q) => $q->where('status', 'approved')], 'rating')
            ->withCount(['reviews as rating_count' => fn ($q) => $q->where('status', 'approved')]);

        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->input('q')).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $term));
            });
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $query->whereIn('category_id', Category::treeIds($categoryId));
        }

        if ($request->filled('brand_id')) {
            $brandIds = collect(explode(',', (string) $request->input('brand_id')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->values()
                ->all();
            if ($brandIds !== []) {
                $query->whereIn('brand_id', $brandIds);
            }
        }

        if ($request->filled('min_price')) {
            $min = (float) $request->input('min_price');
            $query->where(function ($q) use ($min) {
                $q->where(function ($inner) use ($min) {
                    $inner->whereNotNull('sale_price')->where('sale_price', '>=', $min);
                })->orWhere(function ($inner) use ($min) {
                    $inner->whereNull('sale_price')->where('base_price', '>=', $min);
                });
            });
        }

        if ($request->filled('max_price')) {
            $max = (float) $request->input('max_price');
            $query->where(function ($q) use ($max) {
                $q->where(function ($inner) use ($max) {
                    $inner->whereNotNull('sale_price')->where('sale_price', '<=', $max);
                })->orWhere(function ($inner) use ($max) {
                    $inner->whereNull('sale_price')->where('base_price', '<=', $max);
                });
            });
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $rawAttrIds = $request->input('attribute_value_ids', []);
        if (is_string($rawAttrIds)) {
            $rawAttrIds = explode(',', $rawAttrIds);
        }
        $attributeValueIds = collect($rawAttrIds)
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($attributeValueIds->isNotEmpty()) {
            foreach ($attributeValueIds as $valueId) {
                $query->whereHas('variants.attributeValues', fn ($q) => $q->where('attribute_values.id', $valueId));
            }
        }

        $sort = (string) $request->input('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, base_price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, base_price) desc'),
            'name_asc' => $query->orderBy('name'),
            'featured' => $query->orderByDesc('is_featured')->latest('id'),
            default => $query->latest('id'),
        };

        $perPage = min(max((int) $request->input('per_page', 20), 1), 50);
        $page = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($page->items())->map(fn (Product $product) => $this->transformProductCard($product))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function productShow(Product $product): JsonResponse
    {
        if ($product->status !== 'active') {
            abort(404);
        }

        $product->load([
            'brand:id,name,category_id,logo,warranty',
            'brand.policies',
            'category:id,name,slug,parent_id',
            'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order'),
            'policies',
            'variants' => fn ($q) => $q->where('status', true)->with([
                'attributeValues.attribute:id,name,type',
                'images',
            ]),
        ]);

        $images = $product->images->map(fn ($image) => [
            'id' => $image->id,
            'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path),
            'is_primary' => (bool) $image->is_primary,
        ])->values();

        $variants = $product->variants->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'mrp' => (float) $variant->price,
                'sale_price' => $variant->discount_price !== null ? (float) $variant->discount_price : null,
                'stock_quantity' => (int) $variant->stock_quantity,
                'in_stock' => (int) $variant->stock_quantity > 0,
                'attributes' => $variant->attributeValues->map(fn ($av) => [
                    'attribute_id' => $av->attribute_id,
                    'attribute_name' => $av->attribute?->name,
                    'value_id' => $av->id,
                    'value' => $av->value,
                    'hex' => $av->extra_data['hex'] ?? null,
                    'image_url' => $av->image_url,
                ])->values(),
                'image_url' => optional($variant->images->first())->image_path
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($variant->images->first()->image_path)
                    : null,
            ];
        })->values();

        $specs = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->attributeValues as $av) {
                $name = $av->attribute?->name;
                if (! $name) {
                    continue;
                }
                $specs[$name] ??= [];
                if (! in_array($av->value, $specs[$name], true)) {
                    $specs[$name][] = $av->value;
                }
            }
        }
        $specifications = collect($specs)->map(fn ($values, $name) => [
            'name' => $name,
            'value' => implode(' / ', $values),
        ])->values();

        $ratingStats = $product->reviews()
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as count, AVG(rating) as average')
            ->first();

        $reviews = $product->reviews()
            ->with('user:id,name')
            ->where('status', 'approved')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'comment' => $r->comment,
                'admin_reply' => $r->admin_reply,
                'user_name' => $r->user?->name ?? 'Customer',
                'created_at' => optional($r->created_at)?->toIso8601String(),
            ])
            ->values();

        $related = Product::query()
            ->where('status', 'active')
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                $q->where('category_id', $product->category_id);
                if ($product->brand_id) {
                    $q->orWhere('brand_id', $product->brand_id);
                }
            })
            ->with(['brand:id,name,warranty', 'category:id,name', 'images' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('sort_order')])
            ->withAvg(['reviews as rating_avg' => fn ($q) => $q->where('status', 'approved')], 'rating')
            ->withCount(['reviews as rating_count' => fn ($q) => $q->where('status', 'approved')])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => $this->transformProductCard($p))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'warranty_info' => $product->warranty_info,
                'use_brand_policies' => (bool) $product->use_brand_policies,
                'policies' => app(PolicyService::class)->resolvedForProduct($product),
                'mrp' => (float) $product->base_price,
                'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
                'base_price' => (float) $product->base_price,
                'is_featured' => (bool) $product->is_featured,
                'rating_average' => round((float) ($ratingStats->average ?? 0), 1),
                'rating_count' => (int) ($ratingStats->count ?? 0),
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                    'category_id' => $product->brand->category_id,
                    'warranty' => $product->brand->warranty,
                    'logo_url' => $product->brand->logo
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->brand->logo)
                        : null,
                ] : null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'images' => $images,
                'image_url' => $images->first()['url'] ?? null,
                'variants' => $variants,
                'specifications' => $specifications,
                'reviews' => $reviews,
                'related_products' => $related,
            ],
        ]);
    }

    /**
     * Facets for filter UI: brands, dynamic attributes (Material, Color, …), price range.
     * When category_id is present, facets are scoped to that category tree only.
     */
    public function productFilters(Request $request): JsonResponse
    {
        $base = Product::query()->where('status', 'active');
        $scopeCategory = null;
        $scopeIds = [];

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $scopeCategory = Category::query()->find($categoryId);
            $scopeIds = Category::treeIds($categoryId);
            $base->whereIn('category_id', $scopeIds);
        }

        $productIds = (clone $base)->pluck('id');

        $brands = Brand::query()
            ->where('status', true)
            ->whereIn('id', (clone $base)->whereNotNull('brand_id')->distinct()->pluck('brand_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'logo'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'name' => $brand->name,
                'category_id' => $brand->category_id,
                'logo_url' => $brand->logo
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo)
                    : null,
            ]);

        $attributesQuery = \App\Models\Attribute::query()
            ->where('status', true)
            ->with(['values' => fn ($q) => $q->orderBy('value')]);

        if ($scopeIds !== []) {
            $attributesQuery->whereHas(
                'categories',
                fn ($q) => $q->whereIn('categories.id', $scopeIds)
            );
        }

        $attributes = $attributesQuery->orderBy('name')->get()->map(function ($attribute) use ($productIds) {
            $values = $attribute->values
                ->filter(function ($value) use ($productIds) {
                    if ($productIds->isEmpty()) {
                        return false;
                    }

                    return $value->variants()
                        ->whereIn('product_id', $productIds)
                        ->exists();
                })
                ->map(fn ($value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                    'hex' => $value->extra_data['hex'] ?? null,
                    'image_url' => $value->image_url,
                ])
                ->values();

            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'type' => $attribute->type,
                'values' => $values,
            ];
        })->filter(fn ($attr) => count($attr['values']) > 0)->values();

        $priceStats = (clone $base)
            ->selectRaw('MIN(COALESCE(sale_price, base_price)) as min_price, MAX(COALESCE(sale_price, base_price)) as max_price')
            ->first();

        // Root categories for global browse; subcategories when scoped to a parent.
        $categories = Category::query()
            ->where('status', true)
            ->when(
                $scopeCategory,
                fn ($q) => $q->where('parent_id', $scopeCategory->id),
                fn ($q) => $q->whereNull('parent_id')
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ]);

        // If current scope is already a leaf (or mid-level), still allow siblings under the same parent.
        $subcategories = [];
        if ($scopeCategory) {
            $subcategories = Category::query()
                ->where('status', true)
                ->where('parent_id', $scopeCategory->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ])
                ->values()
                ->all();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'scope' => $scopeCategory ? [
                    'category_id' => $scopeCategory->id,
                    'category_name' => $scopeCategory->name,
                    'parent_id' => $scopeCategory->parent_id,
                ] : null,
                'categories' => $categories,
                'subcategories' => $subcategories,
                'brands' => $brands,
                'attributes' => $attributes,
                'price' => [
                    'min' => (float) ($priceStats->min_price ?? 0),
                    'max' => (float) ($priceStats->max_price ?? 0),
                ],
                'sort_options' => [
                    ['value' => 'newest', 'label' => 'Newest'],
                    ['value' => 'price_asc', 'label' => 'Price: Low to High'],
                    ['value' => 'price_desc', 'label' => 'Price: High to Low'],
                    ['value' => 'name_asc', 'label' => 'Name A–Z'],
                    ['value' => 'featured', 'label' => 'Featured'],
                ],
            ],
        ]);
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
            'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'mrp' => (float) $product->base_price,
            'brand' => $product->brand?->name,
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'category' => $product->category?->name,
            'is_featured' => (bool) $product->is_featured,
            'rating_average' => round((float) ($product->rating_avg ?? 0), 1),
            'rating_count' => (int) ($product->rating_count ?? 0),
            'image_url' => $primary
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($primary->image_path)
                : null,
            'image_count' => $product->images->count(),
            'gallery_preview' => $product->images->take(3)->map(
                fn ($image) => \Illuminate\Support\Facades\Storage::disk('public')->url($image->image_path)
            )->values()->all(),
            'warranty_info' => $product->warranty_info,
            'brand_warranty' => $product->brand?->warranty,
            'highlight' => $product->warranty_info
                ?: ($product->brand?->warranty ?: null),
        ];
    }

    public function searchSuggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => [
                'products' => [],
                'categories' => [],
                'brands' => [],
            ]]);
        }

        $term = '%'.$q.'%';

        $products = Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $term));
            })
            ->with(['brand:id,name,warranty', 'images' => fn ($img) => $img->orderByDesc('is_primary')->orderBy('sort_order')])
            ->withAvg(['reviews as rating_avg' => fn ($r) => $r->where('status', 'approved')], 'rating')
            ->withCount(['reviews as rating_count' => fn ($r) => $r->where('status', 'approved')])
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => $this->transformProductCard($p))
            ->values();

        $categories = Category::query()
            ->where('status', true)
            ->where('name', 'like', $term)
            ->orderBy('sort_order')
            ->limit(5)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'type' => 'category',
            ])
            ->values();

        $brands = Brand::query()
            ->where('status', true)
            ->where('name', 'like', $term)
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn (Brand $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'type' => 'brand',
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'categories' => $categories,
                'brands' => $brands,
            ],
        ]);
    }

    public function checkPincode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pincode' => ['required', 'string', 'max:12'],
        ]);

        $pin = preg_replace('/\D+/', '', $data['pincode']) ?: '';
        $raw = (string) Setting::get('serviceable_pincodes', '');
        $list = collect(preg_split('/[\s,;]+/', $raw))
            ->map(fn ($v) => preg_replace('/\D+/', '', $v))
            ->filter()
            ->values();

        $serviceable = $list->isEmpty()
            || $list->contains($pin)
            || $list->contains(fn ($allowed) => str_starts_with($pin, $allowed));

        $shipping = (float) Setting::get('default_shipping_charge', 99);
        $etaDays = (int) Setting::get('delivery_eta_days', 5);

        return response()->json([
            'success' => true,
            'data' => [
                'pincode' => $pin,
                'serviceable' => (bool) $serviceable,
                'shipping_charge' => $serviceable ? $shipping : null,
                'eta_days' => $serviceable ? $etaDays : null,
                'message' => $serviceable
                    ? "Delivery available in {$etaDays}-".($etaDays + 2).' days'
                    : 'Sorry, we do not deliver to this pincode yet.',
            ],
        ]);
    }

    public function previewCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $coupon = Coupon::query()->active()->where('code', strtoupper(trim($data['code'])))->first();
        if (! $coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired coupon.',
            ], 422);
        }

        $subtotal = (float) $data['subtotal'];
        if ($coupon->min_order_value && $subtotal < (float) $coupon->min_order_value) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum order value is ₹'.number_format((float) $coupon->min_order_value, 0),
            ], 422);
        }

        if ($coupon->max_uses !== null && (int) $coupon->used_count >= (int) $coupon->max_uses) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon has reached its usage limit.',
            ], 422);
        }

        $discount = $coupon->discount_type === 'percent'
            ? round($subtotal * ((float) $coupon->discount_value / 100), 2)
            : min((float) $coupon->discount_value, $subtotal);

        $taxPct = (float) Setting::get('tax_percentage', 18);
        $shipping = (float) Setting::get('default_shipping_charge', 99);
        $afterDiscount = max(0, $subtotal - $discount);
        $tax = round($afterDiscount * ($taxPct / 100), 2);
        $total = round($afterDiscount + $tax + $shipping, 2);

        return response()->json([
            'success' => true,
            'data' => [
                'code' => $coupon->code,
                'title' => $coupon->title,
                'discount_type' => $coupon->discount_type,
                'discount_value' => (float) $coupon->discount_value,
                'discount_amount' => $discount,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_charge' => $shipping,
                'total' => $total,
            ],
        ]);
    }
}
