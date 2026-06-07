<?php

class Upload
{
    public static function image(array $file, string $subdir = 'general'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $stored = self::storeImage($file, PUBLIC_ROOT . '/uploads/' . trim($subdir, '/'), 'uploads/' . trim($subdir, '/'));
        return $stored['path'];
    }

    /** Save to assets/images/ as WebP when possible, otherwise original format. */
    public static function mediaImage(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('No file uploaded.');
        }
        $dir = PUBLIC_ROOT . '/assets/images';
        $stored = self::storeImage($file, $dir, 'assets/images');
        // #region agent log
        agent_debug_log('E', 'Upload.php:mediaImage', 'stored image', [
            'path' => $stored['path'],
            'format' => $stored['format'],
            'PUBLIC_ROOT' => PUBLIC_ROOT,
        ]);
        // #endregion
        return $stored['path'];
    }

    /**
     * @return array{path: string, format: string}
     */
    private static function storeImage(array $file, string $absoluteDir, string $relativePrefix): array
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed.');
        }
        $maxBytes = app_config('upload_max_bytes', 5242880);
        if ($file['size'] > $maxBytes) {
            throw new RuntimeException('File is too large.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowed = app_config('allowed_image_types', []);
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('Invalid file type.');
        }
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }
        $original = pathinfo($file['name'] ?? '', PATHINFO_FILENAME);
        $safe = trim(substr(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $original) ?: 'image', 0, 80), '-');
        $token = bin2hex(random_bytes(4));
        $fallbackExt = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'bin',
        };
        $tmpOriginal = $absoluteDir . '/' . $safe . '-' . $token . '.' . $fallbackExt;
        if (!move_uploaded_file($file['tmp_name'], $tmpOriginal)) {
            throw new RuntimeException('Could not save uploaded file.');
        }
        $webpPath = $absoluteDir . '/' . $safe . '-' . $token . '.webp';
        if ($fallbackExt !== 'webp' && self::convertToWebp($tmpOriginal, $mime, $webpPath)) {
            if (is_file($tmpOriginal)) {
                unlink($tmpOriginal);
            }
            return [
                'path' => trim($relativePrefix, '/') . '/' . basename($webpPath),
                'format' => 'webp',
            ];
        }
        return [
            'path' => trim($relativePrefix, '/') . '/' . basename($tmpOriginal),
            'format' => $fallbackExt,
        ];
    }

    private static function convertToWebp(string $source, string $mime, string $dest): bool
    {
        if (!function_exists('imagewebp')) {
            return false;
        }
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png' => @imagecreatefrompng($source),
            'image/gif' => @imagecreatefromgif($source),
            'image/webp' => @imagecreatefromwebp($source),
            default => null,
        };
        if (!$image) {
            return false;
        }
        if ($mime === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
        $ok = imagewebp($image, $dest, 85);
        imagedestroy($image);
        return $ok && is_file($dest);
    }

    public static function delete(?string $relativePath): void
    {
        if (!$relativePath || str_starts_with($relativePath, 'http')) {
            return;
        }
        $full = PUBLIC_ROOT . '/' . ltrim($relativePath, '/');
        if (is_file($full)) {
            unlink($full);
        }
        $base = pathinfo($full, PATHINFO_FILENAME);
        $dir = dirname($full);
        foreach (['webp', 'jpg', 'jpeg', 'png', 'gif'] as $ext) {
            $alt = $dir . '/' . $base . '.' . $ext;
            if ($alt !== $full && is_file($alt)) {
                unlink($alt);
            }
        }
    }
}
