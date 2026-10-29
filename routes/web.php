<?php

use App\Http\Controllers\Prototype\PrototypeFileController;
use App\Http\Controllers\Prototype\PrototypeStateController;
use Illuminate\Support\Facades\Route;

Route::view('/','landing');

// Front-end prototypes: shared data and uploaded files are kept in MySQL and on the server
Route::middleware('auth')->prefix('prototype')->name('prototype.')->group(function () {
    Route::view('/', 'prototype.index')->name('index');
    Route::view('/extension-director', 'prototype.extension-director')->name('extension-director');
    Route::view('/campus-director', 'prototype.campus-director')->name('campus-director');
    Route::view('/president', 'prototype.president')->name('president');

    Route::get('/api/state', [PrototypeStateController::class, 'show'])->name('state.show');
    Route::put('/api/state', [PrototypeStateController::class, 'update'])->name('state.update');
    Route::post('/api/files', [PrototypeFileController::class, 'store'])->name('files.store');
    Route::get('/files/{file}', [PrototypeFileController::class, 'show'])->name('files.show');
});
