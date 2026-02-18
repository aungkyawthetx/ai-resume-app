<?php

use App\Http\Controllers\JobController;
use App\Http\Controllers\ResumeController;
use App\Models\CareerJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function (Request $request) {
        $user = $request->user();
        $latestResume = $user->resumes()->latest()->first();

        $skills = [];
        if (is_array($user->skills)) {
            $skills = array_values(array_filter(array_map('strval', $user->skills)));
        } elseif (is_string($user->skills) && $user->skills !== '') {
            $decoded = json_decode($user->skills, true);
            if (is_array($decoded)) {
                $skills = array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        $normalizedUserSkills = array_values(array_filter(array_map(
            fn (string $skill): string => strtolower(trim($skill)),
            $skills
        )));

        $jobs = CareerJob::query()
            ->latest()
            ->get(['id', 'title', 'company', 'location', 'skills', 'created_at']);

        $matchedJobs = $jobs
            ->map(function (CareerJob $job) use ($normalizedUserSkills): array {
                $jobSkills = is_array($job->skills) ? $job->skills : [];
                $normalizedJobSkills = array_values(array_filter(array_map(
                    fn (string $skill): string => strtolower(trim($skill)),
                    $jobSkills
                )));
                $matchedCount = count(array_intersect($normalizedUserSkills, $normalizedJobSkills));

                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company' => $job->company,
                    'location' => $job->location,
                    'matched_skills_count' => $matchedCount,
                    'created_at' => $job->created_at?->toDateTimeString(),
                ];
            })
            ->filter(fn (array $job): bool => $job['matched_skills_count'] > 0)
            ->sortByDesc('matched_skills_count')
            ->values();

        $hasEducation = trim((string) ($user->education ?? '')) !== '';
        $hasExperience = trim((string) ($user->experience ?? '')) !== '';
        $hasSkills = count($skills) > 0;
        $profileItemsCompleted = count(array_filter([$hasEducation, $hasExperience, $hasSkills]));

        return Inertia::render('dashboard', [
            'stats' => [
                'resumes_count' => $user->resumes()->count(),
                'jobs_count' => $jobs->count(),
                'skills_count' => count($skills),
                'matched_jobs_count' => $matchedJobs->count(),
                'profile_completion_percent' => (int) round(($profileItemsCompleted / 3) * 100),
            ],
            'latestResume' => $latestResume ? [
                'id' => $latestResume->id,
                'created_at' => $latestResume->created_at?->toDateTimeString(),
                'pdf_path' => $latestResume->pdf_path,
            ] : null,
            'profileChecklist' => [
                'education' => $hasEducation,
                'experience' => $hasExperience,
                'skills' => $hasSkills,
            ],
            'topMatches' => $matchedJobs->take(5)->all(),
        ]);
    })->name('dashboard');

    Route::get('/resume', [ResumeController::class, 'index'])->name('resume.index');
    Route::post('/resume/generate', [ResumeController::class, 'generateResume'])->name('resume.generate');
    Route::get('/resume/{resume}/download', [ResumeController::class, 'download'])->name('resume.download');

    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/match', [JobController::class, 'matchJobs'])->name('jobs.match');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
