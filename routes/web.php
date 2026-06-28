<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active', 'permission:access-dashboard'])
    ->name('dashboard');

Route::middleware(['auth', 'active'])->prefix('companies')->name('companies.')->group(function () {
    Route::get('/', [CompanyController::class, 'index'])
        ->middleware('permission:companies.view')
        ->name('index');
    Route::get('/create', [CompanyController::class, 'create'])
        ->middleware('permission:companies.create')
        ->name('create');
    Route::post('/', [CompanyController::class, 'store'])
        ->middleware('permission:companies.create')
        ->name('store');
    Route::get('/{company}', [CompanyController::class, 'show'])
        ->middleware('permission:companies.view')
        ->name('show');
    Route::get('/{company}/edit', [CompanyController::class, 'edit'])
        ->middleware('permission:companies.update')
        ->name('edit');
    Route::put('/{company}', [CompanyController::class, 'update'])
        ->middleware('permission:companies.update')
        ->name('update');
    Route::delete('/{company}', [CompanyController::class, 'destroy'])
        ->middleware('permission:companies.delete')
        ->name('destroy');
});
