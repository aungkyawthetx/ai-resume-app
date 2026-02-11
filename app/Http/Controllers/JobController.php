<?php

namespace App\Http\Controllers;

use App\Models\CareerJob;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $skills = $this->normalizeSkills($user?->skills);
        $experience = (string) ($user->experience ?? '');
        $jobs = CareerJob::query()->latest()->get();
        $matches = $this->buildMatches($jobs->all(), $skills, $experience);

        return Inertia::render('jobs', [
            'jobs' => $jobs->map(fn (CareerJob $job) => $this->serializeJob($job))->values(),
            'matches' => $matches,
            'userSkills' => $skills,
        ]);
    }

    public function matchJobs(Request $request)
    {
        $user = $request->user();
        $skills = $this->normalizeSkills($user?->skills);
        $experience = (string) ($user->experience ?? '');
        $jobs = CareerJob::query()->latest()->get();
        $matches = $this->buildMatches($jobs->all(), $skills, $experience);

        return response()->json($matches);
    }

    /**
     * @param  mixed  $skills
     * @return list<string>
     */
    private function normalizeSkills($skills): array
    {
        if (is_array($skills)) {
            return array_values(array_filter(array_map('strval', $skills)));
        }

        if (is_string($skills) && $skills !== '') {
            $decoded = json_decode($skills, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('strval', $decoded)));
            }
        }

        return [];
    }

    private function serializeJob(CareerJob $job): array
    {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'company' => $job->company,
            'description' => $job->description,
            'location' => $job->location,
            'skills' => is_array($job->skills) ? $job->skills : [],
            'salary' => $job->salary,
        ];
    }

    /**
     * @param  list<CareerJob>  $jobs
     * @param  list<string>  $skills
     */
    private function buildMatches(array $jobs, array $skills, string $experience): array
    {
        $normalizedUserSkills = array_map(fn (string $skill): string => strtolower(trim($skill)), $skills);
        $matches = [];

        foreach ($jobs as $job) {
            $jobSkills = is_array($job->skills) ? $job->skills : [];
            $normalizedJobSkills = array_map(fn (string $skill): string => strtolower(trim($skill)), $jobSkills);
            $intersections = array_values(array_intersect($normalizedUserSkills, $normalizedJobSkills));
            $matchedSkillsCount = count($intersections);
            $score = $matchedSkillsCount * 5;
            $score += $matchedSkillsCount > 0 ? 10 : 0;
            $score += $this->contains((string) $job->title, $experience) ? 3 : 0;
            $score += $this->contains((string) $job->description, $experience) ? 2 : 0;

            if ($score > 0) {
                $matches[] = [
                    'job' => $this->serializeJob($job),
                    'score' => $score,
                    'matched_skills_count' => $matchedSkillsCount,
                ];
            }
        }

        return collect($matches)->sortByDesc('score')->values()->all();
    }

    private function contains(string $haystack, string $needle): bool
    {
        $trimmedNeedle = trim($needle);
        if ($trimmedNeedle === '') {
            return false;
        }

        return str_contains(strtolower($haystack), strtolower($trimmedNeedle));
    }
}
