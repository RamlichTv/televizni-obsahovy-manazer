<?php

namespace App\Support\Modules\Template;

class LegacyTemplateFactory
{
    /**
     * Return built-in starter templates shown in the editor preset picker.
     *
     * @return array
     */
    public function presets(): array
    {
        $defaultSource = [
            'provider' => 'content_objects',
            'filters' => ['status_name' => 'Published'],
            'order_by' => 'published_at',
            'order_direction' => 'desc',
            'limit' => 8,
        ];

        return [
            'generic' => [
                'label' => 'Generic Card',
                'description' => 'Simple container with module title and a short description.',
                'template_html' => $this->genericTemplate(),
                'template_css' => '',
                'template_js' => '',
                'default_config' => ['title' => 'My Module'],
                'data_source' => $defaultSource,
            ],
            'content_carousel' => [
                'label' => 'Content Carousel',
                'description' => 'Horizontal list of content cards.',
                'template_html' => $this->carouselTemplate(),
                'template_css' => '',
                'template_js' => '',
                'default_config' => ['title' => 'Featured Carousel'],
                'data_source' => array_merge($defaultSource, ['limit' => 10]),
            ],
            'content_list' => [
                'label' => 'Content List',
                'description' => 'Vertical list of content rows.',
                'template_html' => $this->listTemplate(),
                'template_css' => '',
                'template_js' => '',
                'default_config' => ['title' => 'Latest Content'],
                'data_source' => $defaultSource,
            ],
        ];
    }

    /**
     * From legacy component.
     *
     * @param string|null $componentName
     * @return string
     */
    public function fromLegacyComponent(?string $componentName): string
    {
        return match ($componentName) {
            'modules.widgets.content-carousel' => $this->carouselTemplate(),
            'modules.widgets.content-list' => $this->listTemplate(),
            default => $this->genericTemplate(),
        };
    }

    /**
     * Generic template.
     *
     * @return string
     */
    public function genericTemplate(): string
    {
        return <<<'HTML'
<section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <h3 class="text-base font-semibold text-gray-900">{{ module.name }}</h3>
    <p class="mt-2 text-sm text-gray-600">Custom module template.</p>
</section>
HTML;
    }

    /**
     * Carousel template.
     *
     * @return string
     */
    public function carouselTemplate(): string
    {
        return <<<'HTML'
<section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <h3 class="text-base font-semibold text-gray-900">{{ config.title }}</h3>

    <div class="mt-3 flex gap-4 overflow-x-auto pb-2">
        {{#each items}}
        <article class="min-w-55 max-w-55 overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="h-28 bg-gray-100"></div>
            <div class="space-y-1 p-3">
                <p class="truncate text-sm font-semibold text-gray-900">{{ name }}</p>
                <p class="text-xs text-gray-500">{{ type.name }}</p>
            </div>
        </article>
        {{/each}}
    </div>
</section>
HTML;
    }

    /**
     * List template.
     *
     * @return string
     */
    public function listTemplate(): string
    {
        return <<<'HTML'
<section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <h3 class="text-base font-semibold text-gray-900">{{ config.title }}</h3>

    <ul class="mt-3 space-y-2">
        {{#each items}}
        <li class="rounded-lg border border-gray-200 px-3 py-2">
            <p class="truncate text-sm font-medium text-gray-900">{{ name }}</p>
            <p class="text-xs text-gray-500">{{ type.name }}</p>
        </li>
        {{/each}}
    </ul>
</section>
HTML;
    }
}

