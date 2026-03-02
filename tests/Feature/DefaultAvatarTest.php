<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Avatar\DefaultAvatar;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DefaultAvatarTest extends TestCase
{
    /**
     * Test svg data uri replaces background and text placeholders.
     *
     * @return void
     */
    public function test_svg_data_uri_replaces_background_and_text_placeholders(): void
    {
        $uri = DefaultAvatar::svgDataUri('alpha', '#123456', 'N');

        $this->assertStringStartsWith('data:image/svg+xml;charset=UTF-8,', $uri);

        $svg = urldecode((string) preg_replace('/^data:image\/svg\+xml;charset=UTF-8,/', '', $uri));
        $this->assertStringContainsString('fill="#123456"', $svg);
        $this->assertStringContainsString('>N<', $svg);
        $this->assertStringNotContainsString('__BACKGROUND__', $svg);
        $this->assertStringNotContainsString('__OBJECT__', $svg);
        $this->assertStringNotContainsString('__TEXT__', $svg);
    }

    /**
     * Test avatar url falls back to data uri when avatar file is missing.
     *
     * @return void
     */
    public function test_avatar_url_falls_back_to_data_uri_when_avatar_file_is_missing(): void
    {
        Storage::fake('public');

        $user = new User([
            'id' => 'user-test-1',
            'name' => 'Nicolas',
            'username' => 'nicolas',
            'email' => 'nicolas@example.com',
            'avatar' => 'missing.png',
        ]);

        $url = $user->avatarUrl();

        $this->assertStringStartsWith('data:image/svg+xml;charset=UTF-8,', $url);
    }

    /**
     * Test avatar url returns stored avatar when file exists.
     *
     * @return void
     */
    public function test_avatar_url_returns_stored_avatar_when_file_exists(): void
    {
        Storage::fake('public');

        $user = new User([
            'id' => 'user-test-2',
            'name' => 'Nicolas',
            'username' => 'nicolas',
            'email' => 'nicolas@example.com',
            'avatar' => 'stored.png',
        ]);

        Storage::disk('public')->put('users/nicolas/stored.png', 'avatar');

        $url = $user->avatarUrl();

        $this->assertStringContainsString('/storage/users/nicolas/stored.png', $url);
    }
}

