<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Down-scales product photos on upload so the gallery never stores — and the
 * browser never downloads — full-size camera images.
 *
 * A 12MP phone photo is ~4000x3000 and several MB. Capped at MAX_EDGE it lands
 * around a couple hundred KB with no visible difference at the sizes product
 * pages render, and re-encoding strips EXIF (including location tags).
 *
 * Every upload also gets a THUMB_EDGE copy for cards/lists: a marketplace grid
 * showing 40px-wide images was pulling the full gallery file per card.
 *
 * EXIF orientation is applied before the re-encode (rotation is lost with the
 * metadata), so a phone portrait photo stays upright.
 *
 * Everything degrades safely: if GD is unavailable, or the file is not a
 * decodable image (placeholder uploads, unsupported type), the original is
 * stored exactly as it was before this class existed — thumbnail_path then
 * stays null and callers fall back to the full image.
 *
 * GIFs are left untouched on purpose — resizing one would flatten animations.
 */
class ProductImageResizer
{
    /**
     * Longest edge (px) kept for any stored product photo. Rotation-invariant:
     * a 90° turn does not change which edge is longest.
     */
    public const MAX_EDGE = 1600;

    /**
     * Longest edge (px) of the copy served by cards and list views.
     */
    public const THUMB_EDGE = 400;

    /**
     * JPEG/WebP re-encode quality (visually near-transparent at web sizes).
     */
    public const QUALITY = 82;

    /**
     * Quality for thumbnails: the smallest copy, seen at the smallest size.
     */
    public const THUMB_QUALITY = 74;

    /**
     * Files already this small are stored untouched when they also fit inside
     * MAX_EDGE — pointless re-encoding would only risk quality loss.
     */
    public const SKIP_UNDER_BYTES = 300000;

    /**
     * Store an upload on the public disk: a down-scaled main file plus a
     * THUMB_EDGE thumbnail next to it.
     *
     * @param  string  $directory  e.g. "products/12"
     * @return array{path: string, thumb_path: string|null}  paths relative to the public disk
     */
    public static function store(UploadedFile $file, string $directory): array
    {
        [$mainBytes, $thumbBytes] = self::encode($file);

        $path = $mainBytes === null
            ? $file->store($directory, 'public')
            : self::write($directory, $mainBytes);

        $thumbPath = null;

        // A thumbnail that is not smaller than what we would serve instead is
        // dead weight — never store it.
        if ($thumbBytes !== null) {
            $budget = $mainBytes !== null ? strlen($mainBytes) : (int) $file->getSize();

            if (strlen($thumbBytes) < $budget) {
                $thumbPath = self::thumbPathFor($path, $thumbBytes);
                Storage::disk('public')->put($thumbPath, $thumbBytes);
            }
        }

        return ['path' => $path, 'thumb_path' => $thumbPath];
    }

    /**
     * Main-file payload, or null when the original should be stored as-is.
     *
     * Null means "no benefit or not safe to touch": GD missing, not an image,
     * GIF, already small, mirrored-rotate orientation (rare — the original
     * keeps its EXIF so it still displays correctly), or the re-encode came
     * out no smaller than the upload.
     */
    public static function resizedBytes(UploadedFile $file): ?string
    {
        return self::payloads($file->getRealPath() ?: '', (int) $file->getSize())[0];
    }

    /**
     * Build the thumbnail for a file already on the public disk, writing it
     * beside the original. Returns the thumbnail path, or null when one would
     * not help (already small, undecodable, no encoder for the format, or the
     * pair already exists).
     *
     * Used by the backfill for rows stored before thumbnails existed.
     */
    public static function thumbnail(string $path): ?string
    {
        $absolute = Storage::disk('public')->path($path);

        if (! is_file($absolute)) {
            return null;
        }

        $thumbBytes = self::payloads($absolute, (int) filesize($absolute), true)[1];

        if ($thumbBytes === null) {
            return null;
        }

        $thumbPath = self::thumbPathFor($path, $thumbBytes);

        if (Storage::disk('public')->exists($thumbPath)) {
            return $thumbPath;
        }

        Storage::disk('public')->put($thumbPath, $thumbBytes);

        return $thumbPath;
    }

