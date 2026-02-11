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
});

Route::post('/resume/generate', [ResumeController::class, 'generateResume']);
Route::get('/jobs/match', [JobController::class, 'matchJobs']);


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
