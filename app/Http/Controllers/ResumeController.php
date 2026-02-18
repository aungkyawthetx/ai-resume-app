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
        $education = trim((string) ($user->education ?? ''));
        $experience = trim((string) ($user->experience ?? ''));
        $skillText = count($skills) > 0 ? implode(', ', $skills) : 'No skills provided.';

        $prompt = "
        Create a professional resume:
        Name: {$user->name}
        Education: ".($education !== '' ? $education : 'No education provided')."
        Experience: ".($experience !== '' ? $experience : 'No experience provided')."
        Target Role: {$targetRole}
        Skills: {$skillText}
        ";

        $resumeText = $this->generateWithOpenAi($prompt);
        if ($resumeText === null) {
            $resumeText = $this->generateFallbackResume($user->name, $education, $experience, $skills, $targetRole);
        }

        $resume = $user->resumes()->create(['content' => $resumeText]);
        $pdfPath = $this->storePdf($resumeText, (int) $user->id, (int) $resume->id);

        if ($pdfPath !== null) {
            $resume->update(['pdf_path' => $pdfPath]);
        }

        return redirect()->route('resume.index', ['generated' => 1]);
    }

    public function download(Resume $resume)
    {
        abort_unless($resume->user_id === Auth::id(), 403);

        $pdfPath = $this->storePdf($resume->content, (int) $resume->user_id, (int) $resume->id);
        if ($pdfPath !== null) {
            $resume->update(['pdf_path' => $pdfPath]);
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
            return $this->cleanSkillList($skills);
        }

        if (is_string($skills) && $skills !== '') {
            $decoded = json_decode($skills, true);
            if (is_array($decoded)) {
                return $this->cleanSkillList($decoded);
            }
        }

        return [];
    }

    /**
     * @param  array<int, mixed>  $skills
     * @return list<string>
     */
    private function cleanSkillList(array $skills): array
    {
        $normalized = array_map(
            fn ($skill): string => trim((string) $skill),
            $skills
        );

        return array_values(array_unique(array_filter(
            $normalized,
            fn (string $skill): bool => $skill !== ''
        )));
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
        $path = "resumes/user-{$userId}-resume-{$resumeId}.pdf";
        $pdfContent = null;
        $resume = $this->extractResumeSections($resumeText);

        if (class_exists($pdfFacade)) {
            try {
                $pdf = $pdfFacade::loadView('resume-template', [
                    'text' => $resumeText,
                    'resume' => $resume,
                ]);
                $pdfContent = (string) $pdf->output();
            } catch (\Throwable) {
                $pdfContent = null;
            }
        }

        if ($pdfContent === null || $pdfContent === '') {
            $pdfContent = $this->buildSimplePdf($resume);
        }

        if ($pdfContent === '') {
            return null;
        }

        try {
            Storage::disk('public')->put($path, $pdfContent);
        } catch (\Throwable) {
            return null;
        }

        return $path;
    }

    /**
     * @return array{
     *   name: string,
     *   target_role: string,
     *   summary: string,
     *   experience: list<string>,
     *   education: list<string>,
     *   skills: list<string>
     * }
     */
    private function extractResumeSections(string $text): array
    {
        $normalizedText = trim((string) $text);
        $lines = preg_split('/\R/u', $normalizedText) ?: [];
        $nonEmptyLines = array_values(array_filter(array_map(
            fn (string $line): string => trim($line),
            $lines
        )));

        $name = $nonEmptyLines[0] ?? 'Professional Candidate';
        $targetRole = $this->readSection($normalizedText, ['Target Position', 'Target Role']);
        $summary = $this->readSection($normalizedText, ['Professional Summary', 'Summary', 'Profile']);
        $experience = $this->readSection($normalizedText, ['Experience', 'Work Experience']);
        $education = $this->readSection($normalizedText, ['Education']);
        $skills = $this->readSection($normalizedText, ['Core Skills', 'Skills', 'Technical Skills']);

        if ($summary === '' && count($nonEmptyLines) > 1) {
            $summary = $nonEmptyLines[1];
        }

        return [
            'name' => trim($name) !== '' ? trim($name) : 'Professional Candidate',
            'target_role' => $targetRole,
            'summary' => $summary,
            'experience' => $this->toBulletList($experience),
            'education' => $this->toBulletList($education),
            'skills' => $this->toSkillList($skills),
        ];
    }

    private function readSection(string $text, array $labels): string
    {
        $escapedLabels = array_map(
            fn (string $label): string => preg_quote($label, '/'),
            $labels
        );
        $labelRegex = implode('|', $escapedLabels);

        $pattern = '/(?:^|\R)\s*(?:'.$labelRegex.')\s*:\s*(.*?)(?=(?:\R\s*[A-Za-z][A-Za-z ]{1,40}\s*:\s*)|\z)/is';
        if (! preg_match($pattern, $text, $matches)) {
            return '';
        }

        return trim((string) ($matches[1] ?? ''));
    }

    /**
     * @return list<string>
     */
    private function toBulletList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $items = preg_split('/\R+/u', $value) ?: [];
        $clean = array_values(array_filter(array_map(
            fn (string $item): string => trim(ltrim($item, "-* \t")),
            $items
        )));

        if (count($clean) > 0) {
            return $clean;
        }

        return [trim($value)];
    }

    /**
     * @return list<string>
     */
    private function toSkillList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $parts = preg_split('/(?:,|\R|\|)+/u', $value) ?: [];
        $skills = array_values(array_unique(array_filter(array_map(
            fn (string $item): string => trim(ltrim($item, "-* \t")),
            $parts
        ))));

        return $skills;
    }

    /**
     * @param  array{
     *   name: string,
     *   target_role: string,
     *   summary: string,
     *   experience: list<string>,
     *   education: list<string>,
     *   skills: list<string>
     * }  $resume
     */
    private function buildSimplePdf(array $resume): string
    {
        $streamRows = [];
        $y = 770;

        $streamRows[] = '0.10 0.18 0.32 rg';
        $streamRows[] = '36 742 540 54 re f';

        $y = $this->addPdfLine($streamRows, $this->toPdfString($resume['name']), 50, $y, 20, '1 1 1');
        if ($resume['target_role'] !== '') {
            $y = $this->addPdfLine($streamRows, $this->toPdfString($resume['target_role']), 50, $y - 2, 11, '0.88 0.93 1');
        }

        $y -= 8;
        $y = $this->addSection($streamRows, $y, 'Professional Summary', $resume['summary'] !== '' ? [$resume['summary']] : []);
        $y = $this->addSection($streamRows, $y, 'Core Skills', $resume['skills']);
        $y = $this->addSection($streamRows, $y, 'Experience', $resume['experience']);
        $y = $this->addSection($streamRows, $y, 'Education', $resume['education']);

        if ($y < 120) {
            $this->addPdfLine($streamRows, $this->toPdfString('Additional content omitted for single-page fallback PDF.'), 42, 50, 9, '0.45 0.45 0.45');
        }

        $stream = implode("\n", $streamRows)."\n";

        return $this->renderPdfDocument($stream);
    }

    /**
     * @param  list<string>  $commands
     */
    private function addSection(array &$commands, int $startY, string $title, array $items): int
    {
        $y = $startY;
        $y = $this->addPdfLine($commands, $this->toPdfString(strtoupper($title)), 42, $y, 10, '0.10 0.18 0.32');
        $y -= 2;

        if (count($items) === 0) {
            return $this->addPdfLine($commands, $this->toPdfString('Not provided.'), 52, $y, 10, '0.35 0.35 0.35') - 10;
        }

        foreach ($items as $item) {
            $wrapped = $this->wrapText($item, 88);
            foreach ($wrapped as $index => $line) {
                $prefix = $index === 0 ? '- ' : '  ';
                $y = $this->addPdfLine($commands, $this->toPdfString($prefix.$line), 52, $y, 10, '0.12 0.12 0.12');
                if ($y < 80) {
                    return $y;
                }
            }
        }

        return $y - 8;
    }

    /**
     * @param  list<string>  $commands
     */
    private function addPdfLine(array &$commands, string $text, int $x, int $y, int $fontSize, string $rgb): int
    {
        $commands[] = "BT {$rgb} rg /F1 {$fontSize} Tf {$x} {$y} Td ({$text}) Tj ET";

        return $y - ($fontSize + 5);
    }

    /**
     * @return list<string>
     */
    private function wrapText(string $text, int $width): array
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($normalized === '') {
            return [];
        }

        $wrapped = wordwrap($normalized, $width, "\n", true);
        $parts = preg_split('/\R/u', $wrapped) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    private function toPdfString(string $text): string
    {
        $latinText = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
        $safe = $latinText !== false ? $latinText : $text;
        $safe = str_replace(["\t", "\r"], ['    ', ''], $safe);

        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $safe);
    }

    private function renderPdfDocument(string $stream): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length ".strlen($stream)." >>\nstream\n{$stream}endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $objectNumber = $index + 1;
            $offsets[$objectNumber] = strlen($pdf);
            $pdf .= "{$objectNumber} 0 obj\n{$object}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (range(1, count($objects)) as $number) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