    /**
     * Encode the upload once and hand back both payloads.
     *
     * @return array{0: ?string, 1: ?string} [main, thumbnail]; either may be null
     */
    private static function encode(UploadedFile $file): array
    {
        return self::payloads($file->getRealPath() ?: '', (int) $file->getSize());
    }

    /**
     * @return array{0: ?string, 1: ?string} [main, thumbnail]; either may be null
     */
    private static function payloads(string $source, int $originalSize, bool $thumbOnly = false): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return [null, null];
        }

        if ($source === '' || ! is_file($source)) {
            return [null, null];
        }

        $info = @getimagesize($source);
        if ($info === false) {
            return [null, null]; // Not a real image — keep the previous behaviour.
        }

        [$width, $height, $type] = $info;

        if ($type === IMAGETYPE_GIF) {
            return [null, null]; // Never touch (possibly animated) GIFs.
        }

        if (! in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return [null, null]; // No encoder for it — store the original.
        }

        $orientation = self::orientation($source);

        if ($orientation === 5 || $orientation === 7) {
            return [null, null]; // Mirror+rotate variants: keep the original (with EXIF).
        }

        $binary = @file_get_contents($source);
        if ($binary === false) {
            return [null, null];
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return [null, null];
        }

        [$image, $width, $height] = self::applyOrientation($image, $orientation);

        // A photograph saved as PNG carries no transparency but weighs 5-10x
        // more than the same pixels as JPEG, so opaque PNGs are flattened.
        $encodeType = $type === IMAGETYPE_PNG && self::isOpaquePng($binary)
            ? IMAGETYPE_JPEG
            : $type;

        $thumbBytes = self::thumbBytes($image, $width, $height, $encodeType);

        $needsResize = max($width, $height) > self::MAX_EDGE;
        $mainBytes = ($thumbOnly || (! $needsResize && $originalSize <= self::SKIP_UNDER_BYTES))
            ? null // Not asked for, or already small enough — no needless re-encode.
            : self::resizedPayload($image, $width, $height, $encodeType, $originalSize, $needsResize);

        imagedestroy($image);

        return [$mainBytes, $thumbBytes];
    }

    /**
     * Down-scale to MAX_EDGE and encode, returning null when the result is not
     * an improvement over what is already on disk.
     *
     * @param  resource  $image  oriented source; never mutated
     */
    private static function resizedPayload($image, int $width, int $height, int $encodeType, int $originalSize, bool $needsResize): ?string
    {
        $scale = $needsResize ? min(self::MAX_EDGE / $width, self::MAX_EDGE / $height) : 1.0;
        $outWidth = max(1, (int) round($width * $scale));
        $outHeight = max(1, (int) round($height * $scale));

        $resized = self::resample($image, $width, $height, $outWidth, $outHeight, $encodeType);

        ob_start();
        $written = self::encodeImage($resized, $encodeType, self::QUALITY);
        $bytes = ob_get_clean();
        imagedestroy($resized);

        if (! $written || $bytes === '' || $bytes === null) {
            return null;
        }

        $isSmaller = strlen($bytes) < $originalSize;
        $reachedCap = $needsResize && max($outWidth, $outHeight) <= self::MAX_EDGE;
        $modestGrowth = strlen($bytes) <= (int) ($originalSize * 1.25);

        // Only replace the original when it is actually an improvement: fewer
        // bytes, or the dimension cap was reached for (at worst) marginal growth.
        if (! $isSmaller && ! ($reachedCap && $modestGrowth)) {
            return null;
        }

        return $bytes;
    }

    /**
     * THUMB_EDGE copy of the source, or null when a thumbnail would not help
     * (already small, or the format cannot be encoded).
     *
     * @param  resource  $image  oriented source; never mutated
     */
    private static function thumbBytes($image, int $width, int $height, int $encodeType): ?string
    {
        if (max($width, $height) <= self::THUMB_EDGE) {
            return null;
        }

        $scale = min(self::THUMB_EDGE / $width, self::THUMB_EDGE / $height);
        $outWidth = max(1, (int) round($width * $scale));
        $outHeight = max(1, (int) round($height * $scale));

        $thumb = self::resample($image, $width, $height, $outWidth, $outHeight, $encodeType);

        ob_start();
        $written = self::encodeImage($thumb, $encodeType, self::THUMB_QUALITY);
        $bytes = ob_get_clean();
        imagedestroy($thumb);

        if (! $written || $bytes === '' || $bytes === null) {
            return null;
        }

        return $bytes;
    }

    /**
     * Copy the source into a new truecolor canvas at the target size.
     *
     * @param  resource  $image
     * @return resource
     */
    private static function resample($image, int $width, int $height, int $outWidth, int $outHeight, int $encodeType)
    {
        if ($outWidth === $width && $outHeight === $height) {
            // Nothing to scale: hand back an independent copy so callers can
            // destroy it without freeing the source.
            $copy = imagecreatetruecolor($width, $height);

            if ($encodeType === IMAGETYPE_PNG || $encodeType === IMAGETYPE_WEBP) {
                imagealphablending($copy, false);
                imagesavealpha($copy, true);
            }

            imagecopy($copy, $image, 0, 0, 0, 0, $width, $height);

            return $copy;
        }

        $canvas = imagecreatetruecolor($outWidth, $outHeight);

        if ($encodeType === IMAGETYPE_PNG || $encodeType === IMAGETYPE_WEBP) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
        }

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $outWidth, $outHeight, $width, $height);

        return $canvas;
    }

    /**
     * Encode the canvas to a binary payload (writes to the output buffer).
     *
     * @param  resource  $image
     */
    private static function encodeImage($image, int $encodeType, int $quality): bool
    {
        return match ($encodeType) {
            IMAGETYPE_JPEG => imagejpeg($image, null, $quality),
            IMAGETYPE_PNG => imagepng($image, null, 6),
            IMAGETYPE_WEBP => imagewebp($image, null, $quality),
            default => false,
        };
    }

    /**
     * Write a payload under a fresh random name.
     */
    private static function write(string $directory, string $bytes): string
    {
        $path = $directory . '/' . Str::random(40) . self::extensionFor($bytes);

        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    /**
     * Thumbnail path beside its full-size file: "products/12/aB3x.jpg" becomes
     * "products/12/aB3x.thumb.jpg".
     */
    private static function thumbPathFor(string $path, string $thumbBytes): string
    {
        $extension = self::extensionFor($thumbBytes);
        $base = preg_replace('/\.[^.]+$/', '', $path);

        return $base . '.thumb' . $extension;
    }

    /**
     * Whether a PNG payload carries no transparency, so it is safe to flatten
     * to JPEG. Colour type 0/2 are grayscale/RGB without alpha; anything else
     * (palette, any alpha channel, or a tRNS chunk) stays PNG.
     */
    private static function isOpaquePng(string $bytes): bool
    {
        if (strlen($bytes) < 26 || substr($bytes, 1, 3) !== 'PNG') {
            return false;
        }

        $colorType = ord($bytes[25]); // IHDR: 8-byte signature + len + type + w + h + depth.

        if ($colorType !== 0 && $colorType !== 2) {
            return false;
        }

        return strpos($bytes, 'tRNS') === false;
    }

    /**
     * EXIF orientation tag (1-8); 1 when absent or unreadable.
     */
    private static function orientation(string $source): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }

        $data = @exif_read_data($source);

        return is_array($data) ? (int) ($data['Orientation'] ?? 1) : 1;
    }

    /**
     * Rotate/flip the decoded image so it is stored upright without EXIF, and
     * return the resource with its corrected dimensions.
     *
     * @param  resource  $image
     * @return array{0: resource, 1: int, 2: int}
     */
    private static function applyOrientation($image, int $orientation): array
    {
        if ($orientation === 2) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        } elseif ($orientation === 3 || $orientation === 6 || $orientation === 8) {
            $angle = match ($orientation) {
                3 => 180,
                6 => -90, // EXIF 6 = rotate 90° clockwise; GD rotates counter-clockwise.
                default => 90,
            };

            $rotated = imagerotate($image, $angle, 0);

            if ($rotated !== false) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        return [$image, imagesx($image), imagesy($image)];
    }

    /**
     * File extension (with dot) matching the encoded payload.
     */
    private static function extensionFor(string $bytes): string
    {
        $info = @getimagesizefromstring($bytes);

        return match ($info[2] ?? null) {
            IMAGETYPE_PNG => '.png',
            IMAGETYPE_WEBP => '.webp',
            default => '.jpg',
        };
    }
}
