<?php

namespace Tests\Feature\Modules;

use App\Models\ContentObject;
use App\Models\Tag;
use App\Models\User;
use App\Support\Modules\DataProviders\ContentObjectsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ContentObjectsProviderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test it selects only whitelisted columns for related models.
     *
     * @return void
     */
    public function test_it_selects_only_whitelisted_columns_for_related_models(): void
    {
        Storage::fake('public');
        $this->seedCatalogData();
        Storage::disk('public')->put('content/poster.jpg', 'poster');

        $author = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Author',
            'username' => 'author_' . Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(10)) . '@example.test',
            'password' => bcrypt('password'),
        ]);

        $tag = Tag::create([
            'name' => 'Highlight',
        ]);

        $content = ContentObject::create([
            'name' => 'Visible poster',
            'type_id' => 2,
            'author_id' => $author->id,
            'description' => 'Top priority module item.',
            'status_id' => 2,
            'priority' => 10,
            'meta' => ['path' => 'content/poster.jpg'],
            'published_at' => now()->subHour(),
        ]);

        $content->tags()->attach($tag->id);

        $payload = app(ContentObjectsProvider::class)->resolve([
            'provider' => 'content_objects',
            'filters' => [
                'status_name' => 'Published',
            ],
            'order_by' => 'created_at',
            'order_direction' => 'desc',
            'limit' => 1,
        ], []);

        $item = $payload['items']->first();
        $this->assertNotNull($item);

        $itemArray = $item->toArray();
        $authorArray = $itemArray['author'] ?? [];
        $typeArray = $itemArray['type'] ?? [];
        $tagArray = $itemArray['tags'][0] ?? [];

        $this->assertArrayHasKey('id', $itemArray);
        $this->assertArrayHasKey('name', $itemArray);
        $this->assertArrayHasKey('file_url', $itemArray);
        $this->assertArrayHasKey('media_url', $itemArray);
        $this->assertArrayHasKey('has_media', $itemArray);
        $this->assertArrayNotHasKey('deleted_at', $itemArray);
        $this->assertTrue((bool) $itemArray['has_media']);
        $this->assertIsString($itemArray['media_url']);

        $this->assertSame(['id', 'name', 'username', 'avatar'], array_keys($authorArray));
        $this->assertArrayNotHasKey('password', $authorArray);
        $this->assertArrayNotHasKey('email', $authorArray);

        $this->assertSame(['id', 'name'], array_keys($typeArray));

        $this->assertArrayHasKey('id', $tagArray);
        $this->assertArrayHasKey('name', $tagArray);
        $this->assertArrayNotHasKey('created_at', $tagArray);
        $this->assertArrayNotHasKey('updated_at', $tagArray);
    }

    /**
     * Test it supports multi-value type/status name filters.
     *
     * @return void
     */
    public function test_it_filters_by_multiple_type_and_status_names(): void
    {
        $this->seedCatalogData();

        $author = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Author',
            'username' => 'author_' . Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(10)) . '@example.test',
            'password' => bcrypt('password'),
        ]);

        ContentObject::create([
            'name' => 'Published poster',
            'type_id' => 2,
            'author_id' => $author->id,
            'status_id' => 2,
            'priority' => 5,
            'meta' => ['path' => 'content/poster.jpg'],
            'published_at' => now()->subMinutes(10),
        ]);

        ContentObject::create([
            'name' => 'Unpublished photo',
            'type_id' => 3,
            'author_id' => $author->id,
            'status_id' => 1,
            'priority' => 4,
            'meta' => ['path' => 'content/photo.jpg'],
            'published_at' => now()->subMinutes(9),
        ]);

        ContentObject::create([
            'name' => 'Archived photo',
            'type_id' => 3,
            'author_id' => $author->id,
            'status_id' => 3,
            'priority' => 3,
            'meta' => ['path' => 'content/archived-photo.jpg'],
            'published_at' => now()->subMinutes(8),
        ]);

        $payload = app(ContentObjectsProvider::class)->resolve([
            'provider' => 'content_objects',
            'filters' => [
                'type_names' => ['Poster', 'Photo'],
                'status_names' => ['Published', 'Unpublished'],
            ],
            'order_by' => 'created_at',
            'order_direction' => 'asc',
            'limit' => 10,
        ], []);

        $names = $payload['items']->pluck('name')->all();

        $this->assertSame(['Published poster', 'Unpublished photo'], $names);
        $this->assertNotContains('Archived photo', $names);
    }

    /**
     * Test it filters by tag ids.
     *
     * @return void
     */
    public function test_it_filters_by_tag_ids(): void
    {
        $this->seedCatalogData();

        $author = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Author',
            'username' => 'author_' . Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(10)) . '@example.test',
            'password' => bcrypt('password'),
        ]);

        $sportsTag = Tag::create(['name' => 'Sports']);
        $newsTag = Tag::create(['name' => 'News']);

        $sportsPoster = ContentObject::create([
            'name' => 'Sports poster',
            'type_id' => 2,
            'author_id' => $author->id,
            'status_id' => 2,
            'priority' => 5,
            'meta' => ['path' => 'content/sports-poster.jpg'],
            'published_at' => now()->subMinutes(10),
        ]);
        $sportsPoster->tags()->attach($sportsTag->id);

        $newsPoster = ContentObject::create([
            'name' => 'News poster',
            'type_id' => 2,
            'author_id' => $author->id,
            'status_id' => 2,
            'priority' => 4,
            'meta' => ['path' => 'content/news-poster.jpg'],
            'published_at' => now()->subMinutes(9),
        ]);
        $newsPoster->tags()->attach($newsTag->id);

        $payload = app(ContentObjectsProvider::class)->resolve([
            'provider' => 'content_objects',
            'filters' => [
                'tag_ids' => [$sportsTag->id],
                'status_name' => 'Published',
            ],
            'order_by' => 'created_at',
            'order_direction' => 'asc',
            'limit' => 10,
        ], []);

        $names = $payload['items']->pluck('name')->all();

        $this->assertSame(['Sports poster'], $names);
        $this->assertNotContains('News poster', $names);
    }

    /**
     * Test it filters content to the current viewer when requested.
     *
     * @return void
     */
    public function test_it_filters_to_the_current_viewer_content(): void
    {
        $this->seedCatalogData();

        $viewer = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Viewer',
            'username' => 'viewer_' . Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(10)) . '@example.test',
            'password' => bcrypt('password'),
        ]);

        $otherAuthor = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Other Author',
            'username' => 'author_' . Str::lower(Str::random(6)),
            'email' => Str::lower(Str::random(10)) . '@example.test',
            'password' => bcrypt('password'),
        ]);

        ContentObject::create([
            'name' => 'Viewer published poster',
            'type_id' => 2,
            'author_id' => $viewer->id,
            'status_id' => 2,
            'priority' => 5,
            'meta' => ['path' => 'content/viewer-published.jpg'],
            'published_at' => now()->subMinutes(10),
        ]);

        ContentObject::create([
            'name' => 'Viewer unpublished photo',
            'type_id' => 3,
            'author_id' => $viewer->id,
            'status_id' => 1,
            'priority' => 4,
            'meta' => ['path' => 'content/viewer-unpublished.jpg'],
            'published_at' => now()->subMinutes(9),
        ]);

        ContentObject::create([
            'name' => 'Other author poster',
            'type_id' => 2,
            'author_id' => $otherAuthor->id,
            'status_id' => 2,
            'priority' => 3,
            'meta' => ['path' => 'content/other-author.jpg'],
            'published_at' => now()->subMinutes(8),
        ]);

        $payload = app(ContentObjectsProvider::class)->resolve([
            'provider' => 'content_objects',
            'filters' => [
                'authored_by' => 'viewer',
                'status_names' => ['Published', 'Unpublished'],
            ],
            'order_by' => 'created_at',
            'order_direction' => 'asc',
            'limit' => 10,
        ], [], $viewer);

        $names = $payload['items']->pluck('name')->all();

        $this->assertSame([
            'Viewer published poster',
            'Viewer unpublished photo',
        ], $names);
        $this->assertNotContains('Other author poster', $names);
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
            ['id' => 3, 'name' => 'Archived'],
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
            [
                'id' => 3,
                'name' => 'Photo',
                'is_file' => true,
                'extension_whitelist' => json_encode(['jpg']),
                'max_size' => 1024,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
