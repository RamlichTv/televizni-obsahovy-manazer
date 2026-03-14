<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Miscellaneous extends Seeder
{
    public function run(): void
    {
        $moduleCategories = [
            ['name' => __('Widget'), 'slug' => 'widget', 'is_widget' => true, 'is_screensaver' => false, 'allow_embedded' => false, 'is_system' => true],
            ['name' => __('Webpage'), 'slug' => 'webpage', 'is_widget' => false, 'is_screensaver' => false, 'allow_embedded' => true, 'is_system' => true],
            ['name' => __('Link'), 'slug' => 'link', 'is_widget' => true, 'is_screensaver' => false, 'allow_embedded' => false, 'is_system' => true],
            ['name' => __('Container'), 'slug' => 'container', 'is_widget' => false, 'is_screensaver' => false, 'allow_embedded' => false, 'is_system' => true],
            ['name' => __('Carousel'), 'slug' => 'carousel', 'is_widget' => false, 'is_screensaver' => false, 'allow_embedded' => false, 'is_system' => true],
            ['name' => __('List'), 'slug' => 'list', 'is_widget' => false, 'is_screensaver' => false, 'allow_embedded' => false, 'is_system' => true],
            ['name' => __('Screensaver'), 'slug' => 'screensaver', 'is_widget' => false, 'is_screensaver' => true, 'allow_embedded' => false, 'is_system' => true],
        ];

        foreach ($moduleCategories as $category) {
            DB::table('module_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
