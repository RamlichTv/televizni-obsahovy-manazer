<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Providers
    |--------------------------------------------------------------------------
    |
    | Providers are allowlisted query handlers. A module references a provider
    | key in `data_source.provider`; the matching class prepares data for
    | template rendering.
    |
    */
    'providers' => [
        'content_objects' => App\Support\Modules\DataProviders\ContentObjectsProvider::class,
    ],

    'provider_labels' => [
        'content_objects' => 'Content Objects',
    ],

    'max_items_per_module' => 30,

    /*
    |--------------------------------------------------------------------------
    | Tailwind Build Automation
    |--------------------------------------------------------------------------
    |
    | Module templates are stored under storage/app/public/users/... and their
    | classes are compiled by Tailwind during the frontend build. Enable this
    | to queue `npm run build` after module create/update/delete operations.
    | A queue worker with Node/npm available is required.
    |
    */
    'tailwind_build' => [
        'enabled' => (bool) env('MODULE_TAILWIND_BUILD_ENABLED', false),
        'command' => (string) env('MODULE_TAILWIND_BUILD_COMMAND', 'npm run build'),
        'queue' => (string) env('MODULE_TAILWIND_BUILD_QUEUE', 'module-builds'),
        'delay_seconds' => (int) env('MODULE_TAILWIND_BUILD_DELAY_SECONDS', 15),
        'timeout_seconds' => (int) env('MODULE_TAILWIND_BUILD_TIMEOUT_SECONDS', 600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Sanitizer Rules
    |--------------------------------------------------------------------------
    */
    'allowed_tags' => [
        'a',
        'article',
        'aside',
        'b',
        'blockquote',
        'br',
        'button',
        'caption',
        'code',
        'circle',
        'div',
        'em',
        'figcaption',
        'figure',
        'footer',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'header',
        'hr',
        'i',
        'input',
        'img',
        'label',
        'li',
        'line',
        'main',
        'nav',
        'ol',
        'p',
        'pre',
        'path',
        'rectangle',
        'section',
        'small',
        'span',
        'strong',
        'sub',
        'sup',
        'svg',
        'table',
        'tbody',
        'td',
        'th',
        'thead',
        'tr',
        'u',
        'ul',
        'video',
    ],

    'allowed_attributes' => [
        'alt',
        'aria-*',
        'class',
        'colspan',
        'cx',
        'cy',
        'data-*',
        'd',
        'fill',
        'stroke*',
        'height',
        'href',
        'id',
        'r',
        'rel',
        'role',
        'rowspan',
        'src',
        'target',
        'title',
        'viewbox',
        'width',
    ],

    'forbidden_tags' => [
        'base',
        'embed',
        'form',
        'iframe',
        'input',
        'link',
        'meta',
        'object',
        'script',
        'style',
        'textarea',
    ],
];
