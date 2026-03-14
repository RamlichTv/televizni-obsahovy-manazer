<?php

namespace App\Support\Modules\Template;

/**
 * Built-in module preset library.
 *
 * This file intentionally keeps the starter templates close together so
 * category-specific module presets can be curated in one place.
 */
class LegacyTemplateFactory
{
    /**
     * Return built-in starter templates shown in the editor preset picker.
     *
     * The presets are intentionally content-first:
     * - HTML focuses on presentation structure.
     * - CSS carries most of the visual identity.
     * - JS is used only where motion materially improves the template.
     *
     * @return array<string, array<string, mixed>>
     */
    public function presets(): array
    {
        return [
            // Widget presets prioritize compact editorial layouts that work in
            // dashboard cards and standard page cells.
            'generic' => [
                'label' => 'Widget: Editorial Stack',
                'description' => 'Flexible editorial card stack for published content with image-first presentation.',
                'template_html' => $this->editorialStackTemplate(),
                'template_css' => $this->editorialStackCss(),
                'template_js' => '',
                'data_schema' => $this->contentModuleSchema([
                    'subtitle' => ['type' => 'string', 'default' => 'Curated highlights for the current layout.'],
                    'empty_text' => ['type' => 'string', 'default' => 'No published content matched this module query.'],
                ]),
                'module_data' => [
                    'title' => 'Editorial Highlights',
                    'subtitle' => 'Curated highlights for the current layout.',
                    'empty_text' => 'No published content matched this module query.',
                ],
                'data_source' => $this->posterSource(limit: 6),
            ],
            'widget_quick_link' => [
                'label' => 'Widget: Quick Link',
                'description' => 'Single-call-to-action card for dashboard shortcuts and frequently used destinations.',
                'template_html' => $this->quickLinkTemplate(),
                'template_css' => $this->quickLinkCss(),
                'template_js' => '',
                'data_schema' => [
                    'title' => ['type' => 'string', 'default' => 'Open Content Library'],
                    'subtitle' => ['type' => 'string', 'default' => 'Jump straight to a page your team uses every day.'],
                    'link_label' => ['type' => 'string', 'default' => 'Open page'],
                    'url' => ['type' => 'string', 'default' => '/content'],
                    'open_in_new_tab' => ['type' => 'boolean', 'default' => false],
                ],
                'module_data' => [
                    'title' => 'Open Content Library',
                    'subtitle' => 'Jump straight to a page your team uses every day.',
                    'link_label' => 'Open page',
                    'url' => '/content',
                    'open_in_new_tab' => false,
                ],
                'data_source' => [],
            ],
            'widget_my_content_list' => [
                'label' => 'Widget: My Content List',
                'description' => 'Compact list of the current user\'s published and unpublished content for dashboard follow-up.',
                'template_html' => $this->myContentListTemplate(),
                'template_css' => $this->myContentListCss(),
                'template_js' => '',
                'data_schema' => $this->contentModuleSchema([
                    'subtitle' => ['type' => 'string', 'default' => 'Published and unpublished items assigned to the current user.'],
                    'empty_text' => ['type' => 'string', 'default' => 'No published or unpublished items belong to the current user yet.'],
                ]),
                'module_data' => [
                    'title' => 'My Content',
                    'subtitle' => 'Published and unpublished items assigned to the current user.',
                    'empty_text' => 'No published or unpublished items belong to the current user yet.',
                ],
                'data_source' => $this->viewerContentSource(limit: 6),
            ],
            'widget_poster_strip' => [
                'label' => 'Widget: Poster Strip',
                'description' => 'Horizontal poster shelf with rich visuals. Admins also get an auto-glide script.',
                'template_html' => $this->posterStripTemplate(),
                'template_css' => $this->posterStripCss(),
                'template_js' => $this->posterStripJs(),
                'data_schema' => $this->contentModuleSchema([
                    'subtitle' => ['type' => 'string', 'default' => 'Use for spotlight rows and dashboard highlights.'],
                    'empty_text' => ['type' => 'string', 'default' => 'Add published posters to populate this strip.'],
                ]),
                'module_data' => [
                    'title' => 'Featured Posters',
                    'subtitle' => 'Use for spotlight rows and dashboard highlights.',
                    'empty_text' => 'Add published posters to populate this strip.',
                ],
                'data_source' => $this->posterSource(limit: 10),
            ],
            'widget_story_grid' => [
                'label' => 'Widget: Story Grid',
                'description' => 'Dense card grid for poster or photo content with tags, timestamps, and summaries.',
                'template_html' => $this->storyGridTemplate(),
                'template_css' => $this->storyGridCss(),
                'template_js' => '',
                'data_schema' => $this->contentModuleSchema([
                    'subtitle' => ['type' => 'string', 'default' => 'Best for editorial dashboards and content landing cells.'],
                    'empty_text' => ['type' => 'string', 'default' => 'No content is available for this grid.'],
                ]),
                'module_data' => [
                    'title' => 'Latest Stories',
                    'subtitle' => 'Best for editorial dashboards and content landing cells.',
                    'empty_text' => 'No content is available for this grid.',
                ],
                'data_source' => $this->visualSource(['Poster', 'Photo'], 8),
            ],

            // Screensaver presets are intentionally more immersive and assume
            // they will be rendered larger than a dashboard widget.
            'screensaver_poster_wall' => [
                'label' => 'Screensaver: Poster Wall',
                'description' => 'Full-cell poster mosaic that keeps every tile visible without scrolling or interaction.',
                'template_html' => $this->posterWallTemplate(),
                'template_css' => $this->posterWallCss(),
                'template_js' => '',
                'data_schema' => [
                    'title' => ['type' => 'string', 'default' => 'Now Showing'],
                    'subtitle' => ['type' => 'string', 'default' => 'Visual loop for idle screens and large display cells.'],
                ],
                'module_data' => [
                    'title' => 'Now Showing',
                    'subtitle' => 'Visual loop for idle screens and large display cells.',
                ],
                'data_source' => $this->visualSource(['Poster', 'Photo'], 8),
            ],
            'screensaver_spotlight' => [
                'label' => 'Screensaver: Spotlight Loop',
                'description' => 'One hero visual at a time with timed cross-fades. JS-enhanced for admin users.',
                'template_html' => $this->spotlightLoopTemplate(),
                'template_css' => $this->spotlightLoopCss(),
                'template_js' => $this->spotlightLoopJs(),
                'data_schema' => [
                    'title' => ['type' => 'string', 'default' => 'Program Highlights'],
                    'subtitle' => ['type' => 'string', 'default' => 'Immersive spotlight carousel for idle screens.'],
                    'interval_ms' => ['type' => 'integer', 'default' => 4800, 'min' => 2000, 'max' => 15000],
                ],
                'module_data' => [
                    'title' => 'Program Highlights',
                    'subtitle' => 'Immersive spotlight carousel for idle screens.',
                    'interval_ms' => 4800,
                ],
                'data_source' => $this->visualSource(['Poster', 'Photo'], 6),
            ],

            // Embedded presets stay config-driven because the main payload is
            // the external URL rather than a queried content collection.
            'embedded_page_frame' => [
                'label' => 'Embedded: Framed Web View',
                'description' => 'Browser-like embed card. Requires a category with Allow Embedded enabled.',
                'template_html' => $this->embeddedPageFrameTemplate(),
                'template_css' => $this->embeddedPageFrameCss(),
                'template_js' => '',
                'data_schema' => $this->embeddedSchema([
                    'caption' => ['type' => 'string', 'default' => 'Web page, BI board, or third-party player'],
                ]),
                'module_data' => [
                    'title' => 'Embedded Page',
                    'embed_url' => 'https://giphy.com/embed/Ju7l5y9osyymQ',
                    'caption' => 'Web page, BI board, or third-party player',
                ],
                'data_source' => [],
            ],
        ];
    }

