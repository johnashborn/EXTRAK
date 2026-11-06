<?php

use App\Http\Controllers\Prototype\PrototypeFileController;
use App\Http\Controllers\Prototype\PrototypeStateController;
use Illuminate\Support\Facades\Route;

Route::view('/','landing');

// Front-end prototypes: shared data and uploaded files are kept in MySQL and on the server
Route::middleware('auth')->prefix('prototype')->name('prototype.')->group(function () {
    Route::view('/', 'prototype.index')->name('index');

    // For now the dashboards run without campus budget allocation: requests carry a proposed budget only.
    // The budget version (campus budgets, remaining amounts, over-budget warnings) is still in the views
    // and extrack.js; set 'noBudget' => false here to bring it back.
    $budgetAllocation = ['noBudget' => true];
    Route::view('/extension-director', 'prototype.extension-director', $budgetAllocation)->name('extension-director');
    Route::view('/campus-director', 'prototype.campus-director', $budgetAllocation)->name('campus-director');
    Route::view('/president', 'prototype.president', $budgetAllocation)->name('president');

    Route::get('/api/state', [PrototypeStateController::class, 'show'])->name('state.show');
    Route::put('/api/state', [PrototypeStateController::class, 'update'])->name('state.update');
    Route::post('/api/files', [PrototypeFileController::class, 'store'])->name('files.store');
    Route::get('/files/{file}', [PrototypeFileController::class, 'show'])->name('files.show');
});
