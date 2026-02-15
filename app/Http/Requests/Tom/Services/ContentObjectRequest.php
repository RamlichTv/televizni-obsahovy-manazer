<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\ContentType;
use Illuminate\Foundation\Http\FormRequest;

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
                    $extension = $value->getClientOriginalExtension();
                    
                    $contentType = ContentType::find($typeId);
                    
                    if (!$contentType) return;

                    $allowed = $contentType->extension_whitelist ?? [];
                    
                    if (!in_array(strtolower($extension), $allowed)) {
                        $fail("The {$attribute} must be a file of type: " . implode(', ', $allowed));
                    }
                    
                    $maxSizeInKb = $contentType->max_size / 1024;
                    if ($value->getSize() / 1024 > $maxSizeInKb && $this->user()->cannot('bypass_max_size')) {
                         $fail("The file is too large. Max size allowed is " . round($maxSizeInKb / 1024, 2) . " MB.");
                    }
                },
            ],
        ];
    }
}
