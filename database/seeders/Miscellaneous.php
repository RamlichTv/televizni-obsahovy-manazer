<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Miscellaneous extends Seeder
{
    public function run(): void
    {
        $module_categories = [
            ['name' => 'container'],
            ['name' => 'media'],
            ['name' => 'document'],
            ['name' => 'other'],
        ];

        DB::table('module_categories')->insert($module_categories);
    }
}
