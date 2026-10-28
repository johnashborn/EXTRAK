<?php

use Illuminate\Support\Facades\Route;

Route::view('/','landing');

// Front-end prototypes (mock data, no database)
Route::middleware('auth')->prefix('prototype')->name('prototype.')->group(function () {
    Route::view('/', 'prototype.index')->name('index');
    Route::view('/extension-director', 'prototype.extension-director')->name('extension-director');
    Route::view('/campus-director', 'prototype.campus-director')->name('campus-director');
    Route::view('/president', 'prototype.president')->name('president');
});
