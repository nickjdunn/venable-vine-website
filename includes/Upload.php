<?php

class Upload
{
    public static function image(array $file, string $subdir = 'general'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
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
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'bin',
        };
        $dir = PUBLIC_ROOT . '/uploads/' . trim($subdir, '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not save uploaded file.');
        }
        return 'uploads/' . trim($subdir, '/') . '/' . $filename;
    }

    /** Save to public assets/images/ (site media library). */
    public static function mediaImage(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('No file uploaded.');
        }
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
        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'bin',
        };
        $original = pathinfo($file['name'] ?? '', PATHINFO_FILENAME);
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $original) ?: 'image';
        $safe = trim(substr($safe, 0, 80), '-');
        $dir = PUBLIC_ROOT . '/assets/images';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = $safe . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('Could not save uploaded file.');
        }
        return 'assets/images/' . $filename;
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
    }
}