    /**
     * Return starter HTML for legacy component names.
     *
     * @param string|null $componentName
     * @return string
     */
    public function fromLegacyComponent(?string $componentName): string
    {
        return match ($componentName) {
            'modules.widgets.content-carousel' => $this->posterStripTemplate(),
            'modules.widgets.content-list' => $this->editorialStackTemplate(),
            default => $this->editorialStackTemplate(),
        };
    }

    /**
     * Shared content data schema used by widget-like presets.
     *
     * @param array<string, array<string, mixed>> $extraFields
     * @return array<string, array<string, mixed>>
     */
    private function contentModuleSchema(array $extraFields = []): array
    {
        return array_merge([
            'title' => ['type' => 'string', 'default' => 'Module Title'],
        ], $extraFields);
    }

    /**
     * Shared embed data schema.
     *
     * @param array<string, array<string, mixed>> $extraFields
     * @return array<string, array<string, mixed>>
     */
    private function embeddedSchema(array $extraFields = []): array
    {
        return array_merge([
            'title' => ['type' => 'string', 'default' => 'Embedded Page'],
            'embed_url' => ['type' => 'string', 'default' => ''],
        ], $extraFields);
    }

    /**
     * Default source for poster-heavy modules.
     *
     * @param int $limit
     * @return array<string, mixed>
     */
    private function posterSource(int $limit = 8): array
    {
        return [
            'provider' => 'content_objects',
            'filters' => [
                'type_names' => ['Poster'],
                'status_names' => ['Published'],
            ],
            'order_by' => 'published_at',
            'order_direction' => 'desc',
            'limit' => $limit,
        ];
    }

    /**
     * Default source for broad visual modules.
     *
     * @param array<int, string> $typeNames
     * @param int $limit
     * @return array<string, mixed>
     */
    private function visualSource(array $typeNames, int $limit = 8): array
    {
        return [
            'provider' => 'content_objects',
            'filters' => [
                'type_names' => $typeNames,
                'status_names' => ['Published'],
            ],
            'order_by' => 'published_at',
            'order_direction' => 'desc',
            'limit' => $limit,
        ];
    }

    /**
     * Default source for widgets that should only expose the current viewer's content.
     *
     * @param int $limit
     * @return array<string, mixed>
     */
    private function viewerContentSource(int $limit = 6): array
    {
        return [
            'provider' => 'content_objects',
            'filters' => [
                'authored_by' => 'viewer',
                'status_names' => ['Published', 'Unpublished'],
            ],
            'order_by' => 'created_at',
            'order_direction' => 'desc',
            'limit' => $limit,
        ];
    }

