<?php

namespace Tests\Feature\Screens;

use App\Models\Module;
use App\Models\ModuleCategory;
use App\Models\Role;
use App\Models\Screen;
use App\Models\ScreenStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test screen can be created with an available screensaver module.
     *
     * @return void
     */
    public function test_screen_can_be_created_with_an_available_screensaver_module(): void
    {
        $user = $this->createAdministrator();
        $status = ScreenStatus::query()->create(['name' => 'Online']);
        $module = $this->createScreensaverModule('Lobby screensaver');

        $response = $this
            ->actingAs($user)
            ->post(route('screen.store'), [
                'name' => 'Lobby screen',
                'status_id' => $status->id,
                'screensaver_module_id' => $module->id,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('screens.index'));

        $screen = Screen::query()->first();

        $this->assertNotNull($screen);
        $this->assertSame('Lobby screen', $screen->name);
        $this->assertDatabaseHas('screen_screensaver_modules', [
            'screen_id' => $screen->id,
            'module_id' => $module->id,
        ]);
    }

    /**
     * Test screen update can keep its current screensaver module.
     *
     * @return void
     */
    public function test_screen_update_can_keep_its_current_screensaver_module(): void
    {
        $user = $this->createAdministrator();
        $status = ScreenStatus::query()->create(['name' => 'Online']);
        $module = $this->createScreensaverModule('Atrium screensaver');
        $screen = Screen::query()->create([
            'name' => 'Atrium screen',
            'status_id' => $status->id,
        ]);

        $screen->screensaverModules()->sync([$module->id => ['order' => 0]]);

        $response = $this
            ->actingAs($user)
            ->patch(route('screen.update', ['screen' => $screen]), [
                'name' => 'Atrium screen updated',
                'status_id' => $status->id,
                'screensaver_module_id' => $module->id,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('screen.edit', ['screen' => $screen]));

        $this->assertSame('Atrium screen updated', $screen->refresh()->name);
        $this->assertDatabaseHas('screen_screensaver_modules', [
            'screen_id' => $screen->id,
            'module_id' => $module->id,
        ]);
    }

    /**
     * Create an administrator user for screen routes.
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
     * Create a screensaver module.
     *
     * @param string $name
     * @return Module
     */
    private function createScreensaverModule(string $name): Module
    {
        $category = ModuleCategory::query()->create([
            'name' => $name . ' category',
            'slug' => str($name)->slug()->value(),
            'is_screensaver' => true,
        ]);

        return Module::query()->create([
            'name' => $name,
            'category' => $category->id,
        ]);
    }
}
