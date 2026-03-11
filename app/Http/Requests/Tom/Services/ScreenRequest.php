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
        $screen = $this->route('screen');
        $screenId = $screen instanceof Screen ? $screen->id : null;

        return [
            'name' => ['string', 'max:255', 'required'],
            'height' => ['nullable', 'integer', 'min:100', 'max:16384'],
            'width' => ['nullable', 'integer', 'min:100', 'max:16384'],
            'profile_id' => ['nullable', 'uuid', Rule::exists('profiles', 'id')],
            'status_id' => ['required', 'integer', Rule::exists('status_types', 'id')],
            'orientation_id' => ['nullable', 'integer', Rule::exists('orientation_types', 'id')],
            'hardware_id' => ['nullable', 'string', 'max:255', Rule::unique(Screen::class, 'hardware_id')->ignore($screenId)],
            'room_id' => ['nullable', 'integer', Rule::exists('rooms', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_interactive' => ['nullable', 'boolean'],
            'auto_refresh_value' => ['nullable', 'integer'],
            'auto_refresh_unit' => ['nullable', 'string', Rule::in(['seconds', 'minutes'])],
            'auto_refresh_interval' => ['nullable', 'integer', 'min:5', 'max:86400'],
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
            'hardware_id',
            'screensaver_module_id',
            'description',
            'height',
            'width',
            'auto_refresh_value',
            'auto_refresh_interval',
            'last_seen_at',
        ];

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

        $refreshValue = $this->input('auto_refresh_value', $this->input('auto_refresh_interval'));
        $refreshUnit = strtolower(trim((string) $this->input('auto_refresh_unit', 'seconds')));

        if (is_string($refreshValue)) {
            $refreshValue = trim($refreshValue);
        }

        if ($refreshValue === '' || $refreshValue === null) {
            $normalized['auto_refresh_value'] = null;
            $normalized['auto_refresh_interval'] = null;
        } else {
            $normalized['auto_refresh_value'] = $refreshValue;

            if (
                is_int($refreshValue)
                || (is_string($refreshValue) && preg_match('/^\d+$/', $refreshValue) === 1)
            ) {
                $normalized['auto_refresh_interval'] = (int) $refreshValue * ($refreshUnit === 'minutes' ? 60 : 1);
            }
        }

        $normalized['auto_refresh_unit'] = $refreshUnit;

        $this->merge($normalized);
    }
}
