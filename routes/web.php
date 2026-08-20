<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UploadDataController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SpkOeeController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'login'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');

Route::group(['middleware' => 'auth'], function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    Route::get('/', fn () => redirect()->route('dashboard.index'));
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/recommendation/{id}', [DashboardController::class, 'getRecommendation'])->name('dashboard.recommendation');
    
    Route::get('/upload-data', [UploadDataController::class, 'index'])->name('upload.index');
    Route::post('/upload-data', [UploadDataController::class, 'store'])->name('upload.store');
    
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
});
