<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facd;

class ResumeController extends Controller
{
    public function generateResume(Request $request) {
        $user = $request->user();

        $prompt = "
        Create a professional resume:
        Name: {$user->name}
        Education: {$user->education}
        Experience: {$user->experience}
        Skills: ".implode(', ', json_decode($user->skills, true))."
        ";

        $response = OpenAI::responses()->create([
            'model' => 'gpt-4.1-mini',
            'input' => $prompt,
        ]);

        $resumeText = $response->output_text;

        $resume = $user->resumes()->create(['content' => $resumeText]);

        $pdf = Pdf::loadView('resume-template', ['text' => $resumeText]);
        $path = "resumes/{$user->id}.pdf";
        $pdf->save(public_path($path));

        $resume->update(['pdf_path' => $path]);

        return response()->json([
            'resume' => $resume,
            'pdf_url' => asset($path)
        ]);
    }
}
