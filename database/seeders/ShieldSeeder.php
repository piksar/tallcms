<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:CmsCategory","View:CmsCategory","Create:CmsCategory","Update:CmsCategory","Delete:CmsCategory","Restore:CmsCategory","ForceDelete:CmsCategory","ForceDeleteAny:CmsCategory","RestoreAny:CmsCategory","Replicate:CmsCategory","Reorder:CmsCategory","ViewAny:CmsPage","View:CmsPage","Create:CmsPage","Update:CmsPage","Delete:CmsPage","Restore:CmsPage","ForceDelete:CmsPage","ForceDeleteAny:CmsPage","RestoreAny:CmsPage","Replicate:CmsPage","Reorder:CmsPage","ViewAny:CmsPost","View:CmsPost","Create:CmsPost","Update:CmsPost","Delete:CmsPost","Restore:CmsPost","ForceDelete:CmsPost","ForceDeleteAny:CmsPost","RestoreAny:CmsPost","Replicate:CmsPost","Reorder:CmsPost","ViewAny:TallcmsMedia","View:TallcmsMedia","Create:TallcmsMedia","Update:TallcmsMedia","Delete:TallcmsMedia","Restore:TallcmsMedia","ForceDelete:TallcmsMedia","ForceDeleteAny:TallcmsMedia","RestoreAny:TallcmsMedia","Replicate:TallcmsMedia","Reorder:TallcmsMedia","ViewAny:TallcmsMenu","View:TallcmsMenu","Create:TallcmsMenu","Update:TallcmsMenu","Delete:TallcmsMenu","Restore:TallcmsMenu","ForceDelete:TallcmsMenu","ForceDeleteAny:TallcmsMenu","RestoreAny:TallcmsMenu","Replicate:TallcmsMenu","Reorder:TallcmsMenu","ViewAny:User","View:User","Create:User","Update:User","Delete:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","View:MenuItemsManager","View:SiteSettings","View:MenuOverviewWidget"]},{"name":"administrator","guard_name":"web","permissions":[]},{"name":"editor","guard_name":"web","permissions":[]},{"name":"author","guard_name":"web","permissions":[]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (! blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            /** @var Model $roleModel */
            $roleModel = Utils::getRoleModel();
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($rolePlusPermissions as $rolePlusPermission) {
                $role = $roleModel::firstOrCreate([
                    'name' => $rolePlusPermission['name'],
                    'guard_name' => $rolePlusPermission['guard_name'],
                ]);

                if (! blank($rolePlusPermission['permissions'])) {
                    $permissionModels = collect($rolePlusPermission['permissions'])
                        ->map(fn ($permission) => $permissionModel::firstOrCreate([
                            'name' => $permission,
                            'guard_name' => $rolePlusPermission['guard_name'],
                        ]))
                        ->all();

                    $role->syncPermissions($permissionModels);
                }
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (! blank($permissions = json_decode($directPermissions, true))) {
            /** @var Model $permissionModel */
            $permissionModel = Utils::getPermissionModel();

            foreach ($permissions as $permission) {
                if ($permissionModel::whereName($permission)->doesntExist()) {
                    $permissionModel::create([
                        'name' => $permission['name'],
                        'guard_name' => $permission['guard_name'],
                    ]);
                }
            }
        }
    }
}
