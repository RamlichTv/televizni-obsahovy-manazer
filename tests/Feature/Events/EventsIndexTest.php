<?php

namespace Tests\Feature\Events;

use App\Models\ContentType;
use App\Models\Event;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventsIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test events index renders tag and content-type rows with valid content links.
     *
     * @return void
     */
    public function test_events_index_renders_related_content_links_without_missing_routes(): void
    {
        $user = $this->createAdministrator();
        $severityId = DB::table('severity_types')->insertGetId(['name' => 'Info']);
        $eventTypeId = DB::table('event_types')->insertGetId([
            'name' => 'content_edited',
            'severity' => $severityId,
        ]);
        $tag = Tag::query()->create(['name' => 'Featured']);
        $contentType = ContentType::query()->create(['name' => 'Announcement']);

        Event::query()->create([
            'message' => 'Content metadata updated.',
            'type_id' => $eventTypeId,
            'user_id' => $user->id,
            'related_tag' => $tag->id,
            'related_content_type' => $contentType->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('events.index'));

        $response
            ->assertOk()
            ->assertSee('Featured')
            ->assertSee('Announcement')
            ->assertSee(route('content.library', ['tag_ids' => [$tag->id]]), false)
            ->assertSee(route('content.library', ['type_ids' => [$contentType->id]]), false);
    }

    /**
     * Create an administrator user for event routes.
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
}
