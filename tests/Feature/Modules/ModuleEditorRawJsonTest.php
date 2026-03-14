<?php

namespace Tests\Feature\Modules;

use App\Models\Module;
use App\Models\ModuleCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModuleEditorRawJsonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test create form starts with blank template editors.
     *
     * @return void
     */
    public function test_create_form_starts_with_empty_template_editors(): void
    {
        $user = $this->createAdministrator();

        $this->actingAs($user)
            ->get(route('module.create'))
            ->assertOk()
            ->assertViewHas('editorViewData', function (array $editorViewData): bool {
                return data_get($editorViewData, 'templates.html') === ''
                    && data_get($editorViewData, 'templates.css') === ''
                    && data_get($editorViewData, 'templates.js') === '';
            });
    }

    /**
     * Test store persists raw JSON payloads exactly as authored.
     *
     * @return void
     */
    public function test_store_persists_raw_json_payloads(): void
    {
        Storage::fake('public');
        config()->set('modules.tailwind_build.enabled', false);

        $user = $this->createAdministrator();
        $category = $this->createWidgetCategory();

        $response = $this
            ->actingAs($user)
            ->post(route('module.store'), $this->rawEditorPayload($category->id, [
                'name' => 'Schema driven module',
                'data_schema' => json_encode([
                    'title' => ['type' => 'string', 'default' => 'Schema Title'],
                    'eyebrow' => ['type' => 'string', 'default' => 'Fresh'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'module_data' => json_encode([
                    'title' => 'Stored Title',
                    'eyebrow' => 'Saved marker',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'data_source' => json_encode([
                    'provider' => 'content_objects',
                    'limit' => 3,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $module = Module::query()->firstOrFail();

        $this->assertSame('Schema driven module', $module->name);
        $this->assertSame('Schema Title', data_get($module->data_schema, 'title.default'));
        $this->assertSame('Fresh', data_get($module->data_schema, 'eyebrow.default'));
        $this->assertSame('Stored Title', data_get($module->module_data, 'title'));
        $this->assertSame('Saved marker', data_get($module->module_data, 'eyebrow'));
        $this->assertSame('content_objects', data_get($module->data_source, 'provider'));
        $this->assertSame(3, data_get($module->data_source, 'limit'));
    }

    /**
     * Test update persists raw JSON changes and creates a new revision snapshot.
     *
     * @return void
     */
    public function test_update_persists_raw_json_payloads_and_increments_revision(): void
    {
        Storage::fake('public');
        config()->set('modules.tailwind_build.enabled', false);

        $user = $this->createAdministrator();
        $category = $this->createWidgetCategory();
        $module = Module::query()->create([
            'name' => 'Existing module',
            'category' => $category->id,
            'created_by' => $user->id,
            'revision' => 1,
            'template_path' => 'users/' . $user->username . '/modules/widget/' . fake()->uuid() . '/v1.html',
            'template_checksum' => hash('sha256', 'existing-template'),
            'data_schema' => [
                'title' => ['type' => 'string', 'default' => 'Old title'],
            ],
            'module_data' => [
                'title' => 'Old title',
            ],
            'data_source' => [
                'provider' => 'content_objects',
                'limit' => 4,
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('module.update', ['module' => $module]), $this->rawEditorPayload($category->id, [
                'name' => 'Updated module',
                'data_schema' => json_encode([
                    'title' => ['type' => 'string', 'default' => 'Updated title'],
                    'subtitle' => ['type' => 'string', 'default' => 'Updated subtitle'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'module_data' => json_encode([
                    'title' => 'Updated title',
                    'subtitle' => 'Manual subtitle',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'data_source' => json_encode([
                    'provider' => 'content_objects',
                    'limit' => 8,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('module.edit', ['module' => $module]));

        $module->refresh();

        $this->assertSame('Updated module', $module->name);
        $this->assertSame(2, (int) $module->revision);
        $this->assertSame('Updated title', data_get($module->data_schema, 'title.default'));
        $this->assertSame('Updated subtitle', data_get($module->data_schema, 'subtitle.default'));
        $this->assertSame('Manual subtitle', data_get($module->module_data, 'subtitle'));
        $this->assertSame(8, data_get($module->data_source, 'limit'));
    }

    /**
     * Test raw preview uses submitted schema defaults immediately.
     *
     * @return void
     */
    public function test_raw_preview_uses_submitted_data_schema_defaults(): void
    {
        config()->set('modules.tailwind_build.enabled', false);

        $user = $this->createAdministrator();
        $category = $this->createWidgetCategory();

        $response = $this
            ->actingAs($user)
            ->postJson(route('module.preview'), $this->rawEditorPayload($category->id, [
                'name' => 'Preview module',
                'template_html' => '<section><h1>{{ config.title }}</h1><p>{{ config.subtitle }}</p></section>',
                'data_schema' => json_encode([
                    'title' => ['type' => 'string', 'default' => 'Schema Title'],
                    'subtitle' => ['type' => 'string', 'default' => 'Preview subtitle'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'module_data' => '{}',
                'data_source' => '{}',
            ]));

        $response->assertOk();
        $response->assertJsonPath('preview_error', '');
        $this->assertStringContainsString('Schema Title', (string) $response->json('preview_html'));
        $this->assertStringContainsString('Preview subtitle', (string) $response->json('preview_html'));
    }

    /**
     * Test raw preview supports object and array schema types.
     *
     * @return void
     */
    public function test_raw_preview_supports_object_and_array_schema_types(): void
    {
        config()->set('modules.tailwind_build.enabled', false);

        $user = $this->createAdministrator();
        $category = $this->createWidgetCategory();

        $response = $this
            ->actingAs($user)
            ->postJson(route('module.preview'), $this->rawEditorPayload($category->id, [
                'name' => 'Complex preview module',
                'template_html' => '<section><p>{{ config.options.theme }}</p>{{#config.slides}}<span>{{ . }}</span>{{/config.slides}}</section>',
                'data_schema' => json_encode([
                    'options' => ['type' => 'object', 'default' => ['theme' => 'light']],
                    'slides' => ['type' => 'array', 'default' => ['one', 'two']],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'module_data' => json_encode([
                    'options' => ['theme' => 'dark'],
                    'slides' => ['alpha', 'beta'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'data_source' => '{}',
            ]));

        $response->assertOk();
        $response->assertJsonPath('preview_error', '');
        $this->assertStringContainsString('dark', (string) $response->json('preview_html'));
        $this->assertStringContainsString('alpha', (string) $response->json('preview_html'));
        $this->assertStringContainsString('beta', (string) $response->json('preview_html'));
    }

    /**
     * Create an administrator for module editor routes.
     *
     * @return User
     */
    private function createAdministrator(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'Administrator']);

        $user->roles()->attach($role);
        $user->unsetRelation('roles');

        return $user;
    }

    /**
     * Create a widget category used by editor requests.
     *
     * @return ModuleCategory
     */
    private function createWidgetCategory(): ModuleCategory
    {
        return ModuleCategory::query()->create([
            'name' => 'Widget',
            'slug' => 'widget',
            'is_widget' => true,
        ]);
    }

    /**
     * Build a baseline raw-editor payload for create/update/preview requests.
     *
     * @param int $categoryId
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function rawEditorPayload(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Raw JSON module',
            'category' => $categoryId,
            'template_html' => '<section><h1>{{ config.title }}</h1></section>',
            'template_css' => '',
            'template_js' => '',
            'module_settings_mode' => 'raw',
            'data_schema' => '{}',
            'module_data' => '{}',
            'data_source' => '{}',
            'is_active' => '1',
        ], $overrides);
    }
}
