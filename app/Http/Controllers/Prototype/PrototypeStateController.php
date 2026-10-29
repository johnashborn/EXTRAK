<?php

namespace App\Http\Controllers\Prototype;

use App\Actions\Prototype\SavePrototypeState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Prototype\UpdatePrototypeStateRequest;
use App\Models\PrototypeState;
use Illuminate\Http\JsonResponse;

class PrototypeStateController extends Controller
{
    public function show(): JsonResponse
    {
        $state = PrototypeState::current();

        return response()->json([
            'data' => $state?->data,
            'version' => $state?->version ?? 0,
        ]);
    }

    public function update(UpdatePrototypeStateRequest $request, SavePrototypeState $save): JsonResponse
    {
        // the whole document: validated() would keep only the keys that have rules (data.requests)
        $state = $save($request->input('data'), $request->user());

        return response()->json(['version' => $state->version]);
    }
}
