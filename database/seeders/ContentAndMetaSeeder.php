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
            ['name' => __('Photo'),   'extension_whitelist' => json_encode(['jpg','jpeg','png','gif']), 'max_size' => 5 * $MB, 'is_file' => true,  'multiple_file' => false],
            ['name' => __('Gallery'), 'extension_whitelist' => json_encode(['jpg','jpeg','png','gif']), 'max_size' => 5 * $MB, 'is_file' => true,  'multiple_file' => true],
            ['name' => __('Poster'),  'extension_whitelist' => json_encode(['jpg','jpeg','png','svg']), 'max_size' => 5 * $MB, 'is_file' => true,  'multiple_file' => false],
            ['name' => __('Video'),   'extension_whitelist' => json_encode(['mp4','webm','mov']), 'max_size' => 100 * $MB, 'is_file' => true, 'multiple_file' => false],
            ['name' => __('Document'),'extension_whitelist' => json_encode(['pdf']), 'max_size' => 10 * $MB, 'is_file' => true, 'multiple_file' => false],
            ['name' => __('Text'),    'extension_whitelist' => null, 'max_size' => null, 'is_file' => false, 'multiple_file' => false],
        ];

        $tags = [
            ['name' => strtolower(__('Announcement'))],
            ['name' => strtolower(__('Event'))],
            ['name' => strtolower(__('Sport'))],
            ['name' => strtolower(__('Competition'))],
            ['name' => strtolower(__('News'))],
            ['name' => strtolower(__('Reportage'))],
            ['name' => strtolower(__('Promotional'))],
        ];

        DB::table('content_statuses')->insert($statuses);
        DB::table('content_types')->insert($types);
        DB::table('tags')->insert($tags);
    }
}
