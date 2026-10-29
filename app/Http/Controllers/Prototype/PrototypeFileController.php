<?php

namespace App\Http\Controllers\Prototype;

use App\Actions\Prototype\StorePrototypeFile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Prototype\StorePrototypeFileRequest;
use App\Models\PrototypeFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrototypeFileController extends Controller
{
    public function store(StorePrototypeFileRequest $request, StorePrototypeFile $store): JsonResponse
    {
        $file = $store($request->file('file'), $request->user());

        return response()->json($file->toClientArray(), 201);
    }

    /** Shows the file in the browser, or downloads it with ?download=1. */
    public function show(Request $request, PrototypeFile $file): StreamedResponse
    {
        $disk = Storage::disk(StorePrototypeFile::DISK);
        abort_unless($disk->exists($file->path), 404);

        return $request->boolean('download')
            ? $disk->download($file->path, $file->original_name)
            : $disk->response($file->path, $file->original_name, ['Content-Type' => $file->mime_type]);
    }
}
