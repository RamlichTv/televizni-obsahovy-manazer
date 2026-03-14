<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DefaultUserSeeder extends Seeder
{
    public function run() : void
    {
        $userId = Str::uuid()->toString();
        $adminRoleId = DB::table('roles')->where('name', 'Administrator')->value('id');

        DB::table('users')->insert([
            'id'       => $userId,
            'name'     => __('Administrator'),
            'username' => 'admin',
            'email'    => 'vanicek@spsjc.cz',
            'password' => Hash::make('Aa123456'), //⚠️!!CHANGE!!⚠️
        ]);

        DB::table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $adminRoleId,
        ]);
    }
}
