<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('banners');

        $mobile = Category::query()->where('slug', 'like', '%mobile%')->first()
            ?? Category::query()->whereNull('parent_id')->orderBy('sort_order')->first();

        $ac = Category::query()->where('slug', 'like', '%air%')->orWhere('name', 'like', '%Air Conditioner%')->first();
        $tv = Category::query()->where('slug', 'like', '%televis%')->orWhere('name', 'like', '%Television%')->first();

        $slides = [
            [
                'title' => 'Summer AC Offers',
                'subtitle' => 'Split & Inverter ACs starting low EMI',
                'color' => '#0d9488',
                'link_type' => $ac ? 'category' : 'none',
                'link_value' => $ac?->id,
                'sort_order' => 0,
            ],
            [
                'title' => 'Latest Smartphones',
                'subtitle' => 'New launches with exclusive exchange deals',
                'color' => '#1e40af',
                'link_type' => $mobile ? 'category' : 'none',
                'link_value' => $mobile?->id,
                'sort_order' => 1,
            ],
            [
                'title' => 'Big Screen Festival',
                'subtitle' => '4K & QLED TVs — limited stock',
                'color' => '#b45309',
                'link_type' => $tv ? 'category' : 'none',
                'link_value' => $tv?->id,
                'sort_order' => 2,
            ],
        ];

        foreach ($slides as $slide) {
            $path = $this->makePlaceholderPng(
                $slide['title'],
                $slide['subtitle'],
                $slide['color']
            );

            Banner::query()->updateOrCreate(
                ['title' => $slide['title']],
                [
                    'subtitle' => $slide['subtitle'],
                    'image_path' => $path,
                    'link_type' => $slide['link_type'],
                    'link_value' => $slide['link_value'] ? (string) $slide['link_value'] : null,
                    'sort_order' => $slide['sort_order'],
                    'status' => true,
                    'starts_at' => null,
                    'ends_at' => null,
                ]
            );
        }
    }

    private function makePlaceholderPng(string $title, string $subtitle, string $hex): string
    {
        $filename = 'banners/'.\Illuminate\Support\Str::slug($title).'.png';
        $full = storage_path('app/public/'.$filename);

        $width = 1200;
        $height = 500;
        $img = imagecreatetruecolor($width, $height);

        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $bg = imagecolorallocate($img, $r, $g, $b);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);

        imagestring($img, 5, 48, 200, $title, $white);
        imagestring($img, 3, 48, 240, $subtitle, $white);
        imagestring($img, 2, 48, 440, 'Unique Solution', $white);

        imagepng($img, $full);
        imagedestroy($img);

        return $filename;
    }
}
