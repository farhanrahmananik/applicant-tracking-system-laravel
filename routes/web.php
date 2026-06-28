<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateResumeController;
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

Route::middleware(['auth', 'active'])->prefix('candidates')->name('candidates.')->group(function () {
    Route::get('/', [CandidateController::class, 'index'])
        ->middleware('permission:candidates.view')
        ->name('index');
    Route::get('/create', [CandidateController::class, 'create'])
        ->middleware('permission:candidates.create')
        ->name('create');
    Route::post('/', [CandidateController::class, 'store'])
        ->middleware('permission:candidates.create')
        ->name('store');
    Route::get('/{candidate}', [CandidateController::class, 'show'])
        ->middleware('permission:candidates.view')
        ->name('show');
    Route::get('/{candidate}/edit', [CandidateController::class, 'edit'])
        ->middleware('permission:candidates.edit')
        ->name('edit');
    Route::put('/{candidate}', [CandidateController::class, 'update'])
        ->middleware('permission:candidates.edit')
        ->name('update');
    Route::delete('/{candidate}', [CandidateController::class, 'destroy'])
        ->middleware('permission:candidates.delete')
        ->name('destroy');
});

Route::middleware(['auth', 'active'])
    ->scopeBindings()
    ->prefix('candidates/{candidate}/resumes')
    ->name('candidates.resumes.')
    ->group(function () {
        Route::post('/', [CandidateResumeController::class, 'store'])
            ->middleware('permission:candidate-resumes.upload')
            ->name('store');
        Route::get('/{resume}/download', [CandidateResumeController::class, 'download'])
            ->middleware('permission:candidate-resumes.download')
            ->name('download');
        Route::delete('/{resume}', [CandidateResumeController::class, 'destroy'])
            ->middleware('permission:candidate-resumes.delete')
            ->name('destroy');
    });

Route::middleware(['auth', 'active'])->prefix('applications')->name('applications.')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])
        ->middleware('permission:applications.view')
        ->name('index');
    Route::get('/create', [ApplicationController::class, 'create'])
        ->middleware('permission:applications.create')
        ->name('create');
    Route::post('/', [ApplicationController::class, 'store'])
        ->middleware('permission:applications.create')
        ->name('store');
    Route::get('/{application}', [ApplicationController::class, 'show'])
        ->middleware('permission:applications.view')
        ->name('show');
    Route::get('/{application}/edit', [ApplicationController::class, 'edit'])
        ->middleware('permission:applications.update')
        ->name('edit');
    Route::put('/{application}', [ApplicationController::class, 'update'])
        ->middleware('permission:applications.update')
        ->name('update');
    Route::delete('/{application}', [ApplicationController::class, 'destroy'])
        ->middleware('permission:applications.delete')
        ->name('destroy');
});
