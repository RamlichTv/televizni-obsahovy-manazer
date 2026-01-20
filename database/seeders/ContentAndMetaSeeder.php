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
            ['name' => 'unpublished'],
            ['name' => 'published'],
            ['name' => 'archived'],
        ];

        $types = [
            ['name' => 'photo',    'extension_whitelist' => json_encode(['jpg','jpeg','png','gif']), 'max_size' => 5 * $MB],
            ['name' => 'poster',   'extension_whitelist' => json_encode(['jpg','jpeg','png','svg']), 'max_size' => 5 * $MB],
            ['name' => 'video',    'extension_whitelist' => json_encode(['mp4','webm','mov']), 'max_size' => 100 * $MB],
            ['name' => 'document', 'extension_whitelist' => json_encode(['pdf']), 'max_size' => 10 * $MB],
        ];

        $tags = [
            ['name' => 'announcement'],
            ['name' => 'event'],
            ['name' => 'sports'],
            ['name' => 'music'],
        ];

        DB::table('content_statuses')->insert($statuses);
        DB::table('content_types')->insert($types);
        DB::table('tags')->insert($tags);
    }
}
