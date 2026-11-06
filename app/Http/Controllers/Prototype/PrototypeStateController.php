<?php

namespace App\Http\Controllers\Prototype;

use App\Http\Controllers\Controller;
use App\Http\Requests\Prototype\UpdatePrototypeStateRequest;
use App\Services\Prototype\DashboardDataStore;
use Illuminate\Http\JsonResponse;

class PrototypeStateController extends Controller
{
    public function show(DashboardDataStore $store): JsonResponse
    {
        return response()->json([
            'data' => $store->export(),
            'version' => $store->version(),
        ]);
    }

    public function update(UpdatePrototypeStateRequest $request, DashboardDataStore $store): JsonResponse
    {
        // the whole document: validated() would keep only the keys that have rules
        $version = $store->import($request->input('data'), $request->user());

        return response()->json(['version' => $version]);
    }
}
