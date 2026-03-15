<?php

namespace Tests\Feature\Screens;

use App\Models\Screen;
use App\Models\ScreenOrientation;
use App\Models\ScreenStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test screen show page renders without a profile assigned.
     *
     * @return void
     */
    public function test_screen_show_page_renders_without_a_profile(): void
    {
        $status = ScreenStatus::query()->create(['name' => 'Online']);
        $orientation = ScreenOrientation::query()->create(['name' => 'Landscape']);
        $screen = Screen::query()->create([
            'name' => 'Test screen',
            'status_id' => $status->id,
            'orientation_id' => $orientation->id,
            'auto_refresh_interval' => 5,
            'screensaver_idle_interval' => 10,
        ]);

        $response = $this->get(route('screen.show', ['screen' => $screen]));

        $response
            ->assertOk()
            ->assertSee('Screen Monitor')
            ->assertSee('Screen not configured');
    }

    /**
     * Test non-interactive screens render an inert monitor surface.
     *
     * @return void
     */
    public function test_non_interactive_screen_disables_monitor_interactions(): void
    {
        $status = ScreenStatus::query()->create(['name' => 'Online']);
        $orientation = ScreenOrientation::query()->create(['name' => 'Landscape']);
        $screen = Screen::query()->create([
            'name' => 'Passive screen',
            'status_id' => $status->id,
            'orientation_id' => $orientation->id,
            'is_interactive' => false,
        ]);

        $response = $this->get(route('screen.show', ['screen' => $screen]));

        $response
            ->assertOk()
            ->assertSee('interactiveScreen: false', false)
            ->assertSee('pointer-events-none select-none', false)
            ->assertSee('inert', false);
    }
}
