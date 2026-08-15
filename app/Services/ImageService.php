<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Store an uploaded image on the public disk and create a 300x300 contain thumbnail.
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $folder = trim($folder, '/');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;

        $path = $file->storeAs($folder, $filename, 'public');

        $this->createThumbnail($path);

        return $path;
    }

    /**
     * Delete an image and its thumbnail from the public disk if they exist.
     */
    public function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        $thumbnail = $this->thumbnailPath($path);

        if ($disk->exists($thumbnail)) {
            $disk->delete($thumbnail);
        }
    }

    public function thumbnailPath(string $path): string
    {
        $directory = trim(dirname($path), '.');
        $filename = basename($path);
        $thumbFilename = 'thumb_'.$filename;

        if ($directory === '' || $directory === '/') {
            return $thumbFilename;
        }

        return $directory.'/'.$thumbFilename;
    }

    protected function createThumbnail(string $path): void
    {
        $disk = Storage::disk('public');
        $absolutePath = $disk->path($path);

        if (! is_file($absolutePath)) {
            return;
        }

        $thumbnailRelative = $this->thumbnailPath($path);
        $thumbnailAbsolute = $disk->path($thumbnailRelative);

        $directory = dirname($thumbnailAbsolute);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        Image::decodePath($absolutePath)
            ->contain(300, 300)
            ->save($thumbnailAbsolute);
    }
}
