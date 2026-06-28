<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JobPostingController;
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

Route::middleware(['auth', 'active'])->prefix('departments')->name('departments.')->group(function () {
    Route::get('/', [DepartmentController::class, 'index'])
        ->middleware('permission:departments.view')
        ->name('index');
    Route::get('/create', [DepartmentController::class, 'create'])
        ->middleware('permission:departments.create')
        ->name('create');
    Route::post('/', [DepartmentController::class, 'store'])
        ->middleware('permission:departments.create')
        ->name('store');
    Route::get('/{department}', [DepartmentController::class, 'show'])
        ->middleware('permission:departments.view')
        ->name('show');
    Route::get('/{department}/edit', [DepartmentController::class, 'edit'])
        ->middleware('permission:departments.update')
        ->name('edit');
    Route::put('/{department}', [DepartmentController::class, 'update'])
        ->middleware('permission:departments.update')
        ->name('update');
    Route::delete('/{department}', [DepartmentController::class, 'destroy'])
        ->middleware('permission:departments.delete')
        ->name('destroy');
});

Route::middleware(['auth', 'active'])->prefix('job-postings')->name('job-postings.')->group(function () {
    Route::get('/', [JobPostingController::class, 'index'])
        ->middleware('permission:job-postings.view')
        ->name('index');
    Route::get('/create', [JobPostingController::class, 'create'])
        ->middleware('permission:job-postings.create')
        ->name('create');
    Route::post('/', [JobPostingController::class, 'store'])
        ->middleware('permission:job-postings.create')
        ->name('store');
    Route::get('/{job_posting}', [JobPostingController::class, 'show'])
        ->middleware('permission:job-postings.view')
        ->name('show');
    Route::get('/{job_posting}/edit', [JobPostingController::class, 'edit'])
        ->middleware('permission:job-postings.update')
        ->name('edit');
    Route::put('/{job_posting}', [JobPostingController::class, 'update'])
        ->middleware('permission:job-postings.update')
        ->name('update');
    Route::delete('/{job_posting}', [JobPostingController::class, 'destroy'])
        ->middleware('permission:job-postings.delete')
        ->name('destroy');
});
