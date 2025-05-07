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
        $modules = ['numero de partes', 'estaciones', 'lineas', 'areas'];
        $actions = ['crear', 'ver', 'editar', 'eliminar'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "$action $module"]);
            }
        }

        // Asignar permisos
        $adminRole = Role::findByName('Administrador');
        $adminRole->syncPermissions(Permission::all());

        $gerente = Role::findByName('Gerente');
        $gerente->givePermissionTo([
            'ver numero de partes',
            'ver estaciones',
            'ver lineas',
            'ver areas',
        ]);

        // Asignar role
        $user = User::find(1);
        $user->assignRole('Administrador');
    }
}
