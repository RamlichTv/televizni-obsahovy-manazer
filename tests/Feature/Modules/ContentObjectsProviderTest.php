<?php

namespace Tests\Feature\Modules;

use App\Models\ContentObject;
use App\Models\Tag;
use App\Models\User;
use App\Support\Modules\DataProviders\ContentObjectsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->seedCatalogData();

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
        $this->assertArrayNotHasKey('deleted_at', $itemArray);

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
}

