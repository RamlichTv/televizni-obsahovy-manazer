<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ContentObjectRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_content');
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $contentType = ContentType::find($this->input('type_id'));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'type_id' => ['required', 'exists:content_types,id'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'integer', 'min:0', 'max:20'],
            'expires_at' => ['nullable', 'date', 'after:date'],
            'published_at' => ['nullable', 'date', 'after_or_equal:date'],
            'tags' => ['array'],
            'tags.*' => ['exists:tags,id'],
        ];

        if (!$contentType?->is_file) {
            $rules['file_upload'] = ['nullable'];
            return $rules;
        }

        if ($contentType->multiple_file) {
            $rules['file_upload'] = ['nullable', 'array'];
            $rules['file_upload.*'] = ['file', $this->buildFileValidationRule($contentType)];
            return $rules;
        }

        $rules['file_upload'] = ['nullable', 'file', $this->buildFileValidationRule($contentType)];

        return $rules;
    }

    /**
     * Build one reusable validator for a single uploaded file.
     *
     * @param ContentType $contentType
     * @return \Closure
     */
    private function buildFileValidationRule(ContentType $contentType): \Closure
    {
        return function ($attribute, $value, $fail) use ($contentType): void {
            if (!$value instanceof UploadedFile) {
                $fail("The {$attribute} must be a valid uploaded file.");
                return;
            }

            $allowed = collect((array) ($contentType->extension_whitelist ?? []))
                ->map(fn ($rule) => strtolower(trim((string) $rule)))
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
        };
    }

    /**
     * File matches any rule.
     *
     * @param UploadedFile $file
     * @param array $rules
     * @return bool
     */
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

    /**
     * Mime matches rule.
     *
     * @param string $mime
     * @param string $rule
     * @return bool
     */
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
