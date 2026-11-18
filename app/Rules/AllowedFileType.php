<?php

namespace App\Rules;

use Closure;
use finfo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

/**
 * The file's extension must be on the allowed list, and its bytes must really be that type.
 * A renamed program (report.pdf that is an .exe) fails. The type list comes from config('extrack.files').
 */
class AllowedFileType implements ValidationRule
{
    /** Word and Excel files are zip containers; some tools save them so they sniff as plain zip. */
    private const ZIP_CONTAINERS = ['docx', 'xlsx'];

    /**
     * @param  list<string>  $types  lower-case extensions, e.g. ['pdf', 'jpg']
     */
    public function __construct(private readonly array $types)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $allowed = array_map('strtolower', $this->types);
        $message = 'The :attribute must be a '.implode(', ', $allowed).' file, and its contents must match its extension.';

        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return; // the "file" rule reports this
        }

        $extension = strtolower($value->getClientOriginalExtension());
        if (! in_array($extension, $allowed, true)) {
            $fail($message);

            return;
        }

        $path = $value->getRealPath();
        $mime = $path ? (new finfo(FILEINFO_MIME_TYPE))->file($path) : false;
        if ($mime === false) {
            $fail($message);

            return;
        }

        $sniffed = MimeTypes::getDefault()->getExtensions($mime);
        if ($mime === 'application/zip' && in_array($extension, self::ZIP_CONTAINERS, true)) {
            $sniffed[] = $extension;
        }

        $same = in_array($extension, $sniffed, true)
            || (in_array($extension, ['jpg', 'jpeg'], true) && array_intersect(['jpg', 'jpeg'], $sniffed) !== []);

        if (! $same) {
            $fail($message);
        }
    }
}