    /**
     * Quick-link widget HTML.
     *
     * @return string
     */
    private function quickLinkTemplate(): string
    {
        return <<<'HTML'
<section class="quick-link-widget">
    {{#config.url}}
    <a
        class="quick-link-widget__card"
        href="{{ config.url }}"
        target="{{#config.open_in_new_tab}}_blank{{/config.open_in_new_tab}}{{^config.open_in_new_tab}}_top{{/config.open_in_new_tab}}"
        {{#config.open_in_new_tab}}rel="noopener noreferrer"{{/config.open_in_new_tab}}
    >
        <p class="quick-link-widget__eyebrow">Quick Link</p>
        <div class="quick-link-widget__body">
            <h2>{{ config.title }}</h2>
            <p>{{ config.subtitle }}</p>
        </div>
        <div class="quick-link-widget__footer">
            <span>{{ config.link_label }}</span>
            <span aria-hidden="true">→</span>
        </div>
    </a>
    {{/config.url}}

    {{^config.url}}
    <div class="quick-link-widget__empty">Add a URL to create a dashboard shortcut.</div>
    {{/config.url}}
</section>
HTML;
    }

    /**
     * Quick-link widget CSS.
     *
     * @return string
     */
    private function quickLinkCss(): string
    {
        return <<<'CSS'
.quick-link-widget {
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
}
.quick-link-widget__card,
.quick-link-widget__empty {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
    gap: 0.9rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
    overflow: hidden;
    padding: 1.15rem;
    border-radius: 1rem;
    border: 1px solid rgba(186, 230, 253, 0.9);
    background:
        radial-gradient(circle at top right, rgba(224, 242, 254, 0.95), rgba(240, 249, 255, 0.78)),
        linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    color: #0f172a;
    box-shadow: 0 18px 38px -34px rgba(7, 124, 182, 0.38);
}
.quick-link-widget__card {
    text-decoration: none;
    transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
}
.quick-link-widget__card:hover,
.quick-link-widget__card:focus-visible {
    transform: translateY(-2px);
    border-color: rgba(7, 124, 182, 0.45);
    box-shadow: 0 22px 44px -34px rgba(7, 124, 182, 0.46);
}
.quick-link-widget__eyebrow {
    margin: 0;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #077cb6;
}
.quick-link-widget__body {
    display: grid;
    align-content: center;
    gap: 0.65rem;
    min-height: 0;
}
.quick-link-widget__body h2 {
    margin: 0;
    font-size: clamp(1.35rem, 2vw, 2rem);
    line-height: 1.1;
}
.quick-link-widget__body p {
    margin: 0;
    color: #475569;
    line-height: 1.5;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}
.quick-link-widget__footer {
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.8rem;
    padding: 0.85rem 1rem;
    border-radius: 0.9rem;
    background: rgba(7, 124, 182, 0.1);
    color: #0369a1;
    font-weight: 700;
}
.quick-link-widget__empty {
    place-items: center;
    border-style: dashed;
    border-color: rgba(148, 163, 184, 0.42);
    color: #64748b;
    text-align: center;
}
CSS;
    }

    /**
     * Personal content-list widget HTML.
     *
     * @return string
     */
    private function myContentListTemplate(): string
    {
        return <<<'HTML'
<section class="my-content-widget">
    <header class="my-content-widget__header">
        <div>
            <h2>{{ config.title }}</h2>
            <p>{{ config.subtitle }}</p>
        </div>
        <span class="my-content-widget__count">{{ meta.count }} items</span>
    </header>

    <div class="my-content-widget__list">
        {{#each items}}
        <article class="my-content-widget__item">
            <div class="my-content-widget__copy">
                <h3>{{ name }}</h3>
                <p>{{ excerpt }}</p>
            </div>
            <div class="my-content-widget__meta">
                <span class="my-content-widget__status" data-status="{{ status_name }}">{{ status_name }}</span>
                {{#type_name}}<span class="my-content-widget__type">{{ type_name }}</span>{{/type_name}}
                {{#published_label}}<span class="my-content-widget__date">{{ published_label }}</span>{{/published_label}}
                {{^published_label}}<span class="my-content-widget__date">Not published yet</span>{{/published_label}}
            </div>
        </article>
        {{/each}}
    </div>

    {{^items}}
    <div class="my-content-widget__empty">{{ config.empty_text }}</div>
    {{/items}}
</section>
HTML;
    }

    /**
     * Personal content-list widget CSS.
     *
     * @return string
     */
    private function myContentListCss(): string
    {
        return <<<'CSS'
.my-content-widget {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 0.9rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
    overflow: hidden;
    padding: 1.1rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    color: #0f172a;
}
.my-content-widget__header {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    justify-content: space-between;
    gap: 0.75rem 1rem;
}
.my-content-widget__header h2 {
    margin: 0;
    font-size: clamp(1.15rem, 1.8vw, 1.55rem);
    line-height: 1.1;
}
.my-content-widget__header p {
    margin: 0.3rem 0 0;
    max-width: 32rem;
    color: #64748b;
    line-height: 1.45;
}
.my-content-widget__count {
    display: inline-flex;
    align-items: center;
    padding: 0.38rem 0.7rem;
    border-radius: 999px;
    background: #e0f2fe;
    color: #0369a1;
    font-size: 0.76rem;
    font-weight: 700;
}
.my-content-widget__list {
    display: grid;
    gap: 0.7rem;
    min-width: 0;
    min-height: 0;
    height: 100%;
    align-content: start;
    overflow: hidden;
}
.my-content-widget__item {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.7rem;
    min-height: 0;
    padding: 0.9rem 1rem;
    border-radius: 0.95rem;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: #ffffff;
    box-shadow: 0 10px 28px -28px rgba(15, 23, 42, 0.3);
}
.my-content-widget__copy {
    min-width: 0;
}
.my-content-widget__copy h3 {
    margin: 0;
    font-size: 0.98rem;
    line-height: 1.25;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}
.my-content-widget__copy p {
    margin: 0.35rem 0 0;
    color: #64748b;
    line-height: 1.45;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}
.my-content-widget__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}
.my-content-widget__status,
.my-content-widget__type,
.my-content-widget__date {
    display: inline-flex;
    align-items: center;
    padding: 0.28rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
}
.my-content-widget__status {
    background: #e2e8f0;
    color: #475569;
}
.my-content-widget__status[data-status="Published"] {
    background: #dcfce7;
    color: #166534;
}
.my-content-widget__status[data-status="Unpublished"] {
    background: #fef3c7;
    color: #92400e;
}
.my-content-widget__type,
.my-content-widget__date {
    background: #f8fafc;
    color: #64748b;
}
.my-content-widget__empty {
    display: grid;
    place-items: center;
    min-height: 0;
    height: 100%;
    padding: 2rem;
    border-radius: 1rem;
    border: 1px dashed rgba(148, 163, 184, 0.4);
    color: #64748b;
    text-align: center;
}
CSS;
    }

    /**
     * Editorial stack HTML.
     *
     * @return string
     */
    private function editorialStackTemplate(): string
    {
        return <<<'HTML'
<section class="editorial-stack">
    <header class="editorial-stack__header">
        <div class="editorial-stack__copy">
            <h2>{{ config.title }}</h2>
            <p>{{ config.subtitle }}</p>
        </div>
        <span class="editorial-stack__count">{{ meta.count }} stories</span>
    </header>

    <div class="editorial-stack__grid">
        {{#each items}}
        <article class="editorial-card">
            <div class="editorial-card__media">
                {{#has_media}}<img src="{{ media_url }}" alt="{{ name }}">{{/has_media}}
                {{^has_media}}<div class="editorial-card__placeholder">Preview</div>{{/has_media}}
            </div>
            <div class="editorial-card__body">
                {{#published_label}}<p class="editorial-card__meta">{{ published_label }}</p>{{/published_label}}
                <h3>{{ name }}</h3>
                <p>{{ excerpt }}</p>
            </div>
        </article>
        {{/each}}
    </div>

    {{^items}}
    <div class="editorial-stack__empty">{{ config.empty_text }}</div>
    {{/items}}
</section>
HTML;
    }

    /**
     * Editorial stack CSS.
     *
     * @return string
     */
    private function editorialStackCss(): string
    {
        return <<<'CSS'
.editorial-stack {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 1rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
    overflow: hidden;
    padding: clamp(1rem, 2vw, 1.5rem);
    background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
    color: #0f172a;
}
.editorial-stack__header {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    min-width: 0;
}
.editorial-stack__copy {
    min-width: 0;
}
.editorial-stack__header h2 {
    margin: 0;
    font-size: clamp(1.4rem, 2vw, 2.15rem);
    line-height: 1;
}
.editorial-stack__header p {
    margin: 0.35rem 0 0;
    max-width: 42rem;
    color: #475569;
    line-height: 1.45;
}
.editorial-stack__count {
    display: inline-flex;
    align-items: center;
    align-self: start;
    padding: 0.42rem 0.72rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
}
.editorial-stack__grid {
    display: grid;
    gap: 0.9rem;
    min-width: 0;
    min-height: 0;
    height: 100%;
    align-content: stretch;
    grid-template-columns: repeat(auto-fit, minmax(min(15rem, 100%), 1fr));
    grid-auto-rows: minmax(0, 1fr);
}
.editorial-card {
    display: grid;
    grid-template-rows: minmax(0, 0.95fr) auto;
    min-height: 0;
    overflow: hidden;
    border-radius: 1rem;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: #ffffff;
    box-shadow: 0 16px 38px -34px rgba(15, 23, 42, 0.28);
}
.editorial-card__media {
    min-height: 0;
    background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
}
.editorial-card__media img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.editorial-card__placeholder {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    font-size: 0.9rem;
    font-weight: 600;
    color: #64748b;
}
.editorial-card__body {
    display: grid;
    grid-template-rows: auto auto minmax(0, 1fr);
    align-content: start;
    gap: 0.55rem;
    min-height: 0;
    padding: 0.9rem 0.95rem 1rem;
}
.editorial-card__meta {
    margin: 0;
    font-size: 0.72rem;
    color: #64748b;
}
.editorial-card__body h3 {
    margin: 0;
    font-size: 1rem;
    line-height: 1.2;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}
.editorial-card__body p:last-child {
    margin: 0;
    color: #475569;
    line-height: 1.45;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}
.editorial-stack__empty {
    display: grid;
    place-items: center;
    min-height: 0;
    height: 100%;
    padding: 2rem;
    border: 1px dashed rgba(100, 116, 139, 0.45);
    border-radius: 1rem;
    color: #64748b;
    text-align: center;
}
@media (max-width: 720px) {
    .editorial-stack__grid {
        grid-auto-rows: minmax(12rem, 1fr);
    }
}
CSS;
    }

    /**
     * Poster strip HTML.
     *
     * @return string
     */
    private function posterStripTemplate(): string
    {
        return <<<'HTML'
<section class="poster-strip">
    <header class="poster-strip__header">
        <div class="poster-strip__header-copy">
            <h2>{{ config.title }}</h2>
            <p>{{ config.subtitle }}</p>
        </div>
        <span class="poster-strip__pill">{{ meta.count }} picks</span>
    </header>

    <div class="poster-strip__rail" data-poster-strip-rail>
        {{#each items}}
        <article class="poster-strip__card">
            <div class="poster-strip__poster">
                {{#has_media}}<img src="{{ media_url }}" alt="{{ name }}">{{/has_media}}
                {{^has_media}}<div class="poster-strip__fallback">Preview</div>{{/has_media}}
            </div>
            <div class="poster-strip__copy">
                {{#published_label}}<p class="poster-strip__meta">{{ published_label }}</p>{{/published_label}}
                <h3>{{ name }}</h3>
                <p>{{ excerpt }}</p>
            </div>
        </article>
        {{/each}}
    </div>

    {{^items}}
    <div class="poster-strip__empty">{{ config.empty_text }}</div>
    {{/items}}
</section>
HTML;
    }

    /**
     * Poster strip CSS.
     *
     * @return string
     */
    private function posterStripCss(): string
    {
        return <<<'CSS'
.poster-strip {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 1rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
    overflow: hidden;
    padding: clamp(1rem, 2vw, 1.5rem);
    background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
    color: #0f172a;
}
.poster-strip__header {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    min-width: 0;
}
.poster-strip__header-copy {
    min-width: 0;
}
.poster-strip__header h2 {
    margin: 0;
    font-size: clamp(1.45rem, 2.2vw, 2.25rem);
    line-height: 1;
}
.poster-strip__header p {
    margin: 0.35rem 0 0;
    max-width: 38rem;
    color: #64748b;
    line-height: 1.45;
}
.poster-strip__pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 0.45rem 0.8rem;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
}
.poster-strip__rail {
    display: grid;
    gap: 0.9rem;
    min-width: 0;
    min-height: 0;
    height: 100%;
    align-content: stretch;
    grid-template-columns: repeat(auto-fit, minmax(min(11rem, 100%), 1fr));
    grid-auto-rows: minmax(0, 1fr);
}
.poster-strip__card {
    display: grid;
    grid-template-rows: minmax(0, 1fr) auto;
    gap: 0.8rem;
    min-height: 0;
}
.poster-strip__poster {
    min-height: 0;
    overflow: hidden;
    border-radius: 1.15rem;
    background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
    box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.28);
}
.poster-strip__poster img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.poster-strip__fallback {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 600;
}
.poster-strip__copy {
    display: grid;
    grid-template-rows: auto auto minmax(0, 1fr);
    gap: 0.4rem;
    min-height: 0;
}
.poster-strip__meta {
    margin: 0;
    color: #64748b;
    font-size: 0.74rem;
}
.poster-strip__copy h3 {
    margin: 0;
    font-size: 1rem;
    line-height: 1.25;
    color: #0f172a;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}
.poster-strip__copy p:last-child {
    margin: 0;
    color: #475569;
    line-height: 1.45;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}
.poster-strip__empty {
    display: grid;
    place-items: center;
    min-height: 0;
    height: 100%;
    border-radius: 1rem;
    border: 1px dashed rgba(148, 163, 184, 0.42);
    color: #64748b;
    text-align: center;
    padding: 2rem;
}
@media (max-width: 720px) {
    .poster-strip__rail {
        grid-auto-rows: minmax(14rem, 1fr);
    }
}
CSS;
    }

    /**
     * Poster strip JS.
     *
     * @return string
     */
    private function posterStripJs(): string
    {
        return '';
    }

    /**
     * Story grid HTML.
     *
     * @return string
     */
    private function storyGridTemplate(): string
    {
        return <<<'HTML'
<section class="story-grid">
    <header class="story-grid__header">
        <div>
            <h2>{{ config.title }}</h2>
            <p>{{ config.subtitle }}</p>
        </div>
        <span>{{ meta.count }} items</span>
    </header>

    <div class="story-grid__cards">
        {{#each items}}
        <article class="story-grid__card">
            <div class="story-grid__media">
                {{#has_media}}<img src="{{ media_url }}" alt="{{ name }}">{{/has_media}}
                {{^has_media}}<div class="story-grid__fallback">Preview</div>{{/has_media}}
            </div>
            <div class="story-grid__body">
                <div class="story-grid__tags">
                    {{#tag_names}}<span>{{.}}</span>{{/tag_names}}
                </div>
                <h3>{{ name }}</h3>
                <p>{{ excerpt }}</p>
                <footer>{{#published_label}}{{ published_label }}{{/published_label}}{{^published_label}}Not yet published{{/published_label}}</footer>
            </div>
        </article>
        {{/each}}
    </div>

    {{^items}}
    <div class="story-grid__empty">{{ config.empty_text }}</div>
    {{/items}}
</section>
HTML;
    }

    /**
     * Story grid CSS.
     *
     * @return string
     */
    private function storyGridCss(): string
    {
        return <<<'CSS'
.story-grid {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 0.95rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
    overflow: hidden;
    padding: clamp(1rem, 2vw, 1.4rem);
    background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
    color: #0f172a;
}
.story-grid__header {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    justify-content: space-between;
    gap: 0.75rem 1rem;
}
.story-grid__header h2 {
    margin: 0;
    font-size: clamp(1.35rem, 2vw, 2.05rem);
    line-height: 1;
}
.story-grid__header p {
    margin: 0.35rem 0 0;
    color: #64748b;
    max-width: 38rem;
    line-height: 1.45;
}
.story-grid__header span {
    font-size: 0.8rem;
    font-weight: 700;
    color: #0f172a;
    opacity: 0.6;
}
.story-grid__cards {
    display: grid;
    gap: 0.9rem;
    min-width: 0;
    min-height: 0;
    height: 100%;
    align-content: stretch;
    grid-template-columns: repeat(auto-fit, minmax(min(15rem, 100%), 1fr));
    grid-auto-rows: minmax(0, 1fr);
}
.story-grid__card {
    display: grid;
    grid-template-rows: minmax(0, 0.9fr) auto;
    min-height: 0;
    overflow: hidden;
    border-radius: 1rem;
    background: white;
    border: 1px solid rgba(148, 163, 184, 0.24);
    box-shadow: 0 20px 42px -36px rgba(15, 23, 42, 0.28);
}
.story-grid__media {
    min-height: 0;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
}
.story-grid__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.story-grid__fallback {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 600;
}
.story-grid__body {
    display: grid;
    grid-template-rows: auto auto auto minmax(0, 1fr) auto;
    gap: 0.7rem;
    min-height: 0;
    padding: 1rem;
}
.story-grid__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.story-grid__tags span {
    padding: 0.22rem 0.5rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.7rem;
    font-weight: 600;
}
.story-grid__body h3 {
    margin: 0;
    font-size: 1rem;
    line-height: 1.25;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}
.story-grid__body p {
    margin: 0;
    color: #475569;
    line-height: 1.45;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}
.story-grid__body footer {
    font-size: 0.75rem;
    color: #64748b;
}
.story-grid__empty {
    display: grid;
    place-items: center;
    min-height: 0;
    height: 100%;
    padding: 2rem;
    border-radius: 1rem;
    border: 1px dashed rgba(148, 163, 184, 0.4);
    color: #64748b;
    text-align: center;
}
@media (max-width: 720px) {
    .story-grid__cards {
        grid-auto-rows: minmax(12rem, 1fr);
    }
}
CSS;
    }

    /**
     * Poster wall HTML.
     *
     * @return string
     */
    private function posterWallTemplate(): string
    {
        return <<<'HTML'
<section class="poster-wall">
    <div class="poster-wall__copy">
        <h2>{{ config.title }}</h2>
        <p>{{ config.subtitle }}</p>
    </div>

    <div class="poster-wall__grid">
        {{#each items}}
        <article class="poster-wall__tile">
            {{#has_media}}<img src="{{ media_url }}" alt="{{ name }}">{{/has_media}}
            {{^has_media}}<div class="poster-wall__fallback">Preview</div>{{/has_media}}
            <div class="poster-wall__shade"></div>
            <div class="poster-wall__label">
                {{#published_label}}<span>{{ published_label }}</span>{{/published_label}}
                <strong>{{ name }}</strong>
            </div>
        </article>
        {{/each}}
    </div>

    {{^items}}
    <div class="poster-wall__empty">Add visual content to populate this screensaver wall.</div>
    {{/items}}
</section>
HTML;
    }

    /**
     * Poster wall CSS.
     *
     * @return string
     */
    private function posterWallCss(): string
    {
        return <<<'CSS'
.poster-wall {
    position: relative;
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 0.95rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
    padding: clamp(1rem, 2vw, 1.45rem);
    overflow: hidden;
    background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
    color: #0f172a;
}
.poster-wall__copy {
    position: relative;
    z-index: 2;
    max-width: 40rem;
}
.poster-wall__copy h2 {
    margin: 0;
    font-size: clamp(1.6rem, 3vw, 2.6rem);
    line-height: 1;
}
.poster-wall__copy p {
    margin: 0.4rem 0 0;
    max-width: 42rem;
    color: #64748b;
    line-height: 1.45;
}
.poster-wall__grid {
    display: grid;
    gap: 0.8rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 0;
    align-content: stretch;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    grid-template-rows: repeat(2, minmax(0, 1fr));
}
.poster-wall__tile {
    position: relative;
    min-height: 0;
    overflow: hidden;
    border-radius: 1rem;
    background: #e2e8f0;
    border: 1px solid rgba(226, 232, 240, 0.95);
    box-shadow: 0 16px 38px -32px rgba(15, 23, 42, 0.25);
    animation: poster-wall-float 20s ease-in-out infinite;
}
.poster-wall__tile:nth-child(2n) {
    animation-duration: 24s;
}
.poster-wall__tile:nth-child(3n) {
    animation-duration: 18s;
}
.poster-wall__tile img,
.poster-wall__fallback {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.poster-wall__fallback {
    display: grid;
    place-items: center;
    font-size: 0.9rem;
    font-weight: 600;
    color: #64748b;
    background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
}
.poster-wall__shade {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.02) 0%, rgba(15, 23, 42, 0.42) 100%);
}
.poster-wall__label {
    position: absolute;
    inset-inline: 0;
    bottom: 0;
    display: grid;
    gap: 0.22rem;
    padding: 1rem;
}
.poster-wall__label span {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.82);
}
.poster-wall__label strong {
    font-size: 0.95rem;
    line-height: 1.2;
    color: #ffffff;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}
.poster-wall__empty {
    display: grid;
    place-items: center;
    min-height: 0;
    height: 100%;
    border-radius: 1rem;
    border: 1px dashed rgba(148, 163, 184, 0.36);
    color: #64748b;
    text-align: center;
}
@keyframes poster-wall-float {
    0%,
    100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-0.4rem) scale(1.015); }
}
@media (max-width: 1100px) {
    .poster-wall__grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        grid-template-rows: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 760px) {
    .poster-wall__grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: repeat(4, minmax(0, 1fr));
    }
}
CSS;
    }

    /**
     * Spotlight loop HTML.
     *
     * @return string
     */
    private function spotlightLoopTemplate(): string
    {
        return <<<'HTML'
<section class="spotlight-loop">
    <header class="spotlight-loop__header">
        <div>
            <h2>{{ config.title }}</h2>
            <p>{{ config.subtitle }}</p>
        </div>
        <span>{{ meta.count }} highlights</span>
    </header>

    <div class="spotlight-loop__viewport" data-spotlight-stack data-interval="{{ config.interval_ms }}">
        {{#each items}}
        <article class="spotlight-loop__slide">
            <div class="spotlight-loop__backdrop">
                {{#has_media}}<img src="{{ media_url }}" alt="{{ name }}">{{/has_media}}
                {{^has_media}}<div class="spotlight-loop__fallback">Preview</div>{{/has_media}}
            </div>
            <div class="spotlight-loop__overlay"></div>
            <div class="spotlight-loop__content">
                {{#published_label}}<p class="spotlight-loop__meta">{{ published_label }}</p>{{/published_label}}
                <h2>{{ name }}</h2>
                <p>{{ excerpt }}</p>
                <div class="spotlight-loop__tags">{{#tag_names}}<span>{{.}}</span>{{/tag_names}}</div>
            </div>
        </article>
        {{/each}}

        {{^items}}
        <div class="spotlight-loop__empty">{{ config.subtitle }}</div>
        {{/items}}
    </div>

    <div class="spotlight-loop__dots" data-spotlight-dots></div>
</section>
HTML;
    }

    /**
     * Spotlight loop CSS.
     *
     * @return string
     */
    private function spotlightLoopCss(): string
    {
        return <<<'CSS'
.spotlight-loop {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
    gap: 0.9rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
    overflow: hidden;
    padding: clamp(1rem, 2vw, 1.3rem);
    background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
    color: #0f172a;
}
.spotlight-loop__header {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    justify-content: space-between;
    gap: 0.75rem 1rem;
}
.spotlight-loop__header h2 {
    margin: 0;
    font-size: clamp(1.45rem, 2.6vw, 2.3rem);
    line-height: 1;
}
.spotlight-loop__header p {
    margin: 0.35rem 0 0;
    color: #64748b;
    line-height: 1.45;
}
.spotlight-loop__header span {
    margin: 0;
    font-size: 0.82rem;
    font-weight: 700;
    color: #475569;
}
.spotlight-loop__viewport {
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 0;
    border-radius: 1.2rem;
    background: #e2e8f0;
    border: 1px solid rgba(226, 232, 240, 0.95);
    box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.28);
}
.spotlight-loop__slide {
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity 850ms ease, transform 850ms ease;
    transform: scale(1.03);
}
.spotlight-loop__slide:first-child,
.spotlight-loop__slide.is-active {
    opacity: 1;
    transform: scale(1);
}
.spotlight-loop__backdrop,
.spotlight-loop__backdrop img,
.spotlight-loop__fallback {
    width: 100%;
    height: 100%;
}
.spotlight-loop__backdrop img {
    object-fit: cover;
    display: block;
}
.spotlight-loop__fallback {
    display: grid;
    place-items: center;
    background: linear-gradient(145deg, #f8fafc 0%, #e2e8f0 100%);
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 600;
}
.spotlight-loop__overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(90deg, rgba(255, 255, 255, 0.88) 0%, rgba(255, 255, 255, 0.12) 70%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.08) 0%, rgba(15, 23, 42, 0.18) 100%);
}
.spotlight-loop__content {
    position: absolute;
    left: 0;
    bottom: 0;
    z-index: 1;
    display: grid;
    gap: 0.7rem;
    max-width: min(38rem, 82%);
    margin: 1rem;
    padding: 1.05rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 0 14px 34px -28px rgba(15, 23, 42, 0.32);
}
.spotlight-loop__meta {
    margin: 0;
    color: #64748b;
    font-size: 0.78rem;
}
.spotlight-loop__content h2 {
    margin: 0;
    font-size: clamp(1.8rem, 3.6vw, 3.4rem);
    line-height: 1;
}
.spotlight-loop__content p {
    margin: 0;
    color: #475569;
    font-size: 0.98rem;
    line-height: 1.5;
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 4;
}
.spotlight-loop__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}
.spotlight-loop__tags span {
    padding: 0.34rem 0.58rem;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.72rem;
}
.spotlight-loop__dots {
    display: flex;
    gap: 0.45rem;
    align-items: center;
}
.spotlight-loop__dots button {
    width: 0.72rem;
    height: 0.72rem;
    border: 0;
    border-radius: 999px;
    padding: 0;
    background: rgba(148, 163, 184, 0.4);
}
.spotlight-loop__dots button.is-active {
    background: #0f172a;
}
.spotlight-loop__empty {
    display: grid;
    place-items: center;
    width: 100%;
    height: 100%;
    padding: 2rem;
    text-align: center;
    color: #64748b;
}
CSS;
    }

    /**
     * Spotlight loop JS.
     *
     * @return string
     */
    private function spotlightLoopJs(): string
    {
        return <<<'JS'
const stack = root.querySelector('[data-spotlight-stack]');
const dotsContainer = root.querySelector('[data-spotlight-dots]');
if (!stack) {
    return;
}

const slides = Array.from(stack.querySelectorAll('.spotlight-loop__slide'));
if (slides.length === 0) {
    return;
}

const interval = Number.parseInt(stack.getAttribute('data-interval') || '4800', 10);
let activeIndex = 0;
let timer = null;

const dotButtons = slides.map((_, index) => {
    if (!dotsContainer) {
        return null;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.setAttribute('aria-label', `Show slide ${index + 1}`);
    button.addEventListener('click', () => {
        activate(index);
        restart();
    });
    dotsContainer.appendChild(button);

    return button;
});

const activate = (index) => {
    activeIndex = index;
    slides.forEach((slide, slideIndex) => {
        slide.classList.toggle('is-active', slideIndex === index);
    });
    dotButtons.forEach((button, buttonIndex) => {
        if (button) {
            button.classList.toggle('is-active', buttonIndex === index);
        }
    });
};

const next = () => activate((activeIndex + 1) % slides.length);

const start = () => {
    if (slides.length < 2 || timer !== null || interval < 2000) {
        return;
    }

    timer = window.setInterval(next, interval);
};

const stop = () => {
    if (timer === null) {
        return;
    }

    window.clearInterval(timer);
    timer = null;
};

const restart = () => {
    stop();
    start();
};

host.addEventListener('mouseenter', stop);
host.addEventListener('mouseleave', start);

activate(0);
start();
JS;
    }

    /**
     * Embedded page frame HTML.
     *
     * @return string
     */
    private function embeddedPageFrameTemplate(): string
    {
        return <<<'HTML'
<section class="embedded-frame">
    <header class="embedded-frame__header">
        <div>
            <strong>{{ config.title }}</strong>
            <span>{{ config.caption }}</span>
        </div>
    </header>

    {{#config.embed_url}}
    <iframe 
    class="embedded-frame__iframe"
    src="{{.}}" 
    title="{{ config.title }}" 
    frameborder="0" 
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
    referrerpolicy="strict-origin-when-cross-origin" 
    allowfullscreen></iframe>
    {{/config.embed_url}}

    {{^config.embed_url}}
    <div class="embedded-frame__empty">Add an embed URL to render a page here.</div>
    {{/config.embed_url}}
</section>
HTML;
    }

    /**
     * Embedded page frame CSS.
     *
     * @return string
     */
    private function embeddedPageFrameCss(): string
    {
        return <<<'CSS'
.embedded-frame {
    display: grid;
    grid-template-rows: auto minmax(0, 1fr);
    gap: 0.9rem;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 100%;
    overflow: hidden;
    padding: clamp(0.9rem, 2vw, 1.25rem);
    background: linear-gradient(180deg, #fffdf8 0%, #f8fafc 100%);
    color: #0f172a;
}
.embedded-frame__header {
    min-width: 0;
}
.embedded-frame__header strong,
.embedded-frame__header span {
    display: block;
}
.embedded-frame__header strong {
    font-size: clamp(1.2rem, 2vw, 1.75rem);
}
.embedded-frame__header span {
    margin-top: 0.35rem;
    color: #64748b;
    line-height: 1.45;
}
.embedded-frame__iframe {
    display: block;
    width: 100%;
    height: 100%;
    border: 0;
    background: white;
    pointer-events: none;
}
.embedded-frame__empty {
    display: grid;
    place-items: center;
    min-height: 0;
    height: 100%;
    padding: 2rem;
    border-radius: 1.15rem;
    border: 1px dashed rgba(148, 163, 184, 0.38);
    color: #64748b;
    text-align: center;
}
CSS;
    }

}
