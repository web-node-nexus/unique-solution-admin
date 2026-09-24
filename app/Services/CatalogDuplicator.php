<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Sale;
use Illuminate\Support\Str;

class CatalogDuplicator
{
    public function __construct(protected ImageService $images) {}

    public function banner(Banner $banner): Banner
    {
        $copy = $banner->replicate(['status']);
        $copy->title = $this->copiedName($banner->title ?: 'Banner');
        $copy->status = false;
        $copy->sort_order = ((int) Banner::query()->max('sort_order')) + 1;
        $copy->image_path = $this->images->copy($banner->image_path, 'banners') ?? $banner->image_path;
        $copy->save();

        activity_log('duplicated', 'banners', "Duplicated banner #{$banner->id} into #{$copy->id}");

        return $copy;
    }

    public function sale(Sale $sale): Sale
    {
        $copy = $sale->replicate(['status', 'notification_sent_at']);
        $copy->title = $this->copiedName($sale->title);
        $copy->status = false;
        $copy->notify_users = false;
        $copy->notification_sent_at = null;
        $copy->sort_order = ((int) Sale::query()->max('sort_order')) + 1;
        $copy->image = $this->images->copy($sale->image, 'sales') ?? $sale->image;
        $copy->save();

        activity_log('duplicated', 'sales', "Duplicated sale #{$sale->id} into #{$copy->id}");

        return $copy;
    }

    public function category(Category $category): Category
    {
        $copy = $category->replicate(['status', 'sale_active', 'slug']);
        $copy->name = $this->copiedName($category->name);
        $copy->slug = Str::slug($copy->name).'-'.Str::lower(Str::random(4));
        $copy->status = false;
        $copy->sale_active = false;
        $copy->sort_order = ((int) Category::query()->max('sort_order')) + 1;
        $copy->image = $this->images->copy($category->image, 'categories') ?? $category->image;
        $copy->sale_banner = $this->images->copy($category->sale_banner, 'categories/sales') ?? $category->sale_banner;
        $copy->save();

        $copy->attributes()->sync($category->attributes()->pluck('id')->all());

        activity_log('duplicated', 'categories', "Duplicated category #{$category->id} into #{$copy->id}");

        return $copy;
    }

    public function brand(Brand $brand): Brand
    {
        $copy = $brand->replicate(['status']);
        $copy->name = $this->uniqueBrandName($this->copiedName($brand->name), $brand->mappedCategoryIds());
        $copy->status = false;
        $copy->logo = $this->images->copy($brand->logo, 'brands') ?? $brand->logo;
        $copy->save();
        $copy->syncCategories($brand->mappedCategoryIds());

        foreach ($brand->policies()->orderBy('sort_order')->orderBy('id')->get() as $index => $policy) {
            $copy->policies()->create([
                'title' => $policy->title,
                'description' => $policy->description,
                'icon' => $this->images->copy($policy->icon, 'policies/brands') ?? $policy->icon,
                'sort_order' => $index,
            ]);
        }

        activity_log('duplicated', 'brands', "Duplicated brand #{$brand->id} into #{$copy->id}");

        return $copy;
    }

    public function coupon(Coupon $coupon): Coupon
    {
        $copy = $coupon->replicate(['status', 'used_count']);
        $copy->code = $this->uniqueCouponCode($coupon->code);
        $copy->title = $coupon->title ? $this->copiedName($coupon->title) : $coupon->title;
        $copy->status = false;
        $copy->used_count = 0;
        $copy->image = $this->images->copy($coupon->image, 'coupons') ?? $coupon->image;
        $copy->save();

        activity_log('duplicated', 'coupons', "Duplicated coupon #{$coupon->id} into #{$copy->id}");

        return $copy;
    }

    public function announcement(AppNotification $notification): AppNotification
    {
        $copy = $notification->replicate([
            'status',
            'sent_at',
            'fcm_success_count',
            'fcm_failure_count',
            'is_active',
        ]);
        $copy->title = $this->copiedName($notification->title);
        $copy->status = 'draft';
        $copy->is_active = false;
        $copy->sent_at = null;
        $copy->fcm_success_count = 0;
        $copy->fcm_failure_count = 0;
        $copy->sent_by = auth()->id();

        if ($notification->related_type !== Sale::class) {
            $copy->image = $this->images->copy($notification->image, 'notifications') ?? $notification->image;
        }

        $copy->save();

        activity_log('duplicated', 'notifications', "Duplicated announcement #{$notification->id} into #{$copy->id}");

        return $copy;
    }

    private function copiedName(?string $name): string
    {
        $base = trim((string) $name);
        if ($base === '') {
            $base = 'Copy';
        }

        if (str_ends_with($base, '(Copy)')) {
            return $base;
        }

        return $base.' (Copy)';
    }

    private function uniqueBrandName(string $name, array $categoryIds = []): string
    {
        $candidate = $name;
        $i = 2;

        while (
            $categoryIds !== []
                ? Brand::nameTakenInCategories($candidate, $categoryIds)
                : Brand::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($candidate)])->exists()
        ) {
            $candidate = $name.' '.$i;
            $i++;
        }

        return $candidate;
    }

    private function uniqueCouponCode(string $code): string
    {
        $base = strtoupper(preg_replace('/-COPY(?:-\d+)?$/', '', $code) ?: $code);
        $candidate = $base.'-COPY';
        $i = 2;

        while (Coupon::query()->where('code', $candidate)->exists()) {
            $candidate = $base.'-COPY-'.$i;
            $i++;
        }

        return $candidate;
    }
}
