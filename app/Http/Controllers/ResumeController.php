<?php

namespace App\Http\Controllers;

use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ResumeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $latestResume = $user?->resumes()->latest()->first();

        return Inertia::render('resume', [
            'latestResume' => $latestResume ? [
                'id' => $latestResume->id,
                'content' => $latestResume->content,
                'pdf_path' => $latestResume->pdf_path,
                'created_at' => $latestResume->created_at?->toDateTimeString(),
            ] : null,
            'generated' => $request->boolean('generated'),
        ]);
    }

    public function generateResume(Request $request)
    {
        $request->validate([
            'target_role' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $skills = $this->normalizeSkills($user?->skills);
        $targetRole = (string) $request->input('target_role', '');

        $prompt = "
        Create a professional resume:
        Name: {$user->name}
        Education: {$user->education}
        Experience: {$user->experience}
        Target Role: {$targetRole}
        Skills: ".implode(', ', $skills)."
        ";

        $resumeText = $this->generateWithOpenAi($prompt);
        if ($resumeText === null) {
            $resumeText = $this->generateFallbackResume($user->name, (string) $user->education, (string) $user->experience, $skills, $targetRole);
        }

        $resume = $user->resumes()->create(['content' => $resumeText]);
        $pdfPath = $this->storePdf($resumeText, (int) $user->id, (int) $resume->id);

        if ($pdfPath !== null) {
            $resume->update(['pdf_path' => $pdfPath]);
        }

        return redirect()->route('resume.index', ['generated' => 1]);
    }

    public function download(Resume $resume): Response
    {
        abort_unless($resume->user_id === Auth::id(), 403);

        if (! $resume->pdf_path || ! Storage::disk('public')->exists($resume->pdf_path)) {
            $pdfPath = $this->storePdf($resume->content, (int) $resume->user_id, (int) $resume->id);
            if ($pdfPath !== null) {
                $resume->update(['pdf_path' => $pdfPath]);
            }
        }

        if ($resume->pdf_path && Storage::disk('public')->exists($resume->pdf_path)) {
            return Storage::disk('public')->download($resume->pdf_path, "resume-{$resume->id}.pdf");
        }

        return response($resume->content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => "attachment; filename=resume-{$resume->id}.txt",
        ]);
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

    private function generateWithOpenAi(string $prompt): ?string
    {
        $openAiFacade = 'OpenAI\\Laravel\\Facades\\OpenAI';
        if (! class_exists($openAiFacade)) {
            return null;
        }

        try {
            $response = $openAiFacade::responses()->create([
                'model' => 'gpt-4.1-mini',
                'input' => $prompt,
            ]);
        } catch (\Throwable) {
            return null;
        }

        $output = trim((string) ($response->output_text ?? ''));

        return $output !== '' ? $output : null;
    }

    /**
     * @param  list<string>  $skills
     */
    private function generateFallbackResume(string $name, string $education, string $experience, array $skills, string $targetRole): string
    {
        $skillLine = count($skills) > 0 ? implode(', ', $skills) : 'Add your key skills in profile.';
        $roleLine = $targetRole !== '' ? $targetRole : 'Professional Role';

        return implode("\n\n", [
            strtoupper($name),
            "Target Position: {$roleLine}",
            "Professional Summary:\nResults-driven professional with practical experience and a focus on measurable outcomes.",
            "Education:\n{$education}",
            "Experience:\n{$experience}",
            "Core Skills:\n{$skillLine}",
        ]);
    }

    private function storePdf(string $resumeText, int $userId, int $resumeId): ?string
    {
        $pdfFacade = 'Barryvdh\\DomPDF\\Facade\\Pdf';
        if (! class_exists($pdfFacade)) {
            return null;
        }

        $path = "resumes/user-{$userId}-resume-{$resumeId}.pdf";

        try {
            $pdf = $pdfFacade::loadView('resume-template', ['text' => $resumeText]);
            Storage::disk('public')->put($path, $pdf->output());
        } catch (\Throwable) {
            return null;
        }

        return $path;
    }
}
