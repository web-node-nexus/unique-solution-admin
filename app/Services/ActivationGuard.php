<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActivationGuard
{
    /**
     * @return list<string>
     */
    public function issues(string $type, Model|array|Request $source): array
    {
        return match ($type) {
            'product' => $this->productIssues($source),
            'category' => $this->categoryIssues($source),
            'brand' => $this->brandIssues($source),
            'banner' => $this->bannerIssues($source),
            'sale', 'offer' => $this->saleIssues($source),
            'coupon' => $this->couponIssues($source),
            'announcement', 'notification' => $this->announcementIssues($source),
            default => [],
        };
    }

    /**
     * @throws ValidationException
     */
    public function assertCanActivate(string $type, Model|array|Request $source): void
    {
        $issues = $this->issues($type, $source);

        if ($issues === []) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => $issues,
        ]);
    }

    /**
     * @return list<string>
     */
    private function productIssues(Model|array|Request $source): array
    {
        $issues = [];
        $name = trim((string) $this->value($source, 'name'));
        $categoryId = $this->value($source, 'category_id');
        $price = $this->value($source, 'base_price');

        if ($name === '') {
            $issues[] = 'Add a product name before activating.';
        }

        if (! $categoryId) {
            $issues[] = 'Select a category before activating.';
        }

        if ($price === null || $price === '' || (float) $price <= 0) {
            $issues[] = 'Enter a valid MRP / price before activating.';
        }

        $hasImage = false;

        if ($source instanceof Product) {
            $hasImage = $source->images()->exists()
                || $source->variants()->whereHas('images')->exists();
        } elseif ($source instanceof Request) {
            $hasImage = $source->hasFile('images')
                || filled($source->input('primary_image_id'))
                || filled($source->input('gallery_order'));

            // Wizard uploads photos per variant — count those too.
            if (! $hasImage) {
                foreach ((array) $source->file('variants', []) as $variantFiles) {
                    if (! is_array($variantFiles)) {
                        continue;
                    }

                    $single = $variantFiles['image'] ?? null;
                    if ($single instanceof \Illuminate\Http\UploadedFile && $single->isValid()) {
                        $hasImage = true;
                        break;
                    }

                    foreach ((array) ($variantFiles['images'] ?? []) as $image) {
                        if ($image instanceof \Illuminate\Http\UploadedFile && $image->isValid()) {
                            $hasImage = true;
                            break 2;
                        }
                    }
                }
            }

            if ($source->route('product') instanceof Product) {
                $product = $source->route('product');
                $removing = array_filter((array) $source->input('remove_image_ids', []));
                $remaining = $product->images()->when(
                    $removing !== [],
                    fn ($q) => $q->whereNotIn('id', $removing)
                )->exists();
                $hasImage = $hasImage
                    || $remaining
                    || $source->hasFile('images')
                    || $product->variants()->whereHas('images')->exists();
            } elseif (! $hasImage && $source instanceof Request) {
                // Fallback: resolve product by route id when route model binding name differs.
                $productId = $source->route('product') ?? $source->route('id');
                if (is_numeric($productId)) {
                    $product = Product::query()->find((int) $productId);
                    if ($product) {
                        $hasImage = $product->images()->exists()
                            || $product->variants()->whereHas('images')->exists();
                    }
                }
            }
        }

        if (! $hasImage) {
            $issues[] = 'Upload at least one product photo (gallery or variant) before activating.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function categoryIssues(Model|array|Request $source): array
    {
        $issues = [];

        if (trim((string) $this->value($source, 'name')) === '') {
            $issues[] = 'Add a category name before activating.';
        }

        $hasImage = false;
        if ($source instanceof Category) {
            $hasImage = filled($source->image);
        } elseif ($source instanceof Request) {
            $hasImage = $source->hasFile('image');
            if ($source->route('category') instanceof Category) {
                $hasImage = $hasImage || filled($source->route('category')->image);
            }
        } else {
            $hasImage = filled($this->value($source, 'image'));
        }

        if (! $hasImage) {
            $issues[] = 'Upload a category image before activating.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function brandIssues(Model|array|Request $source): array
    {
        $issues = [];

        if (trim((string) $this->value($source, 'name')) === '') {
            $issues[] = 'Add a brand name before activating.';
        }

        $categoryIds = [];
        if ($source instanceof Brand) {
            $categoryIds = $source->mappedCategoryIds();
        } elseif ($source instanceof Request) {
            $categoryIds = (array) $source->input('category_ids', []);
        } else {
            $categoryIds = (array) ($source['category_ids'] ?? []);
        }

        if ($categoryIds === []) {
            $issues[] = 'Map this brand to at least one category before activating.';
        }

        $hasLogo = false;
        if ($source instanceof Brand) {
            $hasLogo = filled($source->logo);
        } elseif ($source instanceof Request) {
            $hasLogo = $source->hasFile('logo');
            if ($source->route('brand') instanceof Brand) {
                $hasLogo = $hasLogo || filled($source->route('brand')->logo);
            }
        } else {
            $hasLogo = filled($this->value($source, 'logo'));
        }

        if (! $hasLogo) {
            $issues[] = 'Upload a brand logo before activating.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function bannerIssues(Model|array|Request $source): array
    {
        $hasImage = false;

        if ($source instanceof Banner) {
            $hasImage = filled($source->image_path);
        } elseif ($source instanceof Request) {
            $hasImage = $source->hasFile('image');
            if ($source->route('banner') instanceof Banner) {
                $hasImage = $hasImage || filled($source->route('banner')->image_path);
            }
        } else {
            $hasImage = filled($this->value($source, 'image_path') ?: $this->value($source, 'image'));
        }

        return $hasImage ? [] : ['Upload a banner image before activating.'];
    }

    /**
     * @return list<string>
     */
    private function saleIssues(Model|array|Request $source): array
    {
        $issues = [];

        if (trim((string) $this->value($source, 'title')) === '') {
            $issues[] = 'Add an offer title before activating.';
        }

        $hasImage = false;
        if ($source instanceof Sale) {
            $hasImage = filled($source->image);
        } elseif ($source instanceof Request) {
            $hasImage = $source->hasFile('image') && ! $source->boolean('remove_image');
            if ($source->route('sale') instanceof Sale) {
                $hasImage = $hasImage || (filled($source->route('sale')->image) && ! $source->boolean('remove_image'));
            }
        } else {
            $hasImage = filled($this->value($source, 'image'));
        }

        if (! $hasImage) {
            $issues[] = 'Upload an offer image before activating.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function couponIssues(Model|array|Request $source): array
    {
        $issues = [];

        if (trim((string) $this->value($source, 'code')) === '') {
            $issues[] = 'Add a coupon code before activating.';
        }

        $value = $this->value($source, 'discount_value');
        if ($value === null || $value === '' || (float) $value <= 0) {
            $issues[] = 'Enter a discount value before activating.';
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    private function announcementIssues(Model|array|Request $source): array
    {
        $issues = [];

        if (trim((string) $this->value($source, 'title')) === '') {
            $issues[] = 'Add an announcement title before activating.';
        }

        $body = $this->value($source, 'body');
        if (trim((string) $body) === '') {
            $issues[] = 'Add announcement message text before activating.';
        }

        return $issues;
    }

    private function value(Model|array|Request $source, string $key): mixed
    {
        if ($source instanceof Request) {
            return $source->input($key);
        }

        if ($source instanceof Model) {
            return $source->getAttribute($key);
        }

        return $source[$key] ?? null;
    }
}
