<?php

namespace Tests\Feature\Modules;

use App\Models\ContentObject;
use App\Models\Module;
use App\Models\ModuleCategory;
use App\Models\User;
use App\Support\Modules\ModuleRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModuleRendererTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test it renders file backed template with provider data.
     *
     * @return void
     */
    public function test_it_renders_file_backed_template_with_provider_data(): void
    {
        Storage::fake('public');
        $this->seedCatalogData();

        $author = $this->createAuthor();
        $category = ModuleCategory::create([
            'name' => 'widget',
            'slug' => 'widget',
            'is_widget' => true,
            'is_system' => true,
        ]);

        ContentObject::create([
            'name' => 'Visible poster',
            'type_id' => 2,
            'author_id' => $author->id,
            'status_id' => 2,
            'priority' => 10,
            'meta' => [],
            'published_at' => now()->subHour(),
        ]);

        ContentObject::create([
            'name' => 'Hidden draft',
            'type_id' => 2,
            'author_id' => $author->id,
            'status_id' => 1,
            'priority' => 10,
            'meta' => [],
        ]);

        $module = Module::create([
            'name' => 'Poster list',
            'category' => $category->id,
            'created_by' => $author->id,
            'is_active' => true,
            'revision' => 1,
            'template_path' => 'users/' . $author->username . '/modules/widget/module-1/v1.html',
            'template_checksum' => hash('sha256', 'template'),
            'data_schema' => [],
            'module_data' => ['title' => 'Poster list'],
            'data_source' => [
                'provider' => 'content_objects',
                'filters' => [
                    'type_name' => 'Poster',
                    'status_name' => 'Published',
                ],
                'order_by' => 'created_at',
                'order_direction' => 'desc',
                'limit' => 10,
            ],
        ]);

        Storage::disk('public')->put(
            $module->template_path,
            '<section><h3>{{ config.title }}</h3>{{#each items}}<p>{{ name }}</p>{{/each}}</section>'
        );

        $html = app(ModuleRenderer::class)->renderHtml($module);

        $this->assertStringContainsString('Poster list', $html);
        $this->assertStringContainsString('Visible poster', $html);
        $this->assertStringNotContainsString('Hidden draft', $html);
    }

    /**
     * Test sanitizer blocks javascript execution markup.
     *
     * @return void
     */
    public function test_sanitizer_blocks_javascript_execution_markup(): void
    {
        $sanitized = app(ModuleRenderer::class)->sanitizeTemplate(
            '<div onclick="alert(1)"><script>alert(1)</script><a href="javascript:alert(2)">x</a><p>{{ module.name }}</p></div>'
        );

        $this->assertStringNotContainsString('onclick=', $sanitized);
        $this->assertStringNotContainsString('<script', strtolower($sanitized));
        $this->assertStringNotContainsString('javascript:alert', strtolower($sanitized));
        $this->assertStringContainsString('{{ module.name }}', $sanitized);
    }

    /**
     * Test sanitizer keeps allowed data and aria attributes while hardening blank-target links.
     *
     * @return void
     */
    public function test_sanitizer_preserves_allowed_metadata_attributes_and_hardens_blank_target_links(): void
    {
        $sanitized = app(ModuleRenderer::class)->sanitizeTemplate(
            '<div data-item-id="42" aria-label="Poster card" onclick="alert(1)"><a href="/content" target="_blank">Open</a></div>'
        );

        $this->assertStringContainsString('data-item-id="42"', $sanitized);
        $this->assertStringContainsString('aria-label="Poster card"', $sanitized);
        $this->assertStringNotContainsString('onclick=', $sanitized);
        $this->assertStringContainsString('target="_blank"', $sanitized);
        $this->assertStringContainsString('rel="noopener noreferrer"', $sanitized);
    }

    /**
     * Test sanitizer keeps iframe tags for categories that allow embedded content and pass URL whitelist checks.
     *
     * @return void
     */
    public function test_sanitizer_allows_iframe_when_embedding_is_enabled_and_url_is_whitelisted(): void
    {
        config()->set('modules.embedded_urls.whitelist', ['https://www.youtube.com/embed/']);
        config()->set('modules.embedded_urls.blacklist', []);

        $sanitized = app(ModuleRenderer::class)->sanitizeTemplate(
            '<div><iframe src="https://www.youtube.com/embed/abc123" width="560" height="315"></iframe></div>',
            true,
        );

        $this->assertStringContainsString('<iframe', $sanitized);
        $this->assertStringContainsString('src="https://www.youtube.com/embed/abc123"', $sanitized);
    }

    /**
     * Test sanitizer drops iframe tags when embedding is disabled or URL is blocked by policy.
     *
     * @return void
     */
    public function test_sanitizer_blocks_iframe_when_embedding_is_disabled_or_url_is_not_allowed(): void
    {
        config()->set('modules.embedded_urls.whitelist', ['https://www.youtube.com/embed/']);
        config()->set('modules.embedded_urls.blacklist', []);

        $renderer = app(ModuleRenderer::class);

        $blockedByCategory = $renderer->sanitizeTemplate(
            '<div><iframe src="https://www.youtube.com/embed/abc123"></iframe></div>',
            false,
        );
        $blockedByPolicy = $renderer->sanitizeTemplate(
            '<div><iframe src="https://example.com/embed/abc123"></iframe></div>',
            true,
        );

        $this->assertStringNotContainsString('<iframe', strtolower($blockedByCategory));
        $this->assertStringNotContainsString('<iframe', strtolower($blockedByPolicy));
    }

    /**
     * Test render html includes saved css and js assets.
     *
     * @return void
     */
    public function test_render_html_includes_saved_css_and_js_assets(): void
    {
        Storage::fake('public');
        $this->seedCatalogData();
        $author = $this->createAuthor();

        $category = ModuleCategory::create([
            'name' => 'widget',
            'slug' => 'widget',
            'is_widget' => true,
            'is_system' => true,
        ]);

        $module = Module::create([
            'name' => 'Decorated module',
            'category' => $category->id,
            'created_by' => $author->id,
            'is_active' => true,
            'revision' => 1,
            'template_path' => 'users/' . $author->username . '/modules/widget/module-2/v1.html',
            'template_checksum' => hash('sha256', 'template'),
            'style_path' => 'users/' . $author->username . '/modules/widget/module-2/v1.css',
            'style_checksum' => hash('sha256', 'style'),
            'script_path' => 'users/' . $author->username . '/modules/widget/module-2/v1.js',
            'script_checksum' => hash('sha256', 'script'),
            'data_schema' => [],
            'module_data' => [],
            'data_source' => [],
        ]);

        Storage::disk('public')->put($module->template_path, '<section><h3>{{ module.name }}</h3></section>');
        Storage::disk('public')->put($module->style_path, '.module-title { color: red; }');
        Storage::disk('public')->put($module->script_path, 'console.log("module-ready");');

        $html = app(ModuleRenderer::class)->renderHtml($module);
        preg_match('/data-module-scope="([^"]+)"/', $html, $matches);
        $scopeSelector = '[data-module-scope="' . ($matches[1] ?? '') . '"]';

        $this->assertStringContainsString('<style>', $html);
        $this->assertNotSame('', $matches[1] ?? '');
        $this->assertStringContainsString('class="module-runtime-root"', $html);
        $this->assertMatchesRegularExpression(
            '/'. preg_quote($scopeSelector, '/') . '\s+\.module-title\s*\{\s*color:\s*red;\s*\}/',
            $html,
        );
        $this->assertStringContainsString('Decorated module', $html);
        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('const host = document.querySelector(', $html);
        $this->assertStringContainsString('document: scopedDocument', $html);
        $this->assertStringContainsString('console.log("module-ready");', $html);
    }

    /**
     * Test isolated runtime output keeps a full-cell iframe contract.
     *
     * @return void
     */
    public function test_render_isolated_html_uses_full_height_runtime_contract(): void
    {
        Storage::fake('public');
        $this->seedCatalogData();
        $author = $this->createAuthor();

        $category = ModuleCategory::create([
            'name' => 'widget',
            'slug' => 'widget',
            'is_widget' => true,
            'is_system' => true,
        ]);

        $module = Module::create([
            'name' => 'Runtime fill module',
            'category' => $category->id,
            'created_by' => $author->id,
            'is_active' => true,
            'revision' => 1,
            'template_path' => 'users/' . $author->username . '/modules/widget/module-4/v1.html',
            'template_checksum' => hash('sha256', 'template'),
            'data_schema' => [],
            'module_data' => [],
            'data_source' => [],
        ]);

        Storage::disk('public')->put($module->template_path, '<section class="fill-test">{{ module.name }}</section>');

        $html = app(ModuleRenderer::class)->renderIsolatedHtml($module);

        $this->assertStringContainsString('class="module-runtime-frame', $html);
        $this->assertStringContainsString('class=&quot;module-runtime-root&quot;', $html);
        $this->assertStringContainsString('class=&quot;module-runtime-fill&quot;', $html);
        $this->assertStringContainsString('html,body{margin:0;padding:0;width:100%;height:100%', $html);
    }

    /**
     * Test renderer scopes global CSS selectors back to the module root.
     *
     * @return void
     */
    public function test_render_html_scopes_global_css_selectors_to_the_module_root(): void
    {
        Storage::fake('public');
        $this->seedCatalogData();
        $author = $this->createAuthor();

        $category = ModuleCategory::create([
            'name' => 'widget',
            'slug' => 'widget',
            'is_widget' => true,
            'is_system' => true,
        ]);

        $module = Module::create([
            'name' => 'Scoped styles module',
            'category' => $category->id,
            'created_by' => $author->id,
            'is_active' => true,
            'revision' => 1,
            'template_path' => 'users/' . $author->username . '/modules/widget/module-3/v1.html',
            'template_checksum' => hash('sha256', 'template'),
            'style_path' => 'users/' . $author->username . '/modules/widget/module-3/v1.css',
            'style_checksum' => hash('sha256', 'style'),
            'data_schema' => [],
            'module_data' => [],
            'data_source' => [],
        ]);

        Storage::disk('public')->put($module->template_path, '<section><h3>{{ module.name }}</h3></section>');
        Storage::disk('public')->put($module->style_path, 'body { color: red; }');

        $html = app(ModuleRenderer::class)->renderHtml($module);
        preg_match('/data-module-scope="([^"]+)"/', $html, $matches);
        $scopeSelector = '[data-module-scope="' . ($matches[1] ?? '') . '"]';

        $this->assertNotSame('', $matches[1] ?? '');
        $this->assertStringContainsString('<style>', $html);
        $this->assertMatchesRegularExpression(
            '/'. preg_quote($scopeSelector, '/') . '\s*\{\s*color:\s*red;\s*\}/',
            $html,
        );
        $this->assertStringNotContainsString('<style>' . PHP_EOL . 'body { color: red; }', $html);
    }

    /**
     * Test draft render escapes bound values.
     *
     * @return void
     */
    public function test_draft_render_escapes_bound_values(): void
    {
        $html = app(ModuleRenderer::class)->renderDraftHtml(
            templateHtml: '<section><h3>{{ module.name }}</h3></section>',
            templateCss: '',
            templateJs: '',
            moduleData: ['name' => '<script>alert(1)</script>'],
            source: [],
            schema: [],
            defaults: [],
            instanceConfig: [],
            viewer: null,
        );

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    /**
     * Test draft render supports object and array schema normalization.
     *
     * @return void
     */
    public function test_draft_render_supports_object_and_array_schema_types(): void
    {
        $html = app(ModuleRenderer::class)->renderDraftHtml(
            templateHtml: '<section><p>{{ config.options.theme }}</p>{{#config.slides}}<span>{{ . }}</span>{{/config.slides}}</section>',
            templateCss: '',
            templateJs: '',
            moduleData: ['name' => 'Complex module'],
            source: [],
            schema: [
                'options' => ['type' => 'object', 'default' => ['theme' => 'light']],
                'slides' => ['type' => 'array', 'default' => ['one', 'two']],
            ],
            defaults: [
                'options' => ['theme' => 'dark'],
                'slides' => ['alpha', 'beta'],
            ],
            instanceConfig: [],
            viewer: null,
        );

        $this->assertStringContainsString('dark', $html);
        $this->assertStringContainsString('alpha', $html);
        $this->assertStringContainsString('beta', $html);
    }

    /**
     * Seed catalog data.
     *
     * @return void
     */
    private function seedCatalogData(): void
    {
        DB::table('content_statuses')->insert([
            ['id' => 1, 'name' => 'Unpublished'],
            ['id' => 2, 'name' => 'Published'],
        ]);

        DB::table('content_types')->insert([
            [
                'id' => 2,
                'name' => 'Poster',
                'is_file' => true,
                'extension_whitelist' => json_encode(['jpg']),
                'max_size' => 1024,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Create author.
     *
     * @return User
     */
    private function createAuthor(): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Author',
            'username' => 'author_' . Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(10)) . '@example.test',
            'password' => bcrypt('password'),
        ]);
    }
}
