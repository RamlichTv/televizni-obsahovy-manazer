<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScreenInfoSeeder extends Seeder
{
    public function run() : void
    {
        $statuses = [
            ['name' => 'online'],
            ['name' => 'offline'],
            ['name' => 'maintenance'],
        ];

        $orients = [
            ['name' => 'landscape'],
            ['name' => 'portrait'],
        ];

        DB::table('status_types')->insert($statuses);
        DB::table('orientation_types')->insert($orients);
    }
}
