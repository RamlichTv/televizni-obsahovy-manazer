<?php

namespace Tests\Feature\Modules;

use App\Models\Module;
use App\Models\ModuleCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModulePermissionAclTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test storing module JavaScript is rejected without the dedicated permission.
     *
     * @return void
     */
    public function test_store_rejects_template_js_without_javascript_permission(): void
    {
        Storage::fake('public');
        config()->set('modules.tailwind_build.enabled', false);

        $user = $this->createUserWithPermissions(['create_modules']);
        $category = $this->createCategory();

        $this->actingAs($user)
            ->post(route('module.store'), $this->modulePayload($category->id, [
                'template_js' => 'console.log("forbidden");',
            ]))
            ->assertSessionHasErrors('template_js');

        $this->assertDatabaseCount('modules', 0);
    }

    /**
     * Test previewing module JavaScript is rejected without the dedicated permission.
     *
     * @return void
     */
    public function test_preview_rejects_template_js_without_javascript_permission(): void
    {
        $user = $this->createUserWithPermissions(['create_modules']);
        $category = $this->createCategory();

        $this->actingAs($user)
            ->postJson(route('module.preview'), $this->modulePayload($category->id, [
                'template_js' => 'console.log("forbidden");',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_js');
    }

    /**
     * Test storing module JavaScript succeeds with the dedicated permission.
     *
     * @return void
     */
    public function test_store_accepts_template_js_with_javascript_permission(): void
    {
        Storage::fake('public');
        config()->set('modules.tailwind_build.enabled', false);

        $user = $this->createUserWithPermissions(['create_modules', 'manage_module_javascript']);
        $category = $this->createCategory();

        $this->actingAs($user)
            ->post(route('module.store'), $this->modulePayload($category->id, [
                'name' => 'JavaScript module',
                'template_js' => 'console.log("allowed");',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $module = Module::query()->firstOrFail();

        $this->assertNotNull($module->script_path);
        $this->assertSame('console.log("allowed");', Storage::disk('public')->get($module->script_path));
    }

    /**
     * Test storing into an embedded category is rejected without embedded permission.
     *
     * @return void
     */
    public function test_store_rejects_embedded_category_without_embedded_permission(): void
    {
        Storage::fake('public');
        config()->set('modules.tailwind_build.enabled', false);

        $user = $this->createUserWithPermissions(['create_modules']);
        $category = $this->createCategory(true);

        $this->actingAs($user)
            ->post(route('module.store'), $this->modulePayload($category->id))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('modules', 0);
    }

    /**
     * Test previewing an embedded category is rejected without embedded permission.
     *
     * @return void
     */
    public function test_preview_rejects_embedded_category_without_embedded_permission(): void
    {
        $user = $this->createUserWithPermissions(['create_modules']);
        $category = $this->createCategory(true);

        $this->actingAs($user)
            ->postJson(route('module.preview'), $this->modulePayload($category->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');
    }

    /**
     * Test storing into an embedded category succeeds with embedded permission.
     *
     * @return void
     */
    public function test_store_accepts_embedded_category_with_permission(): void
    {
        Storage::fake('public');
        config()->set('modules.tailwind_build.enabled', false);

        $user = $this->createUserWithPermissions(['create_modules', 'manage_embedded_modules']);
        $category = $this->createCategory(true);

        $this->actingAs($user)
            ->post(route('module.store'), $this->modulePayload($category->id, [
                'name' => 'Embedded module',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('modules', [
            'name' => 'Embedded module',
            'category' => $category->id,
        ]);
    }

    /**
     * Test create view hides embedded categories and script editing without permissions.
     *
     * @return void
     */
    public function test_create_view_filters_embedded_categories_and_script_access_without_permissions(): void
    {
        $user = $this->createUserWithPermissions(['create_modules']);
        $normalCategory = $this->createCategory();
        $embeddedCategory = $this->createCategory(true, 'Embedded');

        $this->actingAs($user)
            ->get(route('module.create'))
            ->assertOk()
            ->assertViewHas('categories', function ($categories) use ($normalCategory, $embeddedCategory): bool {
                return $categories->contains('id', $normalCategory->id)
                    && !$categories->contains('id', $embeddedCategory->id);
            })
            ->assertViewHas('editorViewData', function (array $editorViewData): bool {
                return data_get($editorViewData, 'flags.canEditScript') === false
                    && data_get($editorViewData, 'flags.embeddedAccessLocked') === false;
            });
    }

    /**
     * Test edit view becomes read-only when an embedded module is opened without permission.
     *
     * @return void
     */
    public function test_edit_view_marks_existing_embedded_module_as_locked_without_permission(): void
    {
        Storage::fake('public');
        $author = $this->createUserWithPermissions([]);
        $editor = $this->createUserWithPermissions(['edit_modules']);
        $category = $this->createCategory(true, 'Embedded');
        $module = $this->createModule($author, $category);

        $this->actingAs($editor)
            ->get(route('module.edit', ['module' => $module]))
            ->assertOk()
            ->assertViewHas('editorViewData', function (array $editorViewData): bool {
                return data_get($editorViewData, 'flags.embeddedAccessLocked') === true;
            })
            ->assertViewHas('categories', function ($categories) use ($category): bool {
                return $categories->contains('id', $category->id);
            });
    }

    /**
     * Test category updates keep the embedded flag untouched without embedded permission.
     *
     * @return void
     */
    public function test_module_category_update_preserves_allow_embedded_without_embed_permission(): void
    {
        $user = $this->createUserWithPermissions(['manage_module_categories']);
        $category = ModuleCategory::query()->create([
            'name' => 'Embedded category',
            'slug' => 'embedded-category',
            'allow_embedded' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('module.categories.update', ['category' => $category]), [
                'name' => 'Renamed category',
                'is_widget' => '0',
                'is_screensaver' => '0',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('modules.index'));

        $category->refresh();

        $this->assertSame('Renamed category', $category->name);
        $this->assertTrue((bool) $category->allow_embedded);
    }

    /**
     * Create a user with direct permissions for module ACL tests.
     *
     * @param array<int, string> $permissionNames
     * @return User
     */
    private function createUserWithPermissions(array $permissionNames): User
    {
        $user = User::factory()->create();

        foreach ($permissionNames as $permissionName) {
            DB::table('permissions')->updateOrInsert(['name' => $permissionName]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            $user->permissions()->attach($permissionIds);
            $user->unsetRelation('permissions');
        }

        return $user;
    }

    /**
     * Create a module category for ACL scenarios.
     *
     * @param bool $allowEmbedded
     * @param string $name
     * @return ModuleCategory
     */
    private function createCategory(bool $allowEmbedded = false, string $name = 'Widget'): ModuleCategory
    {
        return ModuleCategory::query()->create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)) . '-' . ($allowEmbedded ? 'embedded' : 'plain'),
            'is_widget' => true,
            'allow_embedded' => $allowEmbedded,
        ]);
    }

    /**
     * Create a persisted module with file-backed revision assets.
     *
     * @param User $author
     * @param ModuleCategory $category
     * @return Module
     */
    private function createModule(User $author, ModuleCategory $category): Module
    {
        $module = Module::query()->create([
            'name' => 'Existing embedded module',
            'category' => $category->id,
            'created_by' => $author->id,
            'is_active' => true,
            'revision' => 1,
            'template_path' => 'users/' . $author->username . '/modules/' . $category->slug . '/module-acl/v1.html',
            'template_checksum' => hash('sha256', '<section>Example</section>'),
            'style_path' => 'users/' . $author->username . '/modules/' . $category->slug . '/module-acl/v1.css',
            'style_checksum' => hash('sha256', ''),
            'script_path' => 'users/' . $author->username . '/modules/' . $category->slug . '/module-acl/v1.js',
            'script_checksum' => hash('sha256', ''),
            'data_schema' => [],
            'module_data' => [],
            'data_source' => [],
        ]);

        Storage::disk('public')->put($module->template_path, '<section>Example</section>');
        Storage::disk('public')->put($module->style_path, '');
        Storage::disk('public')->put($module->script_path, '');

        return $module;
    }

    /**
     * Build a baseline module payload for store and preview requests.
     *
     * @param int $categoryId
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function modulePayload(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'name' => 'ACL test module',
            'category' => $categoryId,
            'template_html' => '<section><h1>{{ module.name }}</h1></section>',
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
