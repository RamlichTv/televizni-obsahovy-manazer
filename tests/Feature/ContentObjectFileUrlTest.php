<?php

namespace Tests\Feature;

use App\Models\ContentObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentObjectFileUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_legacy_storage_prefixed_meta_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('users/test/content/video.mp4', 'fake-video-content');

        $content = ContentObject::create([
            'name' => 'Video',
            'meta' => [
                'path' => '/storage/users/test/content/video.mp4',
                'mime_type' => 'video/mp4',
            ],
        ]);

        $this->assertSame('users/test/content/video.mp4', $content->filePath());
        $this->assertNotNull($content->fileUrl());
        $this->assertStringEndsWith('/storage/users/test/content/video.mp4', (string) $content->fileUrl());
    }

    public function test_it_keeps_absolute_meta_path_urls_unchanged(): void
    {
        $absolutePath = 'https://example.test/storage/users/test/content/video.mp4';

        $content = ContentObject::create([
            'name' => 'Video URL',
            'meta' => [
                'path' => $absolutePath,
                'mime_type' => 'video/mp4',
            ],
        ]);

        $this->assertSame($absolutePath, $content->filePath());
        $this->assertSame($absolutePath, $content->fileUrl());
    }
}
