<?php

namespace App\Actions\Prototype;

use App\Models\PrototypeFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Keeps an uploaded photo or document on the private local disk and records it.
 */
class StorePrototypeFile
{
    public const DISK = 'local';

    public function __invoke(UploadedFile $upload, ?User $user): PrototypeFile
    {
        $path = $upload->store('prototype', self::DISK);

        return PrototypeFile::create([
            'original_name' => $upload->getClientOriginalName(),
            'mime_type' => $upload->getMimeType() ?: 'application/octet-stream',
            'size' => $upload->getSize(),
            'path' => $path,
            'uploaded_by' => $user?->id,
        ]);
    }
}
