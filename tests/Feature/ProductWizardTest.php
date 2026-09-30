<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\StagedUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductWizardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        foreach (['products.view', 'products.create', 'products.update'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $role = \Spatie\Permission\Models\Role::findOrCreate('Manager', 'web');
        $role->givePermissionTo(['products.view', 'products.create', 'products.update']);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function stage(User $user, string $folder = 'products'): string
    {
        $res = $this->actingAs($user)->postJson(route('admin.products.upload-image'), [
            'image' => UploadedFile::fake()->image('p.jpg', 400, 400),
            'folder' => $folder,
        ])->assertOk();

        return $res->json('token');
    }

    public function test_create_and_reedit_with_staged_bulk_images(): void
    {
        Storage::fake('public');
        $user = $this->admin();
        $category = Category::query()->create(['name' => 'Phones', 'slug' => 'phones', 'status' => true]);

        $gallery = [];
        for ($i = 0; $i < 16; $i++) {
            $gallery[] = $this->stage($user);
        }
        $variantTokens = [$this->stage($user, 'variants'), $this->stage($user, 'variants')];

        $this->actingAs($user)->post(route('admin.products.store'), [
            'name' => 'Test Phone',
            'category_id' => $category->id,
            'status' => 'inactive',
            'is_featured' => '1',
            'gallery_uploads' => $gallery,
            'gallery_order' => array_map(fn ($i) => 'new:'.$i, array_keys($gallery)),
            'variants' => [
                ['sku' => 'TP-1', 'price' => 1000, 'discount_price' => 900, 'stock_quantity' => 5, 'low_stock_threshold' => 2, 'status' => 1,
                    'images_managed' => 1, 'uploaded_images' => $variantTokens, 'image_order' => ['new:1', 'new:0']],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product = Product::query()->with(['images', 'variants.images'])->firstOrFail();
        $this->assertTrue((bool) $product->is_featured);
        $this->assertCount(16, $product->images);
        $this->assertTrue((bool) $product->images->first()->is_primary);
        $this->assertEquals(1000, (float) $product->base_price);
        $variant = $product->variants->first();
        $this->assertSame(2, (int) $variant->low_stock_threshold);
        $this->assertCount(2, $variant->images);
        $this->assertSame(StagedUpload::resolve($variantTokens[1]), $variant->images->first()->image_path);

        // Re-edit: new thumbnail in slot 1, drop one old photo, change price, turn featured off.
        $images = $product->images;
        $newThumb = $this->stage($user);
        $order = array_merge(['new:0'], $images->skip(1)->map(fn ($img) => 'existing:'.$img->id)->values()->all());
        $keepVariantImage = $variant->images->last();

        $this->actingAs($user)->put(route('admin.products.update', $product), [
            'name' => 'Test Phone 2',
            'category_id' => $category->id,
            'status' => 'inactive',
            'is_featured' => '0',
            'gallery_uploads' => [$newThumb],
            'gallery_order' => $order,
            'remove_image_ids' => [$images->first()->id],
            'variants' => [
                ['id' => $variant->id, 'sku' => 'TP-1', 'price' => 1200, 'discount_price' => 1100, 'stock_quantity' => 7, 'low_stock_threshold' => 3, 'status' => 1,
                    'images_managed' => 1, 'keep_image_ids' => [$keepVariantImage->id], 'image_order' => ['existing:'.$keepVariantImage->id]],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $product->refresh()->load(['images', 'variants.images']);
        $this->assertFalse((bool) $product->is_featured);
        $this->assertCount(16, $product->images);
        $this->assertSame(StagedUpload::resolve($newThumb), $product->images->first()->image_path);
        $this->assertTrue((bool) $product->images->first()->is_primary);
        $this->assertSame(1, $product->images->where('is_primary', true)->count());
        $this->assertEquals(1200, (float) $product->base_price);
        $this->assertCount(1, $product->variants->first()->images);
        $this->assertSame(3, (int) $product->variants->first()->low_stock_threshold);

        $this->actingAs($user)->get(route('admin.products.edit', $product))->assertOk()->assertSee('Save changes');
        $this->actingAs($user)->get(route('admin.products.create'))->assertOk();
    }

    public function test_brand_form_puts_visibility_last_and_actions_beside_preview(): void
    {
        foreach (['brands.create', 'brands.update'] as $name) {
            Permission::findOrCreate($name, 'web');
        }
        $user = $this->admin();
        $user->givePermissionTo(['brands.create', 'brands.update']);

        $create = $this->actingAs($user)->get(route('admin.brands.create'))->assertOk()->getContent();
        $policy = strpos($create, 'Policy details');
        $review = strpos($create, 'id="brandReview"');
        $visibility = strpos($create, 'App visibility');
        $preview = strpos($create, '>Preview<');
        $this->assertNotFalse($policy);
        $this->assertTrue($policy < $review && $review < $visibility);
        $this->assertTrue($preview < strpos($create, 'id="btnBrandReview"'));
        $this->assertTrue(strpos($create, 'id="btnBrandReview"') < strpos($create, '>Cancel<'));
        $this->assertTrue(strpos($create, '>Cancel<') < strpos($create, 'id="btnBrandReset"'));

        $category = Category::query()->create(['name' => 'Mobiles', 'slug' => 'mobiles', 'status' => true]);
        $brand = \App\Models\Brand::query()->create(['name' => 'Samsung', 'status' => false, 'category_id' => $category->id]);
        $edit = $this->actingAs($user)->get(route('admin.brands.edit', $brand))->assertOk()->getContent();
        $this->assertTrue(strpos($edit, 'Policy details') < strpos($edit, 'id="brandReview"'));
        $this->assertTrue(strpos($edit, 'id="brandReview"') < strpos($edit, 'App visibility'));
        $this->assertStringContainsString('id="btnBrandReset"', $edit);
    }

    public function test_forged_token_is_ignored(): void
    {
        Storage::fake('public');
        $user = $this->admin();
        $category = Category::query()->create(['name' => 'Phones', 'slug' => 'phones', 'status' => true]);

        $this->actingAs($user)->post(route('admin.products.store'), [
            'name' => 'Forged',
            'category_id' => $category->id,
            'status' => 'inactive',
            'gallery_uploads' => ['../../.env', encrypt('x')],
            'variants' => [['sku' => 'F-1', 'price' => 10]],
        ])->assertSessionHasNoErrors();

        $this->assertCount(0, Product::query()->firstOrFail()->images);
    }
}
