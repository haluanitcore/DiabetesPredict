<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnalysisController;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/comparison', [AnalysisController::class, 'comparison'])->name('analysis.comparison');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // throttle:6,1 = maks 6 percobaan per menit per IP -> cegah brute-force akun.
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/analysis', [AnalysisController::class, 'showForm'])->name('analysis.form');
    Route::post('/analysis', [AnalysisController::class, 'process']);
    Route::get('/analysis/history', [AnalysisController::class, 'history'])->name('analysis.history');
    Route::get('/analysis/{id}', [AnalysisController::class, 'show'])->name('analysis.detail');
});

Route::get('/legal', [App\Http\Controllers\LegalController::class, 'index'])->name('legal');
