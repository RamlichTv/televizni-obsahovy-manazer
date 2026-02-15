<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentAndMetaSeeder extends Seeder
{
    public function run() : void
    {
        $MB = pow(2, 20);

        $statuses = [
            ['name' => 'Unpublished'],
            ['name' => 'Published'],
            ['name' => 'Archived'],
            ['name' => 'Deleted'],
        ];

        $types = [
            ['name' => 'Photo',    'extension_whitelist' => json_encode(['jpg','jpeg','png','gif']), 'max_size' => 5 * $MB, 'is_file' => true],
            ['name' => 'Poster',   'extension_whitelist' => json_encode(['jpg','jpeg','png','svg']), 'max_size' => 5 * $MB, 'is_file' => true],
            ['name' => 'Video',    'extension_whitelist' => json_encode(['mp4','webm','mov']), 'max_size' => 100 * $MB, 'is_file' => true],
            ['name' => 'Document', 'extension_whitelist' => json_encode(['pdf']), 'max_size' => 10 * $MB, 'is_file' => true],
            ['name' => 'Text',     'extension_whitelist' => null, 'max_size' => null, 'is_file' => false],
        ];

        $tags = [
            ['name' => 'announcement'],
            ['name' => 'event'],
            ['name' => 'sports'],
            ['name' => 'competition'],
            ['name' => 'news'],
            ['name' => 'report'],
            ['name' => 'promotional'],
        ];

        DB::table('content_statuses')->insert($statuses);
        DB::table('content_types')->insert($types);
        DB::table('tags')->insert($tags);
    }
}
