<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\Module;
use App\Models\ModuleCategory;
use Illuminate\Validation\Rule;

/**
 * Validate payloads used for draft module preview rendering.
 *
 * This request mirrors most of `ModuleRequest` but focuses on preview-only
 * flows where a module may or may not already exist in the database.
 */
class ModulePreviewRequest extends \Illuminate\Foundation\Http\FormRequest
{
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
        $allowScript = (bool) ($this->user()?->isAdmin());

        // JavaScript preview is intentionally restricted to administrators.
        return [
            'module_id' => ['nullable', 'uuid', Rule::exists(Module::class, 'id')],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'integer', Rule::exists(ModuleCategory::class, 'id')],
            'template_html' => ['required', 'string'],
            'template_css' => ['nullable', 'string'],
            'template_js' => $allowScript ? ['nullable', 'string'] : ['prohibited'],

            'settings_title' => ['nullable', 'string', 'max:255'],
            'settings_provider' => ['nullable', 'string', Rule::in(array_keys((array) config('modules.providers', [])))],
            'settings_type_name' => ['nullable', 'string', 'max:255'],
            'settings_status_name' => ['nullable', 'string', 'max:255'],
            'settings_order_by' => ['nullable', 'string', Rule::in(['priority', 'published_at', 'created_at', 'name'])],
            'settings_order_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'settings_limit' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('modules.max_items_per_module', 30)],

            'config_schema' => ['nullable', 'array'],
            'default_config' => ['nullable', 'array'],
            'data_source' => ['nullable', 'array'],
            'data_source.provider' => ['nullable', 'string', Rule::in(array_keys((array) config('modules.providers', [])))],
            'data_source.filters' => ['nullable', 'array'],
            'data_source.filters.type_name' => ['nullable', 'string', 'max:255'],
            'data_source.filters.status_name' => ['nullable', 'string', 'max:255'],
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
            'template_js.prohibited' => 'Only administrators can preview module JavaScript.',
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

        // Gracefully fallback to empty array when malformed JSON is submitted.
        $decoded = json_decode(trim($value), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Prepare payload before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Normalize dynamic form fields to stable scalar/array types.
        $this->merge([
            'config_schema' => $this->normalizeArrayField('config_schema'),
            'default_config' => $this->normalizeArrayField('default_config'),
            'data_source' => $this->normalizeArrayField('data_source'),
            'settings_limit' => $this->filled('settings_limit') ? (int) $this->input('settings_limit') : null,
        ]);
    }

}
