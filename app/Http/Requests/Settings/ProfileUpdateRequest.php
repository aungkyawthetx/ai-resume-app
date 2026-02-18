<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $skills = $this->input('skills');

        if (is_string($skills)) {
            $normalizedSkills = array_values(array_unique(array_filter(array_map(
                fn (string $skill): string => trim($skill),
                explode(',', $skills)
            ))));

            $this->merge(['skills' => $normalizedSkills]);
        } elseif (! is_array($skills)) {
            $this->merge(['skills' => []]);
        }

        $this->merge([
            'education' => trim((string) $this->input('education', '')),
            'experience' => trim((string) $this->input('experience', '')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'education' => ['nullable', 'string', 'max:500'],
            'experience' => ['nullable', 'string', 'max:5000'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:80'],
        ];
    }
}
