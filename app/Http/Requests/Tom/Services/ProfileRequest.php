<?php

namespace App\Http\Requests\Tom\Services;

use Illuminate\Validation\Rule;

class ProfileRequest extends \Illuminate\Foundation\Http\FormRequest
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'settings' => ['nullable', 'array'],
            'settings.playback' => ['nullable', 'array'],
            'settings.playback.auto_rotate' => ['nullable', 'boolean'],
            'settings.playback.loop_pages' => ['nullable', 'boolean'],
            'settings.display' => ['nullable', 'array'],
            'settings.display.show_page_name' => ['nullable', 'boolean'],
            'settings.display.show_page_indicators' => ['nullable', 'boolean'],
            'settings.animation' => ['nullable', 'array'],
            'settings.animation.transition' => ['nullable', 'string', Rule::in(['slide', 'fade', 'none'])],
            'settings.animation.transition_duration_ms' => ['nullable', 'integer', 'min:100', 'max:10000'],
            'settings.custom_pairs' => ['nullable', 'array'],
            'settings.custom_pairs.*.key' => ['nullable', 'string', 'max:64'],
            'settings.custom_pairs.*.value' => ['nullable', 'string', 'max:255'],
        ];

        if ($this->isMethod('post')) {
            $rules['initial_page_name'] = ['nullable', 'string', 'max:255'];
            $rules['initial_page_rows'] = ['nullable', 'integer', 'min:1', 'max:24'];
            $rules['initial_page_columns'] = ['nullable', 'integer', 'min:1', 'max:24'];
            $rules['initial_page_duration'] = ['nullable', 'integer', 'min:1', 'max:86400'];
        }

        return $rules;
    }

    /**
     * Prepare for validation.
     *
     * @return void
     */
    protected function prepareForValidation() : void
    {
        $normalized = [];

        $settings = $this->input('settings');
        if (is_string($settings)) {
            $trimmed = trim($settings);
            if ($trimmed === '') {
                $normalized['settings'] = [];
            } else {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $normalized['settings'] = $decoded;
                }
            }
        }

        if (is_array($settings)) {
            $custom = [];
            if (is_array($settings['custom_pairs'] ?? null)) {
                foreach ($settings['custom_pairs'] as $pair) {
                    if (!is_array($pair)) {
                        continue;
                    }

                    $key = trim((string) ($pair['key'] ?? ''));
                    if ($key === '') {
                        continue;
                    }

                    $custom[$key] = trim((string) ($pair['value'] ?? ''));
                }
            }

            if ($custom === []) {
                unset($settings['custom']);
            } else {
                $settings['custom'] = $custom;
            }
            unset($settings['custom_pairs']);

            if (is_string($settings['animation']['transition'] ?? null)) {
                $settings['animation']['transition'] = strtolower(trim((string) $settings['animation']['transition']));
            }

            $duration = $settings['animation']['transition_duration_ms'] ?? null;
            if ($duration === '' || $duration === null) {
                unset($settings['animation']['transition_duration_ms']);
            }

            $normalized['settings'] = $settings;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
