<?php

use App\Http\Controllers\Auth\InviteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/login/demo/{user}', [LoginController::class, 'demo'])->name('login.demo');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware('signed')->group(function () {
    Route::get('/invites/{user}', [InviteController::class, 'show'])->name('invites.show');
    Route::post('/invites/{user}', [InviteController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)->except(['show', 'destroy']);
    Route::patch('/users/{user}/active', [UserController::class, 'setActive'])->name('users.active');

    // {service} resolves through the draft scope: another campus's draft is a 404.
    Route::get('/services/create', [ServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
    Route::post('/services/{service}/publish', [ServiceController::class, 'publish'])->name('services.publish');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.discard');

    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
});
