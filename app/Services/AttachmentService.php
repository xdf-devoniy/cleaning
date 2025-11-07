<?php
namespace App\Services;

use App\Core\Storage;

class AttachmentService
{
    public function store(array $file, string $folder): ?string
    {
        return Storage::putUploadedFile($file, $folder);
    }
}
