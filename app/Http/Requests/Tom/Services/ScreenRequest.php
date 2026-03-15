<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\Screen;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class ScreenRequest extends \Illuminate\Foundation\Http\FormRequest
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
    public function rules() : array
    {
        // Edits need the current screen id so the screensaver uniqueness rule can ignore itself.
        $screen = $this->route('screen');
        $screenId = $screen instanceof Screen ? $screen->id : null;

        return [
            'name' => ['string', 'max:255', 'required'],
            'height' => ['nullable', 'integer', 'min:100', 'max:16384'],
            'width' => ['nullable', 'integer', 'min:100', 'max:16384'],
            'profile_id' => ['nullable', 'uuid', Rule::exists('profiles', 'id')],
            'status_id' => ['required', 'integer', Rule::exists('status_types', 'id')],
            'orientation_id' => ['nullable', 'integer', Rule::exists('orientation_types', 'id')],
            'room_id' => ['nullable', 'integer', Rule::exists('rooms', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_interactive' => ['nullable', 'boolean'],
            'auto_refresh_value' => ['nullable', 'integer'],
            'auto_refresh_unit' => ['nullable', 'string', Rule::in(['seconds', 'minutes'])],
            'auto_refresh_interval' => ['nullable', 'integer', 'min:5', 'max:86400'],
            'screensaver_idle_value' => ['nullable', 'integer'],
            'screensaver_idle_unit' => ['nullable', 'string', Rule::in(['seconds', 'minutes'])],
            'screensaver_idle_interval' => ['nullable', 'integer', 'min:5', 'max:86400'],
            'last_seen_at' => ['nullable', 'date'],
            'ip_whitelist' => ['nullable', 'array'],
            'ip_whitelist.*' => ['string', 'ip'],
            'ip_blacklist' => ['nullable', 'array'],
            'ip_blacklist.*' => ['string', 'ip'],
            'screensaver_module_id' => [
                'nullable',
                'uuid',
                Rule::exists('modules', 'id')->where(function (Builder $query) use ($screenId): void {
                    $query->whereIn('category', function (Builder $subquery): void {
                        $subquery->select('id')
                            ->from('module_categories')
                            ->where('is_screensaver', true);
                    });

                    // One screensaver module can only be attached to one screen.
                    $query->whereNotIn('id', function (Builder $subquery) use ($screenId): void {
                        $subquery->select('module_id')
                            ->from('screen_screensaver_modules')
                            ->when(is_string($screenId) && trim($screenId) !== '', function (Builder $innerQuery) use ($screenId): void {
                                $innerQuery->where('screen_id', '!=', $screenId);
                            });
                    });
                }),
            ],
        ];
    }

    /**
     * Validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'auto_refresh_value.integer' => __('Auto refresh value must be a whole number.'),
            'auto_refresh_unit.in' => __('Auto refresh unit must be seconds or minutes.'),
            'auto_refresh_interval.min' => __('Auto refresh must be at least 5 seconds.'),
            'auto_refresh_interval.max' => __('Auto refresh cannot exceed 24 hours.'),
            'screensaver_idle_value.integer' => __('Idle screensaver value must be a whole number.'),
            'screensaver_idle_unit.in' => __('Idle screensaver unit must be seconds or minutes.'),
            'screensaver_idle_interval.min' => __('Idle screensaver must be at least 5 seconds.'),
            'screensaver_idle_interval.max' => __('Idle screensaver cannot exceed 24 hours.'),
        ];
    }

    /**
     * Normalize ip input.
     *
     * @param mixed $arrayInput
     * @param mixed $textInput
     * @return array|null
     */
    private function normalizeIpInput(mixed $arrayInput, mixed $textInput): ?array
    {
        $values = [];

        if (is_array($arrayInput)) {
            $values = $arrayInput;
        } elseif (is_string($textInput)) {
            $values = preg_split('/[\s,;]+/', $textInput) ?: [];
        }

        $normalized = collect($values)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        return $normalized === [] ? null : $normalized;
    }

    /**
     * Normalize one UI interval pair into its stored seconds value.
     *
     * @param string $valueField
     * @param string $unitField
     * @param string $intervalField
     * @return array<string, mixed>
     */
    private function normalizeIntervalInput(string $valueField, string $unitField, string $intervalField): array
    {
        $value = $this->input($valueField, $this->input($intervalField));
        $unit = strtolower(trim((string) $this->input($unitField, 'seconds')));

        if (!in_array($unit, ['seconds', 'minutes'], true)) {
            $unit = 'seconds';
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '' || $value === null) {
            return [
                $valueField => null,
                $unitField => $unit,
                $intervalField => null,
            ];
        }

        $normalized = [
            $valueField => $value,
            $unitField => $unit,
        ];

        if (
            is_int($value)
            || (is_string($value) && preg_match('/^\d+$/', $value) === 1)
        ) {
            $normalized[$intervalField] = (int) $value * ($unit === 'minutes' ? 60 : 1);
        }

        return $normalized;
    }

    /**
     * Prepare payload before validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $nullableFields = [
            'profile_id',
            'orientation_id',
            'room_id',
            'screensaver_module_id',
            'description',
            'height',
            'width',
            'auto_refresh_value',
            'auto_refresh_interval',
            'screensaver_idle_value',
            'screensaver_idle_interval',
            'last_seen_at',
        ];

        // Normalize empty strings from selects/text inputs so nullable rules behave consistently.
        $normalized = [
            'is_interactive' => $this->boolean('is_interactive'),
            'ip_whitelist' => $this->normalizeIpInput(
                arrayInput: $this->input('ip_whitelist'),
                textInput: $this->input('ip_whitelist_text'),
            ),
            'ip_blacklist' => $this->normalizeIpInput(
                arrayInput: $this->input('ip_blacklist'),
                textInput: $this->input('ip_blacklist_text'),
            ),
            'screensaver_module_id' => trim((string) $this->input('screensaver_module_id', '')),
        ];

        foreach ($nullableFields as $field) {
            $value = $this->input($field);
            if (is_string($value) && trim($value) === '') {
                $normalized[$field] = null;
            }
        }

        // The UI splits refresh and idle screensaver timers into value + unit pairs.
        $normalized = array_merge(
            $normalized,
            $this->normalizeIntervalInput(
                valueField: 'auto_refresh_value',
                unitField: 'auto_refresh_unit',
                intervalField: 'auto_refresh_interval',
            ),
            $this->normalizeIntervalInput(
                valueField: 'screensaver_idle_value',
                unitField: 'screensaver_idle_unit',
                intervalField: 'screensaver_idle_interval',
            ),
        );

        $this->merge($normalized);
    }
}
