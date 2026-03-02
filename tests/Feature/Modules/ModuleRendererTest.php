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
            'config_schema' => [],
            'default_config' => ['title' => 'Poster list'],
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
            'config_schema' => [],
            'default_config' => [],
            'data_source' => [],
        ]);

        Storage::disk('public')->put($module->template_path, '<section><h3>{{ module.name }}</h3></section>');
        Storage::disk('public')->put($module->style_path, '.module-title { color: red; }');
        Storage::disk('public')->put($module->script_path, 'console.log("module-ready");');

        $html = app(ModuleRenderer::class)->renderHtml($module);

        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('.module-title { color: red; }', $html);
        $this->assertStringContainsString('Decorated module', $html);
        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('console.log("module-ready");', $html);
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

