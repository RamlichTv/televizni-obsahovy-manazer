<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Miscellaneous extends Seeder
{
    public function run(): void
    {
        $moduleCategories = [
            ['name' => 'Widget', 'slug' => 'widget', 'is_widget' => true, 'is_system' => true],
            ['name' => 'Webpage', 'slug' => 'widget', 'is_widget' => false, 'is_system' => true],
            ['name' => 'Link', 'slug' => 'widget', 'is_widget' => true, 'is_system' => true],
            ['name' => 'Container', 'slug' => 'container', 'is_widget' => false, 'is_system' => true],
            ['name' => 'Carousel', 'slug' => 'carousel', 'is_widget' => false, 'is_system' => true],
            ['name' => 'List', 'slug' => 'list', 'is_widget' => false, 'is_system' => true],
        ];

        foreach ($moduleCategories as $category) {
            DB::table('module_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}

