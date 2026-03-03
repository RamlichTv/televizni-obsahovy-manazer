<?php

namespace App\Http\Requests\Tom\Services;

use App\Models\Screen;
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
            'auto_refresh_interval' => ['nullable', 'integer', 'min:5', 'max:86400'],
            'last_seen_at' => ['nullable', 'date'],
            'ip_whitelist' => ['nullable', 'array'],
            'ip_whitelist.*' => ['string', 'ip'],
            'ip_blacklist' => ['nullable', 'array'],
            'ip_blacklist.*' => ['string', 'ip'],
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
            'description',
            'height',
            'width',
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
        ];

        foreach ($nullableFields as $field) {
            $value = $this->input($field);
            if (is_string($value) && trim($value) === '') {
                $normalized[$field] = null;
            }
        }

        $this->merge($normalized);
    }
}
