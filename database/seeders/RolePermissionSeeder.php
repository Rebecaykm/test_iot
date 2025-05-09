<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles
        $roles = ['Administrador', 'Gerente', 'Lider', 'Soporte', 'Operador'];

        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
        }

        // Crear permisos
        $modules = ['part numbers', 'work centers', 'lines', 'areas', 'users', 'roles', 'permissions', 'tag types', 'tags'];
        $actions = ['create', 'view', 'edit', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "$action $module"]);
            }
        }

        Permission::create(['name' => 'view work centers map']);

        // Asignar permisos
        $adminRole = Role::findByName('Administrador');
        $adminRole->syncPermissions(Permission::all());

        // Asignar role
        $user = User::find(1);
        $user->assignRole('Administrador');
    }
}
