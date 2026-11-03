<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Store a campaign image as a resized, compressed JPEG.
     *
     * Existing external/legacy URLs are intentionally left untouched when
     * replacing an image; only files owned by the public disk are removed.
     */
    public function storeCampaignCover(UploadedFile $file, ?string $previousUrl = null): string
    {
        return $this->storeCampaignImage($file, $previousUrl);
    }

    public function storeCampaignMedia(UploadedFile $file, ?string $previousUrl = null): string
    {
        return $this->storeCampaignImage($file, $previousUrl);
    }

    public function deleteOwnedFile(?string $url): void
    {
        if (! $url) {
            return;
        }

        $disk = Storage::disk('public');
        $diskUrl = rtrim((string) config('filesystems.disks.public.url'), '/');
        $path = null;

        if ($diskUrl !== '' && Str::startsWith($url, $diskUrl.'/')) {
            $path = Str::after($url, $diskUrl.'/');
        } elseif (Str::startsWith($url, '/storage/')) {
            $path = Str::after($url, '/storage/');
        }

        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }
    }

    private function storeCampaignImage(UploadedFile $file, ?string $previousUrl = null): string
    {
        $path = 'campaigns/'.Str::uuid().'.jpg';
        $disk = Storage::disk('public');
        $contents = $this->optimizedJpeg($file);

        $disk->put($path, $contents);
        $this->deleteOwnedFile($previousUrl);

        // Use a relative public URL so local and deployed domains never leak
        // the APP_URL value used by another environment.
        return '/storage/'.$path;
    }

    private function optimizedJpeg(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            return (string) file_get_contents($file->getRealPath());
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, 1600 / max(1, $width), 1000 / max(1, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 80);
        $contents = (string) ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return $contents;
    }

}
