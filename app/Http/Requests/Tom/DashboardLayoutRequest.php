<?php

namespace App\Http\Requests\Tom;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardLayoutRequest extends FormRequest
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
        return [
            'layout' => ['required', 'array'],
            'layout.row_count' => ['required', 'integer', 'min:1', 'max:24'],
            'layout.column_count' => ['required', 'integer', 'min:1', 'max:24'],
            'layout.row_sizes' => ['nullable', 'array'],
            'layout.row_sizes.*' => ['numeric', 'min:1', 'max:100'],
            'layout.column_sizes' => ['nullable', 'array'],
            'layout.column_sizes.*' => ['numeric', 'min:1', 'max:100'],
            'layout.placements' => ['nullable', 'array'],
            'layout.placements.*.id' => ['nullable', 'integer', 'min:1'],
            'layout.placements.*.module_id' => [
                'required',
                'uuid',
                Rule::exists('modules', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'layout.placements.*.row_index' => ['required', 'integer', 'min:1', 'max:24'],
            'layout.placements.*.column_index' => ['required', 'integer', 'min:1', 'max:24'],
            'layout.placements.*.row_span' => ['nullable', 'integer', 'min:1', 'max:24'],
            'layout.placements.*.column_span' => ['nullable', 'integer', 'min:1', 'max:24'],
            'layout.placements.*.order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'layout.placements.*.config_overrides' => ['nullable', 'array'],
        ];
    }

    /**
     * Prepare for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $layout = $this->input('layout');

        // Accept JSON payloads from fetch() as well as array payloads from traditional form submissions.
        if (is_string($layout)) {
            $decoded = json_decode($layout, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $layout = $decoded;
            }
        }

        if (is_array($layout) && is_array($layout['placements'] ?? null)) {
            $layout['placements'] = array_map(function ($placement) {
                if (!is_array($placement)) {
                    return $placement;
                }

                // Normalize nested override payloads so validation always receives array data.
                if (is_string($placement['config_overrides'] ?? null)) {
                    $decoded = json_decode($placement['config_overrides'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $placement['config_overrides'] = $decoded;
                    }
                }

                return $placement;
            }, $layout['placements']);
        }

        $this->merge(['layout' => $layout]);
    }
}
