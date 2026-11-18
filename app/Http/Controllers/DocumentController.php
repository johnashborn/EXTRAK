<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    /** Only these open in the browser; SVG and anything else can carry script, so it downloads. */
    /** The sandbox token blanks Chromium's PDF viewer, so PDFs get the same lockdown without it. */
    private const PDF_CSP = "default-src 'none'; style-src 'unsafe-inline'";

    private const DEFAULT_CSP = "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox";

    private const INLINE_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function show(Request $request, Document $document, ActivityLogger $logger): Response
    {
        Gate::authorize('view', $document);

        $inline = in_array($document->mime, self::INLINE_MIMES, true);

        return $this->send($request, $document, $logger, $inline ? 'inline' : 'attachment', 'document_viewed');
    }

    public function download(Request $request, Document $document, ActivityLogger $logger): Response
    {
        Gate::authorize('view', $document);

        return $this->send($request, $document, $logger, 'attachment', 'document_downloaded');
    }

    private function send(Request $request, Document $document, ActivityLogger $logger, string $type, string $action): Response
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->path), 404);

        $logger->log($request->user(), $action, $document);

        // Control characters never belong in a header; the real name otherwise survives in filename*.
        $name = str_replace(['/', '\\'], '-', preg_replace('/[\x00-\x1F\x7F]/', '', $document->original_name));
        $fallback = str_replace(['%', '/', '\\'], '-', preg_replace('/[\x00-\x1F\x7F]/', '', Str::ascii($name)));

        return $disk->response($document->path, $name, [
            'Content-Type' => $document->mime,
            'Content-Disposition' => HeaderUtils::makeDisposition($type, $name, $fallback === '' ? 'document' : $fallback),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => $document->mime === 'application/pdf' ? self::PDF_CSP : self::DEFAULT_CSP,
        ]);
    }
}
