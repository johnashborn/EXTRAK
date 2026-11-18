<?php

namespace App\Actions\Documents;

use App\Enums\CopyType;
use App\Enums\DocumentKind;
use App\Models\Document;
use App\Models\Service;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Stores one file on the private disk under a random name and records it against the service.
 */
class AttachDocument
{
    public const DISK = 'local';

    public function __construct(private readonly ActivityLogger $log)
    {
    }

    public function handle(User $by, Service $s, UploadedFile $f, DocumentKind $kind, CopyType $copy): Document
    {
        $extension = strtolower($f->getClientOriginalExtension());
        $directory = "documents/{$s->id}";
        $name = Str::random(40).($extension !== '' ? ".{$extension}" : '');

        $path = Storage::disk(self::DISK)->putFileAs($directory, $f, $name);
        if ($path === false) {
            throw new RuntimeException("Could not store [{$f->getClientOriginalName()}] on the [".self::DISK.'] disk.');
        }

        try {
            $document = $s->documents()->create([
                'kind' => $kind,
                'copy_type' => $copy,
                'original_name' => Str::limit($f->getClientOriginalName(), 250, ''),
                'path' => $path,
                'mime' => Str::limit((string) $f->getMimeType(), 100, ''),
                'size' => (int) $f->getSize(),
                'uploaded_by' => $by->id,
            ]);

            $this->log->log($by, 'document_added', $s, [
                'document_id' => $document->id,
                'kind' => $kind->value,
                'original_name' => $document->original_name,
            ]);
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($path);

            throw $e;
        }

        return $document;
    }
}
