<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\VariantGeneratorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@uniquesolution.com')->first();
        $variantGenerator = app(VariantGeneratorService::class);

        $categories = $this->seedCategories();
        $attributes = $this->seedAttributes();
        $this->mapCategoryAttributes($categories, $attributes);
        $brands = $this->seedBrands();
        $this->seedProducts($categories, $brands, $attributes, $variantGenerator, $admin?->id);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $parents = [
            'Air Conditioners' => 1,
            'Refrigerators' => 2,
            'Laptops' => 3,
            'Mobile Phones' => 4,
            'Televisions' => 5,
            'Washing Machines' => 6,
            'Microwaves' => 7,
        ];

        $map = [];

        foreach ($parents as $name => $sort) {
            $map[$name] = Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'parent_id' => null,
                    'image' => null,
                    'status' => true,
                    'sort_order' => $sort,
                ]
            );
        }

        $subcategories = [
            'Split ACs' => ['Air Conditioners', 1],
            'Window ACs' => ['Air Conditioners', 2],
            'Smartphones' => ['Mobile Phones', 1],
            'Android Phones' => ['Mobile Phones', 2],
        ];

        foreach ($subcategories as $name => [$parentName, $sort]) {
            $map[$name] = Category::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'parent_id' => $map[$parentName]->id,
                    'image' => null,
                    'status' => true,
                    'sort_order' => $sort,
                ]
            );
        }

        return $map;
    }

    /**
     * @return array<string, array{attribute: Attribute, values: array<string, AttributeValue>}>
     */
    private function seedAttributes(): array
    {
        $definitions = [
            'Color' => [
                'type' => 'color-swatch',
                'values' => [
                    'Black' => ['hex' => '#000000'],
                    'White' => ['hex' => '#FFFFFF'],
                    'Silver' => ['hex' => '#C0C0C0'],
                    'Blue' => ['hex' => '#1E90FF'],
                    'Red' => ['hex' => '#E53935'],
                ],
            ],
            'Storage' => [
                'type' => 'dropdown',
                'values' => [
                    '64GB' => null,
                    '128GB' => null,
                    '256GB' => null,
                    '512GB' => null,
                    '1TB' => null,
                ],
            ],
            'RAM' => [
                'type' => 'dropdown',
                'values' => [
                    '4GB' => null,
                    '8GB' => null,
                    '12GB' => null,
                    '16GB' => null,
                ],
            ],
            'Star Rating' => [
                'type' => 'dropdown',
                'values' => [
                    '3 Star' => null,
                    '5 Star' => null,
                ],
            ],
            'AC Capacity' => [
                'type' => 'dropdown',
                'values' => [
                    '1 Ton' => null,
                    '1.5 Ton' => null,
                    '2 Ton' => null,
                ],
            ],
            'AC Type' => [
                'type' => 'dropdown',
                'values' => [
                    'Split' => null,
                    'Window' => null,
                    'Inverter' => null,
                ],
            ],
            'Fridge Capacity' => [
                'type' => 'dropdown',
                'values' => [
                    '190L' => null,
                    '250L' => null,
                    '340L' => null,
                    '450L' => null,
                ],
            ],
            'Door Type' => [
                'type' => 'dropdown',
                'values' => [
                    'Single Door' => null,
                    'Double Door' => null,
                    'Triple Door' => null,
                ],
            ],
            'Screen Size' => [
                'type' => 'dropdown',
                'values' => [
                    '32 inch' => null,
                    '43 inch' => null,
                    '55 inch' => null,
                    '65 inch' => null,
                ],
            ],
            'Resolution' => [
                'type' => 'dropdown',
                'values' => [
                    'HD' => null,
                    'Full HD' => null,
                    '4K' => null,
                    '8K' => null,
                ],
            ],
            'TV Type' => [
                'type' => 'dropdown',
                'values' => [
                    'LED' => null,
                    'OLED' => null,
                    'QLED' => null,
                ],
            ],
            'WM Capacity' => [
                'type' => 'dropdown',
                'values' => [
                    '6 kg' => null,
                    '7 kg' => null,
                    '8 kg' => null,
                    '10 kg' => null,
                ],
            ],
            'WM Type' => [
                'type' => 'dropdown',
                'values' => [
                    'Top Load' => null,
                    'Front Load' => null,
                ],
            ],
        ];

        $map = [];

        foreach ($definitions as $name => $definition) {
            $attribute = Attribute::query()->updateOrCreate(
                ['name' => $name],
                [
                    'type' => $definition['type'],
                    'status' => true,
                ]
            );

            $values = [];

            foreach ($definition['values'] as $value => $extra) {
                $values[$value] = AttributeValue::query()->updateOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'value' => $value,
                    ],
                    [
                        'extra_data' => $extra,
                    ]
                );
            }

            $map[$name] = [
                'attribute' => $attribute,
                'values' => $values,
            ];
        }

        return $map;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, array{attribute: Attribute, values: array<string, AttributeValue>}>  $attributes
     */
    private function mapCategoryAttributes(array $categories, array $attributes): void
    {
        $mappings = [
            'Mobile Phones' => ['Color', 'Storage', 'RAM'],
            'Smartphones' => ['Color', 'Storage', 'RAM'],
            'Android Phones' => ['Color', 'Storage', 'RAM'],
            'Laptops' => ['Color', 'Storage', 'RAM'],
            'Air Conditioners' => ['Star Rating', 'AC Capacity', 'AC Type'],
            'Split ACs' => ['Star Rating', 'AC Capacity', 'AC Type'],
            'Window ACs' => ['Star Rating', 'AC Capacity', 'AC Type'],
            'Refrigerators' => ['Fridge Capacity', 'Star Rating', 'Door Type', 'Color'],
            'Televisions' => ['Screen Size', 'Resolution', 'TV Type'],
            'Washing Machines' => ['WM Capacity', 'WM Type', 'Color'],
            'Microwaves' => ['Color', 'Star Rating'],
        ];

        foreach ($mappings as $categoryName => $attributeNames) {
            if (! isset($categories[$categoryName])) {
                continue;
            }

            $ids = [];
            foreach ($attributeNames as $attributeName) {
                $ids[] = $attributes[$attributeName]['attribute']->id;
            }

            $categories[$categoryName]->attributes()->sync($ids);
        }
    }

    /**
     * @return array<string, Brand>
     */
    private function seedBrands(): array
    {
        $names = [
            'Samsung', 'LG', 'Sony', 'Apple', 'Dell',
            'Whirlpool', 'Voltas', 'Mi', 'OnePlus', 'Panasonic',
        ];

        $map = [];

        foreach ($names as $name) {
            $map[$name] = Brand::query()->updateOrCreate(
                ['name' => $name],
                [
                    'logo' => null,
                    'status' => true,
                ]
            );
        }

        return $map;
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, Brand>  $brands
     * @param  array<string, array{attribute: Attribute, values: array<string, AttributeValue>}>  $attributes
     */
    private function seedProducts(
        array $categories,
        array $brands,
        array $attributes,
        VariantGeneratorService $variantGenerator,
        ?int $createdBy
    ): void {
        $products = [
            [
                'name' => 'Samsung Galaxy A54',
                'category' => 'Mobile Phones',
                'brand' => 'Samsung',
                'base_price' => 34999,
                'is_featured' => true,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'Samsung Galaxy A54 5G with Super AMOLED display, OIS camera and long-lasting battery — ideal daily driver.',
                'selected' => [
                    'Color' => ['Black', 'Blue'],
                    'Storage' => ['128GB', '256GB'],
                    'RAM' => ['8GB'],
                ],
                'pricing' => [
                    'Black|128GB|8GB' => ['price' => 34999, 'discount_price' => 32999, 'stock' => 25, 'threshold' => 5],
                    'Black|256GB|8GB' => ['price' => 38999, 'discount_price' => 36999, 'stock' => 18, 'threshold' => 5],
                    'Blue|128GB|8GB' => ['price' => 34999, 'discount_price' => null, 'stock' => 4, 'threshold' => 5], // low stock
                    'Blue|256GB|8GB' => ['price' => 38999, 'discount_price' => 35999, 'stock' => 12, 'threshold' => 5],
                ],
            ],
            [
                'name' => 'OnePlus Nord CE 3',
                'category' => 'Mobile Phones',
                'brand' => 'OnePlus',
                'base_price' => 26999,
                'is_featured' => true,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'OnePlus Nord CE 3 with smooth OxygenOS experience, fast charging and dual stereo speakers.',
                'selected' => [
                    'Color' => ['Black', 'Silver'],
                    'Storage' => ['128GB'],
                    'RAM' => ['8GB', '12GB'],
                ],
                'pricing' => [
                    'Black|128GB|8GB' => ['price' => 26999, 'discount_price' => 24999, 'stock' => 30, 'threshold' => 5],
                    'Black|128GB|12GB' => ['price' => 28999, 'discount_price' => null, 'stock' => 3, 'threshold' => 5], // low stock
                    'Silver|128GB|8GB' => ['price' => 26999, 'discount_price' => 25999, 'stock' => 20, 'threshold' => 5],
                    'Silver|128GB|12GB' => ['price' => 28999, 'discount_price' => 27999, 'stock' => 15, 'threshold' => 5],
                ],
            ],
            [
                'name' => 'Apple iPhone 15',
                'category' => 'Mobile Phones',
                'brand' => 'Apple',
                'base_price' => 79900,
                'is_featured' => true,
                'warranty_info' => '1 Year Apple India Warranty',
                'description' => 'iPhone 15 with Dynamic Island, A16 Bionic chip and advanced dual-camera system.',
                'selected' => [
                    'Color' => ['Black', 'Blue'],
                    'Storage' => ['128GB', '256GB'],
                    'RAM' => ['8GB'],
                ],
                'pricing' => [
                    'Black|128GB|8GB' => ['price' => 79900, 'discount_price' => 77900, 'stock' => 10, 'threshold' => 3],
                    'Black|256GB|8GB' => ['price' => 89900, 'discount_price' => null, 'stock' => 8, 'threshold' => 3],
                    'Blue|128GB|8GB' => ['price' => 79900, 'discount_price' => 76900, 'stock' => 2, 'threshold' => 3], // low stock
                    'Blue|256GB|8GB' => ['price' => 89900, 'discount_price' => 86900, 'stock' => 6, 'threshold' => 3],
                ],
            ],
            [
                'name' => 'Redmi Note 13 Pro',
                'category' => 'Mobile Phones',
                'brand' => 'Mi',
                'base_price' => 24999,
                'is_featured' => false,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'Redmi Note 13 Pro with AMOLED display, 200MP camera and powerful charging.',
                'selected' => [
                    'Color' => ['Black', 'White'],
                    'Storage' => ['128GB', '256GB'],
                    'RAM' => ['8GB'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Dell Inspiron 15',
                'category' => 'Laptops',
                'brand' => 'Dell',
                'base_price' => 58990,
                'is_featured' => true,
                'warranty_info' => '1 Year Onsite Warranty',
                'description' => 'Dell Inspiron 15 laptop for everyday productivity with Full HD display and SSD storage.',
                'selected' => [
                    'Color' => ['Silver', 'Black'],
                    'Storage' => ['512GB', '1TB'],
                    'RAM' => ['8GB', '16GB'],
                ],
                'only_priced' => true,
                'pricing' => [
                    'Silver|512GB|8GB' => ['price' => 58990, 'discount_price' => 55990, 'stock' => 12, 'threshold' => 4],
                    'Silver|1TB|16GB' => ['price' => 74990, 'discount_price' => 71990, 'stock' => 7, 'threshold' => 4],
                    'Black|512GB|8GB' => ['price' => 58990, 'discount_price' => null, 'stock' => 4, 'threshold' => 4],
                    'Black|1TB|16GB' => ['price' => 74990, 'discount_price' => 69990, 'stock' => 5, 'threshold' => 4],
                ],
            ],
            [
                'name' => 'Apple MacBook Air M2',
                'category' => 'Laptops',
                'brand' => 'Apple',
                'base_price' => 99900,
                'is_featured' => true,
                'warranty_info' => '1 Year Apple India Warranty',
                'description' => 'MacBook Air with M2 chip — silent, powerful and ultra-portable for work and creativity.',
                'selected' => [
                    'Color' => ['Silver', 'Black'],
                    'Storage' => ['256GB', '512GB'],
                    'RAM' => ['8GB'],
                ],
                'pricing' => [
                    'Silver|256GB|8GB' => ['price' => 99900, 'discount_price' => 96900, 'stock' => 9, 'threshold' => 3],
                    'Silver|512GB|8GB' => ['price' => 119900, 'discount_price' => null, 'stock' => 6, 'threshold' => 3],
                    'Black|256GB|8GB' => ['price' => 99900, 'discount_price' => 97900, 'stock' => 5, 'threshold' => 3],
                    'Black|512GB|8GB' => ['price' => 119900, 'discount_price' => 114900, 'stock' => 4, 'threshold' => 3],
                ],
            ],
            [
                'name' => 'Samsung Galaxy Book3',
                'category' => 'Laptops',
                'brand' => 'Samsung',
                'base_price' => 69990,
                'is_featured' => false,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'Samsung Galaxy Book3 with AMOLED screen and lightweight aluminium body.',
                'selected' => [
                    'Color' => ['Silver'],
                    'Storage' => ['512GB'],
                    'RAM' => ['8GB', '16GB'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Voltas 1.5 Ton Split Inverter AC',
                'category' => 'Air Conditioners',
                'brand' => 'Voltas',
                'base_price' => 38990,
                'is_featured' => true,
                'warranty_info' => '1 Year Comprehensive, 10 Years Compressor',
                'description' => 'Voltas Split Inverter AC with efficient cooling for Indian summers.',
                'selected' => [
                    'Star Rating' => ['3 Star', '5 Star'],
                    'AC Capacity' => ['1.5 Ton'],
                    'AC Type' => ['Split', 'Inverter'],
                ],
                'pricing' => [
                    '3 Star|1.5 Ton|Split' => ['price' => 35990, 'discount_price' => 33990, 'stock' => 14, 'threshold' => 5],
                    '3 Star|1.5 Ton|Inverter' => ['price' => 38990, 'discount_price' => 36990, 'stock' => 10, 'threshold' => 5],
                    '5 Star|1.5 Ton|Split' => ['price' => 41990, 'discount_price' => null, 'stock' => 8, 'threshold' => 5],
                    '5 Star|1.5 Ton|Inverter' => ['price' => 44990, 'discount_price' => 42990, 'stock' => 6, 'threshold' => 5],
                ],
            ],
            [
                'name' => 'LG 1 Ton Window AC',
                'category' => 'Air Conditioners',
                'brand' => 'LG',
                'base_price' => 27990,
                'is_featured' => false,
                'warranty_info' => '1 Year Comprehensive Warranty',
                'description' => 'LG Window AC with powerful cooling and easy installation for compact rooms.',
                'selected' => [
                    'Star Rating' => ['3 Star', '5 Star'],
                    'AC Capacity' => ['1 Ton'],
                    'AC Type' => ['Window'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Panasonic 2 Ton Split AC',
                'category' => 'Air Conditioners',
                'brand' => 'Panasonic',
                'base_price' => 52990,
                'is_featured' => false,
                'warranty_info' => '1 Year Comprehensive, 5 Years PCB',
                'description' => 'Panasonic 2 Ton Split AC for larger rooms with reliable performance.',
                'selected' => [
                    'Star Rating' => ['5 Star'],
                    'AC Capacity' => ['2 Ton'],
                    'AC Type' => ['Split', 'Inverter'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Samsung 253L Double Door Refrigerator',
                'category' => 'Refrigerators',
                'brand' => 'Samsung',
                'base_price' => 26990,
                'is_featured' => true,
                'warranty_info' => '1 Year Comprehensive, 10 Years Compressor',
                'description' => 'Samsung double door refrigerator with Digital Inverter technology and spacious freezer.',
                'selected' => [
                    'Fridge Capacity' => ['250L'],
                    'Star Rating' => ['3 Star', '5 Star'],
                    'Door Type' => ['Double Door'],
                    'Color' => ['Silver', 'Black'],
                ],
                'pricing' => [
                    '250L|3 Star|Double Door|Silver' => ['price' => 24990, 'discount_price' => 23490, 'stock' => 11, 'threshold' => 4],
                    '250L|3 Star|Double Door|Black' => ['price' => 24990, 'discount_price' => null, 'stock' => 9, 'threshold' => 4],
                    '250L|5 Star|Double Door|Silver' => ['price' => 27990, 'discount_price' => 26490, 'stock' => 7, 'threshold' => 4],
                    '250L|5 Star|Double Door|Black' => ['price' => 27990, 'discount_price' => 25990, 'stock' => 5, 'threshold' => 4],
                ],
            ],
            [
                'name' => 'LG 190L Single Door Refrigerator',
                'category' => 'Refrigerators',
                'brand' => 'LG',
                'base_price' => 15990,
                'is_featured' => false,
                'warranty_info' => '1 Year Comprehensive, 10 Years Compressor',
                'description' => 'LG single door fridge perfect for small families with smart inverter compressor.',
                'selected' => [
                    'Fridge Capacity' => ['190L'],
                    'Star Rating' => ['3 Star', '5 Star'],
                    'Door Type' => ['Single Door'],
                    'Color' => ['White', 'Red'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Whirlpool 450L Triple Door Refrigerator',
                'category' => 'Refrigerators',
                'brand' => 'Whirlpool',
                'base_price' => 54990,
                'is_featured' => false,
                'warranty_info' => '1 Year Comprehensive Warranty',
                'description' => 'Whirlpool triple door refrigerator with Adaptive Intelligence and large capacity.',
                'selected' => [
                    'Fridge Capacity' => ['450L'],
                    'Star Rating' => ['5 Star'],
                    'Door Type' => ['Triple Door'],
                    'Color' => ['Silver', 'Black'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Sony Bravia 55 inch 4K TV',
                'category' => 'Televisions',
                'brand' => 'Sony',
                'base_price' => 69990,
                'is_featured' => true,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'Sony Bravia 4K Google TV with stunning picture quality and immersive sound.',
                'selected' => [
                    'Screen Size' => ['55 inch'],
                    'Resolution' => ['4K'],
                    'TV Type' => ['LED', 'OLED'],
                ],
                'pricing' => [
                    '55 inch|4K|LED' => ['price' => 69990, 'discount_price' => 64990, 'stock' => 8, 'threshold' => 3],
                    '55 inch|4K|OLED' => ['price' => 119990, 'discount_price' => 112990, 'stock' => 4, 'threshold' => 3],
                ],
            ],
            [
                'name' => 'Samsung 43 inch Smart LED TV',
                'category' => 'Televisions',
                'brand' => 'Samsung',
                'base_price' => 32990,
                'is_featured' => false,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'Samsung Smart LED TV with PurColor and multiple voice assistants.',
                'selected' => [
                    'Screen Size' => ['43 inch'],
                    'Resolution' => ['Full HD', '4K'],
                    'TV Type' => ['LED'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'LG 65 inch QLED TV',
                'category' => 'Televisions',
                'brand' => 'LG',
                'base_price' => 99990,
                'is_featured' => true,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'LG QLED 65-inch TV with rich contrast, webOS smart platform and cinematic sound.',
                'selected' => [
                    'Screen Size' => ['65 inch'],
                    'Resolution' => ['4K', '8K'],
                    'TV Type' => ['QLED'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Mi 32 inch HD Smart TV',
                'category' => 'Televisions',
                'brand' => 'Mi',
                'base_price' => 13499,
                'is_featured' => false,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'Affordable Mi HD Smart TV with PatchWall and built-in Chromecast.',
                'selected' => [
                    'Screen Size' => ['32 inch'],
                    'Resolution' => ['HD'],
                    'TV Type' => ['LED'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'LG 7 kg Front Load Washing Machine',
                'category' => 'Washing Machines',
                'brand' => 'LG',
                'base_price' => 32990,
                'is_featured' => true,
                'warranty_info' => '2 Years Comprehensive, 10 Years Motor',
                'description' => 'LG front load washing machine with AI Direct Drive and steam wash.',
                'selected' => [
                    'WM Capacity' => ['7 kg'],
                    'WM Type' => ['Front Load'],
                    'Color' => ['White', 'Silver'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Samsung 8 kg Top Load Washing Machine',
                'category' => 'Washing Machines',
                'brand' => 'Samsung',
                'base_price' => 24990,
                'is_featured' => false,
                'warranty_info' => '2 Years Comprehensive Warranty',
                'description' => 'Samsung top load washer with Wobble technology and digital inverter motor.',
                'selected' => [
                    'WM Capacity' => ['8 kg', '10 kg'],
                    'WM Type' => ['Top Load'],
                    'Color' => ['White', 'Blue'],
                ],
                'pricing' => [
                    '8 kg|Top Load|White' => ['price' => 24990, 'discount_price' => 22990, 'stock' => 16, 'threshold' => 5],
                    '8 kg|Top Load|Blue' => ['price' => 24990, 'discount_price' => null, 'stock' => 10, 'threshold' => 5],
                    '10 kg|Top Load|White' => ['price' => 28990, 'discount_price' => 26990, 'stock' => 8, 'threshold' => 5],
                    '10 kg|Top Load|Blue' => ['price' => 28990, 'discount_price' => 27490, 'stock' => 6, 'threshold' => 5],
                ],
            ],
            [
                'name' => 'Whirlpool 6 kg Fully Automatic Top Load',
                'category' => 'Washing Machines',
                'brand' => 'Whirlpool',
                'base_price' => 16990,
                'is_featured' => false,
                'warranty_info' => '2 Years Comprehensive Warranty',
                'description' => 'Whirlpool 6 kg top load washing machine with 12 wash programs.',
                'selected' => [
                    'WM Capacity' => ['6 kg'],
                    'WM Type' => ['Top Load'],
                    'Color' => ['White', 'Silver'],
                ],
                'pricing' => null,
            ],
            [
                'name' => 'Panasonic 27L Convection Microwave',
                'category' => 'Microwaves',
                'brand' => 'Panasonic',
                'base_price' => 14990,
                'is_featured' => false,
                'warranty_info' => '1 Year Manufacturer Warranty',
                'description' => 'Panasonic convection microwave with auto cook menus and grill function.',
                'selected' => [
                    'Color' => ['Black', 'Silver'],
                    'Star Rating' => ['3 Star', '5 Star'],
                ],
                'pricing' => null,
            ],
        ];

        foreach ($products as $definition) {
            $this->createProductWithVariants(
                $definition,
                $categories,
                $brands,
                $attributes,
                $variantGenerator,
                $createdBy
            );
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, Category>  $categories
     * @param  array<string, Brand>  $brands
     * @param  array<string, array{attribute: Attribute, values: array<string, AttributeValue>}>  $attributes
     */
    private function createProductWithVariants(
        array $definition,
        array $categories,
        array $brands,
        array $attributes,
        VariantGeneratorService $variantGenerator,
        ?int $createdBy
    ): void {
        $category = $categories[$definition['category']];
        $brand = $brands[$definition['brand']];
        $slug = Str::slug($definition['name']);

        $product = Product::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'name' => $definition['name'],
                'description' => $definition['description'],
                'base_price' => $definition['base_price'],
                'warranty_info' => $definition['warranty_info'],
                'status' => 'active',
                'is_featured' => $definition['is_featured'] ?? false,
                'meta_title' => $definition['name'].' | Unique Solution',
                'meta_description' => Str::limit(strip_tags($definition['description']), 155),
                'created_by' => $createdBy,
            ]
        );

        // Rebuild variants on each seed for consistency.
        $product->variants()->each(function (ProductVariant $variant): void {
            $variant->attributeValues()->detach();
            $variant->delete();
        });

        $selectedByAttributeId = [];

        foreach ($definition['selected'] as $attributeName => $valueNames) {
            $attributeId = $attributes[$attributeName]['attribute']->id;
            $ids = [];

            foreach ($valueNames as $valueName) {
                if (! isset($attributes[$attributeName]['values'][$valueName])) {
                    continue;
                }
                $ids[] = $attributes[$attributeName]['values'][$valueName]->id;
            }

            $selectedByAttributeId[$attributeId] = $ids;
        }

        $rows = $variantGenerator->buildVariantRows($selectedByAttributeId, $definition['name']);
        $pricing = $definition['pricing'] ?? null;
        $onlyPriced = (bool) ($definition['only_priced'] ?? false);

        $index = 0;

        foreach ($rows as $row) {
            $labelKey = implode('|', $row['labels']);

            if ($onlyPriced && $pricing !== null && ! isset($pricing[$labelKey])) {
                continue;
            }

            $priceData = $pricing[$labelKey] ?? null;
            $price = $priceData['price'] ?? ($definition['base_price'] + ($index * 1000));
            $discount = $priceData['discount_price'] ?? (($index % 2 === 0) ? round($price * 0.95, 2) : null);
            $stock = $priceData['stock'] ?? (10 + $index * 3);
            $threshold = $priceData['threshold'] ?? 5;

            $sku = $row['suggested_sku'];
            // Ensure uniqueness if SKU already used
            $baseSku = $sku;
            $suffix = 1;
            while (ProductVariant::query()->where('sku', $sku)->exists()) {
                $sku = $baseSku.'-'.$suffix;
                $suffix++;
            }

            $variant = ProductVariant::query()->create([
                'product_id' => $product->id,
                'sku' => $sku,
                'price' => $price,
                'discount_price' => $discount,
                'stock_quantity' => $stock,
                'low_stock_threshold' => $threshold,
                'weight' => null,
                'status' => true,
            ]);

            $variant->attributeValues()->sync($row['attribute_value_ids']);
            $index++;
        }
    }
}
