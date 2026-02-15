<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventTypesSeeder extends Seeder
{
    public function run() : void
    {
        $severity = [
            ['name' => 'info'],
            ['name' => 'notice'],
            ['name' => 'warning'],
            ['name' => 'critical'],
        ];
        DB::table('severity_types')->insert($severity);

        $sev = DB::table('severity_types')->pluck('id', 'name');

        $types = [
            // Content
            ['name' => 'content_created',   'severity' => $sev['notice']],
            ['name' => 'content_modified',  'severity' => $sev['info']],
            ['name' => 'content_published', 'severity' => $sev['notice']],
            ['name' => 'content_archived',  'severity' => $sev['info']],
            ['name' => 'content_deleted',   'severity' => $sev['warning']],

            // Content types
            ['name' => 'content_type_created', 'severity' => $sev['notice']],
            ['name' => 'content_type_edited',  'severity' => $sev['info']],
            ['name' => 'content_type_deleted', 'severity' => $sev['warning']],

            // Screens
            ['name' => 'screen_created',         'severity' => $sev['notice']],
            ['name' => 'screen_online',          'severity' => $sev['info']],
            ['name' => 'screen_offline',         'severity' => $sev['warning']],
            ['name' => 'screen_profile_changed', 'severity' => $sev['notice']],
            ['name' => 'screen_deleted',         'severity' => $sev['critical']],

            // Profiles
            ['name' => 'profile_created', 'severity' => $sev['notice']],
            ['name' => 'profile_edited',  'severity' => $sev['info']],
            ['name' => 'profile_deleted', 'severity' => $sev['critical']],
            
            // Modules
            ['name' => 'module_created', 'severity' => $sev['notice']],
            ['name' => 'module_edited',  'severity' => $sev['info']],
            ['name' => 'module_deleted', 'severity' => $sev['warning']],

            // Module categories
            ['name' => 'module_category_created', 'severity' => $sev['notice']],
            ['name' => 'module_category_assigned',  'severity' => $sev['info']],
            ['name' => 'module_category_deleted', 'severity' => $sev['notice']],

            // Tags
            ['name' => 'tag_created',  'severity' => $sev['notice']],
            ['name' => 'tag_attached', 'severity' => $sev['info']],
            ['name' => 'tag_deleted',  'severity' => $sev['notice']],

            /// ACL
            ['name' => 'permission_assigned', 'severity' => $sev['warning']],
            ['name' => 'permission_removed',  'severity' => $sev['notice']],

            ['name' => 'role_created',  'severity' => $sev['notice']],
            ['name' => 'role_assigned', 'severity' => $sev['warning']],
            ['name' => 'role_edited',   'severity' => $sev['info']],
            ['name' => 'role_removed',  'severity' => $sev['notice']],
            ['name' => 'role_deleted',  'severity' => $sev['warning']],

            ['name' => 'user_created', 'severity' => $sev['notice']],
            ['name' => 'user_logged_in', 'severity' => $sev['info']],
            ['name' => 'user_logged_out', 'severity' => $sev['info']],
            ['name' => 'user_edited',  'severity' => $sev['info']],
            ['name' => 'user_deleted', 'severity' => $sev['critical']],

            // Security
            ['name' => 'database_accessed', 'severity' => $sev['critical']],
        ];
        DB::table('event_types')->insert($types);
    }
}
