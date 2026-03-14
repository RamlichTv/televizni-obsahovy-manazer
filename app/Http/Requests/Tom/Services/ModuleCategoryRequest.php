<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\ModuleCategory;
use Illuminate\Validation\Rule;

/**
 * Validate create/update payloads for module category management.
 *
 * Notes:
 * - Category names must stay unique.
 * - Only administrators are allowed to set `is_system`.
 */
class ModuleCategoryRequest extends \Illuminate\Foundation\Http\FormRequest
{
    /**
     * Get the validation rules for the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;
        $isAdmin = (bool) ($this->user()?->isAdmin());
        $canManageEmbedded = (bool) ($this->user()?->can('manage_embedded_modules'));

        // `is_system` is intentionally write-protected for non-admin users.
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(ModuleCategory::class, 'name')->ignore($categoryId),
            ],
            'is_widget' => ['nullable', 'boolean'],
            'is_screensaver' => ['nullable', 'boolean'],
            'allow_embedded' => $canManageEmbedded ? ['nullable', 'boolean'] : ['prohibited'],
            'is_system' => $isAdmin ? ['nullable', 'boolean'] : ['prohibited'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'allow_embedded.prohibited' => 'Only users with embedded module permission can change embedded category access.',
        ];
    }
}
