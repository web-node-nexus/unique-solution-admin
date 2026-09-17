<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Max longest edge for stored originals (keeps banners/products app-friendly).
     */
    private const MAX_EDGE = 1920;

    /**
     * Store an uploaded image on the public disk (resized/compressed) + 300x300 thumb.
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        $folder = trim($folder, '/');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $extension = 'jpg';
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $path = trim($folder.'/'.$filename, '/');

        $disk = Storage::disk('public');
        $absolutePath = $disk->path($path);
        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $source = $file->getRealPath() ?: $file->getPathname();

        try {
            $image = Image::decodePath($source);
            $image->scaleDown(width: self::MAX_EDGE, height: self::MAX_EDGE);

            if (in_array($extension, ['jpg', 'jpeg'], true)) {
                $image->toJpeg(82)->save($absolutePath);
            } elseif ($extension === 'png') {
                $image->toPng()->save($absolutePath);
            } elseif ($extension === 'webp') {
                $image->toWebp(82)->save($absolutePath);
            } else {
                $image->save($absolutePath);
            }
        } catch (\Throwable) {
            // Fallback: store original bytes if decode/resize fails.
            $file->storeAs($folder, $filename, 'public');
        }

        $this->createThumbnail($path);

        return $path;
    }

    /**
     * Copy an existing public-disk image into a new folder (used when duplicating).
     */
    public function copy(?string $path, string $folder): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return $path;
        }

        $folder = trim($folder, '/');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;
        $destination = trim($folder.'/'.$filename, '/');

        $disk->copy($path, $destination);

        $thumb = $this->thumbnailPath($path);
        if ($disk->exists($thumb)) {
            $disk->copy($thumb, $this->thumbnailPath($destination));
        }

        return $destination;
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

        try {
            Image::decodePath($absolutePath)
                ->contain(300, 300)
                ->save($thumbnailAbsolute);
        } catch (\Throwable) {
            // ignore thumbnail failures
        }
    }
}
