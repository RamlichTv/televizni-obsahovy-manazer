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

        // `is_system` is intentionally write-protected for non-admin users.
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(ModuleCategory::class, 'name')->ignore($categoryId),
            ],
            'is_widget' => ['nullable', 'boolean'],
            'is_system' => $isAdmin ? ['nullable', 'boolean'] : ['prohibited'],
        ];
    }
}

