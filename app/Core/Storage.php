<?php
namespace App\Core;

class Storage
{
    public static function putUploadedFile(array $file, string $directory): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $filename = uniqid('', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $targetDir = rtrim($directory, '/');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $path = $targetDir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return null;
        }
        return $path;
    }
}
