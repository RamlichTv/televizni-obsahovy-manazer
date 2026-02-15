<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ContentObjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_content');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type_id' => ['required', 'exists:content_types,id'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'integer', 'min:0', 'max:20'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'tags' => ['array'],
            'tags.*' => ['exists:tags,id'],
            
            'file_upload' => [
                'nullable',
                'file',
                function ($attribute, $value, $fail) {
                    $typeId = $this->input('type_id');
                    $contentType = ContentType::find($typeId);

                    if (!$contentType || !$contentType->is_file) {
                        return;
                    }

                    $allowed = collect((array) ($contentType->extension_whitelist ?? []))
                        ->map(fn($rule) => strtolower(trim((string) $rule)))
                        ->filter()
                        ->values()
                        ->all();

                    if (!empty($allowed) && !$this->fileMatchesAnyRule($value, $allowed)) {
                        $fail("The {$attribute} must match one of: " . implode(', ', $allowed));
                    }

                    $maxSizeBytes = (int) ($contentType->max_size ?? 0);
                    if ($maxSizeBytes > 0 && $value->getSize() > $maxSizeBytes && $this->user()->cannot('bypass_max_size')) {
                        $fail("The file is too large. Max size allowed is " . round($maxSizeBytes / 1024 / 1024, 2) . " MB.");
                    }
                },
            ],
        ];
    }

    private function fileMatchesAnyRule(UploadedFile $file, array $rules): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        $clientExtension = strtolower((string) $file->getClientOriginalExtension());
        $guessedExtension = strtolower((string) $file->extension());

        foreach ($rules as $rule) {
            if (str_contains($rule, '/')) {
                if ($this->mimeMatchesRule($mime, $rule)) {
                    return true;
                }

                continue;
            }

            $normalizedExtension = ltrim($rule, '.');
            if ($normalizedExtension !== '' &&
                ($normalizedExtension === $clientExtension || $normalizedExtension === $guessedExtension)) {
                return true;
            }
        }

        return false;
    }

    private function mimeMatchesRule(string $mime, string $rule): bool
    {
        if ($mime === '') {
            return false;
        }

        if (str_ends_with($rule, '/*')) {
            return str_starts_with($mime, rtrim($rule, '*'));
        }

        return $mime === $rule;
    }
}
