<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\ModuleCategory;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

/**
 * Validate module create/update payloads from the module editor form.
 *
 * The request accepts both:
 * - Structured arrays posted directly by the UI.
 * - JSON strings (for compatibility with plain textarea submissions).
 */
class ModuleRequest extends \Illuminate\Foundation\Http\FormRequest
{
    /**
     * JSON parse errors collected during input normalization.
     *
     * @var array<string, string>
     */
    private array $invalidJsonFields = [];

    /**
     * Determine whether the request is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $allowScript = (bool) ($this->user()?->can('manage_module_javascript'));

        // JavaScript editing is permission-gated separately from admin-only sanitizer bypass.
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'integer', Rule::exists(ModuleCategory::class, 'id')],
            'is_active' => ['nullable', 'boolean'],
            'template_html' => ['required', 'string'],
            'template_css' => ['nullable', 'string'],
            'template_js' => $allowScript ? ['nullable', 'string'] : ['prohibited'],
            'module_settings_mode' => ['nullable', 'string', Rule::in(['builder', 'raw'])],

            'settings_title' => ['nullable', 'string', 'max:255'],
            'settings_provider' => ['nullable', 'string', Rule::in(array_keys((array) config('modules.providers', [])))],
            'settings_type_name' => ['nullable', 'string', 'max:255'],
            'settings_status_name' => ['nullable', 'string', 'max:255'],
            'settings_type_names' => ['nullable', 'array'],
            'settings_type_names.*' => ['nullable', 'string', 'max:255'],
            'settings_status_names' => ['nullable', 'array'],
            'settings_status_names.*' => ['nullable', 'string', 'max:255'],
            'settings_tag_ids' => ['nullable', 'array'],
            'settings_tag_ids.*' => ['nullable', 'integer', 'min:1'],
            'settings_order_by' => ['nullable', 'string', Rule::in(['priority', 'published_at', 'created_at', 'name'])],
            'settings_order_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'settings_limit' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('modules.max_items_per_module', 30)],

            'data_schema' => ['nullable', 'array'],
            'module_data' => ['nullable', 'array'],
            'data_source' => ['nullable', 'array'],
            'data_source.provider' => ['nullable', 'string', Rule::in(array_keys((array) config('modules.providers', [])))],
            'data_source.filters' => ['nullable', 'array'],
            'data_source.filters.type_name' => ['nullable', 'string', 'max:255'],
            'data_source.filters.status_name' => ['nullable', 'string', 'max:255'],
            'data_source.filters.tag_name' => ['nullable', 'string', 'max:255'],
            'data_source.filters.type_names' => ['nullable', 'array'],
            'data_source.filters.type_names.*' => ['nullable', 'string', 'max:255'],
            'data_source.filters.status_names' => ['nullable', 'array'],
            'data_source.filters.status_names.*' => ['nullable', 'string', 'max:255'],
            'data_source.filters.tag_names' => ['nullable', 'array'],
            'data_source.filters.tag_names.*' => ['nullable', 'string', 'max:255'],
            'data_source.filters.tag_ids' => ['nullable', 'array'],
            'data_source.filters.tag_ids.*' => ['nullable', 'integer', 'min:1'],
            'data_source.filters.type_ids' => ['nullable', 'array'],
            'data_source.filters.type_ids.*' => ['nullable', 'integer', 'min:1'],
            'data_source.filters.status_ids' => ['nullable', 'array'],
            'data_source.filters.status_ids.*' => ['nullable', 'integer', 'min:1'],
            'data_source.order_by' => ['nullable', 'string', Rule::in(['priority', 'published_at', 'created_at', 'name'])],
            'data_source.order_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'data_source.limit' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('modules.max_items_per_module', 30)],
        ];
    }

    /**
     * Messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'data_schema.array' => 'Data schema must be valid JSON object.',
            'module_data.array' => 'Module data must be valid JSON object.',
            'data_source.array' => 'Data source must be valid JSON object.',
            'template_js.prohibited' => 'Only users with module JavaScript permission can edit module JavaScript.',
        ];
    }

    /**
     * Normalize array field.
     *
     * @param string $field
     * @return array
     */
    private function normalizeArrayField(string $field): array
    {
        $value = $this->input($field);

        // Already normalized by frontend helpers.
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return [];
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $this->invalidJsonFields[$field] = match ($field) {
                'data_schema' => 'Data schema must be valid JSON object.',
                'module_data' => 'Module data must be valid JSON object.',
                'data_source' => 'Data source must be valid JSON object.',
                default => 'Invalid JSON payload.',
            };

            return [];
        }

        return $decoded;
    }

    /**
     * Prepare payload before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Force consistent types before rules are evaluated.
        $this->merge([
            'data_schema' => $this->normalizeArrayField('data_schema'),
            'module_data' => $this->normalizeArrayField('module_data'),
            'data_source' => $this->normalizeArrayField('data_source'),
            'is_active' => $this->boolean('is_active'),
            'settings_limit' => $this->filled('settings_limit') ? (int) $this->input('settings_limit') : null,
        ]);
    }

    /**
     * Add explicit JSON validation errors after normalization.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->invalidJsonFields as $field => $message) {
                $validator->errors()->add($field, $message);
            }

            $this->validateEmbeddedCategoryPermission($validator);
        });
    }

    /**
     * Validate whether the selected category requires embedded-module permission.
     *
     * Live UI may hide restricted categories, but backend validation remains the
     * authoritative guard for crafted requests and stale editor state.
     *
     * @param Validator $validator
     * @return void
     */
    private function validateEmbeddedCategoryPermission(Validator $validator): void
    {
        if ($validator->errors()->has('category')) {
            return;
        }

        $categoryId = (int) $this->input('category');
        if ($categoryId <= 0) {
            return;
        }

        $category = ModuleCategory::query()->find($categoryId, ['id', 'allow_embedded']);
        if (!$category?->allow_embedded) {
            return;
        }

        if ($this->user()?->can('manage_embedded_modules')) {
            return;
        }

        $validator->errors()->add(
            'category',
            'Only users with embedded module permission can use categories that allow embedded content.',
        );
    }
}
