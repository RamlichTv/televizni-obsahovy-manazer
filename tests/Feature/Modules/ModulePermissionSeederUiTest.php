<?php

namespace Tests\Feature\Modules;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModulePermissionSeederUiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the permission seeder registers new module ACL permissions without default Media Team grants.
     *
     * @return void
     */
    public function test_role_permission_seeder_registers_new_module_permissions_without_default_media_team_grants(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $javascriptPermissionId = DB::table('permissions')->where('name', 'manage_module_javascript')->value('id');
        $embeddedPermissionId = DB::table('permissions')->where('name', 'manage_embedded_modules')->value('id');
        $mediaRoleId = DB::table('roles')
            ->where('is_hidden', false)
            ->where('color', '#5865F2')
            ->value('id');

        $this->assertNotNull($javascriptPermissionId);
        $this->assertNotNull($embeddedPermissionId);
        $this->assertNotNull($mediaRoleId);

        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $mediaRoleId,
            'permission_id' => $javascriptPermissionId,
        ]);
        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $mediaRoleId,
            'permission_id' => $embeddedPermissionId,
        ]);
    }

    /**
     * Test role and user manager screens expose the new module permissions to ACL actors.
     *
     * @return void
     */
    public function test_role_and_user_managers_list_new_module_permissions_for_acl_actor(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = $this->createAclActor();

        $this->actingAs($actor)
            ->get(route('role.create'))
            ->assertOk()
            ->assertSee('Manage Module Javascript')
            ->assertSee('Manage Embedded Modules');

        $this->actingAs($actor)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('Manage Module Javascript')
            ->assertSee('Manage Embedded Modules');
    }

    /**
     * Test a non-admin ACL manager can assign the new module permissions to a role.
     *
     * @return void
     */
    public function test_non_admin_acl_manager_can_assign_new_module_permissions_to_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $actor = $this->createAclActor();
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['manage_module_javascript', 'manage_embedded_modules'])
            ->pluck('id')
            ->all();

        $this->actingAs($actor)
            ->post(route('role.store'), [
                'name' => 'Module Advanced Editors',
                'color' => '#077CB6',
                'is_hidden' => '0',
                'permissions' => $permissionIds,
            ])
            ->assertSessionHasNoErrors();

        $roleId = DB::table('roles')->where('name', 'Module Advanced Editors')->value('id');

        $this->assertNotNull($roleId);

        foreach ($permissionIds as $permissionId) {
            $this->assertDatabaseHas('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    /**
     * Create a non-admin user with ACL-management permissions.
     *
     * @return User
     */
    private function createAclActor(): User
    {
        $user = User::factory()->create();
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['manage_acl', 'manage_users'])
            ->pluck('id')
            ->all();

        $user->permissions()->attach($permissionIds);
        $user->unsetRelation('permissions');

        return $user;
    }
}
