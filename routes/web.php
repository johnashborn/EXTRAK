<?php

use App\Http\Controllers\Auth\InviteController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
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
});
