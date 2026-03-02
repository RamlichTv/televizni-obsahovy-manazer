<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run() : void
    {
        $roles = [
            ['name' => 'Administrator', 'color' => '#ED4245', 'is_hidden' => true, 'created_by' => null],
            ['name' => 'Media Team', 'color' => '#5865F2', 'is_hidden' => false, 'created_by' => null],
        ];

        $perms = [
            ['name' => 'create_content'],
            ['name' => 'edit_content'],
            ['name' => 'publish_content'],
            ['name' => 'delete_content'],
            ['name' => 'bypass_max_size'],
            ['name' => 'manage_archived'],

            ['name' => 'manage_content_types'],

            ['name' => 'manage_screens'],

            ['name' => 'create_profiles'],
            ['name' => 'edit_profiles'],
            ['name' => 'delete_profiles'],

            ['name' => 'create_modules'],
            ['name' => 'edit_modules'],
            ['name' => 'delete_modules'],
            
            ['name' => 'manage_module_categories'],
            
            ['name' => 'manage_tags'],

            ['name' => 'manage_acl'],
            ['name' => 'manage_users'],

            ['name' => 'access_database'],

            ['name' => 'view_events'],
        ];

        DB::table('roles')->insert($roles);
        DB::table('permissions')->insert($perms);

        $mediaRoleId = DB::table('roles')->where('name', 'Media Team')->value('id');
        $mediaPerms = DB::table('permissions')
            ->whereIn('name', [
                'create_content',
                'publish_content',
            ])->pluck('id');

        foreach ($mediaPerms as $pid) {
            DB::table('role_permissions')->insert([
                'role_id' => $mediaRoleId,
                'permission_id' => $pid,
            ]);
        }
    }
}
