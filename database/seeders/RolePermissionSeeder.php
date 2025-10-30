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
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Crear permisos
        $modules = [
            'part numbers',
            'work centers',
            'lines',
            'areas',
            'users',
            'roles',
            'permissions',
            'tag types',
            'tags',
            'statuses',
            'shifts',
            'clients',
            'projects',
            'production records',
            'material validations',
            'type scraps',
            'scraps',
            'scrap records',
            'type line stoppages',
            'line stoppages',
            'line stoppage records'
        ];

        $actions = ['create', 'view', 'edit', 'delete'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "$action $module"]);
            }
        }

        Permission::firstOrCreate(['name' => 'view work centers map']);

        // Asignar permisos al admin
        $adminRole = Role::where('name', 'Administrador')->first();
        $adminRole->syncPermissions(Permission::all());

        // Asignar role al usuario admin
        $user = User::find(1);
        if ($user && !$user->hasRole('Administrador')) {
            $user->assignRole('Administrador');
        }
    }
}
