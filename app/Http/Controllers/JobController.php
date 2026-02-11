<?php

namespace App\Http\Controllers;

use App\Models\CareerJob;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function matchJobs(Request $request) {
        $user = $request->user();
        $skills = json_decode($user->skills, true);

        $jobs = CareerJob::all();
        $matches = [];

        foreach ($jobs as $job) {
            $jobSkills = json_decode($job->skills, true);
            $score = count(array_intersect($skills, $jobSkills)) * 5;
            if(str_contains(strtolower($job->title), strtolower($user->experience))) $score += 3;
            if(str_contains(strtolower($job->location), strtolower($user->location ?? ''))) $score += 2;
            if ($score > 0) {
                $matches[] = ['job' => $job, 'score' => $score];
            }
        }

        return response()->json(collect($matches)->sortByDesc('score')->values());
    }
}
