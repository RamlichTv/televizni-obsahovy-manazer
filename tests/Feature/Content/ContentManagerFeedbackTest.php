<?php

/**
 * Feedback-focused content manager feature tests.
 *
 * These assertions protect the modal reopen state that the shared toast and modal
 * refresh now depends on for tag and content-type management.
 */

namespace Tests\Feature\Content;

use App\Models\ContentType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ContentManagerFeedbackTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful tag creation reopens the tag manager and flashes success.
     *
     * @return void
     */
    public function test_tag_creation_reopens_the_tag_manager(): void
    {
        $user = $this->createAdministrator();

        $response = $this
            ->actingAs($user)
            ->from(route('content.index'))
            ->post(route('content.tags.store'), [
                'name' => 'Sports',
                'return_modal' => 'tags',
            ]);

        $response
            ->assertRedirect(route('content.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('openContentManager', 'tags')
            ->assertSessionHas('success', __('Tag created successfully.'));
    }

    /**
     * Test failed content-type updates keep the correct modal and row context active.
     *
     * @return void
     */
    public function test_failed_content_type_update_preserves_modal_and_row_context(): void
    {
        $user = $this->createAdministrator();
        $primaryType = ContentType::query()->create([
            'name' => 'Animation',
            'is_file' => true,
            'multiple_file' => false,
            'extension_whitelist' => ['json'],
            'max_size' => 1048576,
        ]);
        $duplicateType = ContentType::query()->create([
            'name' => 'Image',
            'is_file' => true,
            'multiple_file' => false,
            'extension_whitelist' => ['png'],
            'max_size' => 1048576,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('content.index'))
            ->patch(route('content.types.update', ['type' => $primaryType]), [
                'name' => $duplicateType->name,
                'is_file' => '1',
                'multiple_file' => '0',
                'extensions' => 'json',
                'max_size_mb' => '2',
                'return_modal' => 'types',
                'editing_type_id' => (string) $primaryType->id,
            ]);

        $response
            ->assertRedirect(route('content.index'))
            ->assertSessionHasInput('return_modal', 'types')
            ->assertSessionHasInput('editing_type_id', (string) $primaryType->id)
            ->assertSessionHas('errors', function ($errors): bool {
                return $errors instanceof ViewErrorBag
                    && $errors->getBag('contentTypeManager')->has('name');
            });
    }

    /**
     * Create an administrator user for content management routes.
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
