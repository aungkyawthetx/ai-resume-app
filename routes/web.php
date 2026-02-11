<?php

use App\Http\Controllers\JobController;
use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/resume', [ResumeController::class, 'index'])->name('resume.index');
    Route::post('/resume/generate', [ResumeController::class, 'generateResume'])->name('resume.generate');
    Route::get('/resume/{resume}/download', [ResumeController::class, 'download'])->name('resume.download');

    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/match', [JobController::class, 'matchJobs'])->name('jobs.match');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
