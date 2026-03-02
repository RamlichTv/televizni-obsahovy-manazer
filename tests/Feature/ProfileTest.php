<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test profile page is displayed.
     *
     * @return void
     */
    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings');

        $response->assertOk();
    }

    /**
     * Test profile information can be updated.
     *
     * @return void
     */
    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
    }

    /**
     * Test email can be left unchanged when updating settings.
     *
     * @return void
     */
    public function test_email_can_be_left_unchanged_when_updating_settings(): void
    {
        $user = User::factory()->create();
        $email = $user->email;

        $response = $this
            ->actingAs($user)
            ->patch('/settings', [
                'name' => 'Test User',
                'email' => $email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings');

        $this->assertSame('Test User', $user->refresh()->name);
        $this->assertSame($email, $user->email);
    }

    /**
     * Test email must be unique when updating settings.
     *
     * @return void
     */
    public function test_email_must_be_unique_when_updating_settings(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings')
            ->patch('/settings', [
                'name' => 'Test User',
                'email' => $otherUser->email,
            ]);

        $response
            ->assertSessionHasErrors(['email'])
            ->assertRedirect('/settings');
    }

    /**
     * Test user can delete their account.
     *
     * @return void
     */
    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertSoftDeleted($user);
    }

    /**
     * Test correct password must be provided to delete account.
     *
     * @return void
     */
    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings')
            ->delete('/settings', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/settings');

        $this->assertNotSoftDeleted($user);
    }
}

