<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::create([
            'name' => 'Activo',
            'description' => 'El proceso está habilitado y en funcionamiento.'
        ]);
        Status::create([
            'name' => 'Inactivo',
            'description' => 'El proceso está deshabilitado y no operativo.'
        ]);
        Status::create([
            'name' => 'Pendiente',
            'description' => 'El proceso está esperando alguna acción o evento.'
        ]);
        Status::create([
            'name' => 'Completado',
            'description' => 'El proceso o tarea ha sido finalizada con éxito.'
        ]);
        Status::create([
            'name' => 'Aprobado',
            'description' => 'El proceso o solicitud ha sido validada y aceptada.'
        ]);
        Status::create([
            'name' => 'Rechazado',
            'description' => 'El proceso o solicitud ha sido denegada.'
        ]);
        Status::create([
            'name' => 'En progreso',
            'description' => 'El proceso o tarea está en curso.'
        ]);
        Status::create([
            'name' => 'Detenido',
            'description' => 'El proceso o tarea ha sido detenido o pausado.'
        ]);
        Status::create([
            'name' => 'Exitoso',
            'description' => 'La operación o proceso se completó correctamente.'
        ]);
        Status::create([
            'name' => 'Fallido',
            'description' => 'La operación o proceso no se completó correctamente.'
        ]);
        Status::create([
            'name' => 'En espera',
            'description' => 'El proceso está esperando alguna acción o evento.'
        ]);
        Status::create([
            'name' => 'Cancelado',
            'description' => 'El proceso o tarea ha sido cancelada.'
        ]);
        Status::create([
            'name' => 'Verificado',
            'description' => 'El proceso o entidad ha sido validado o confirmado.'
        ]);
        Status::create([
            'name' => 'No verificado',
            'description' => 'El proceso o entidad no ha sido validado o confirmado.'
        ]);
        Status::create([
            'name' => 'Suspendido',
            'description' => 'El proceso o cuenta ha sido suspendida temporalmente.'
        ]);
        Status::create([
            'name' => 'Nuevo',
            'description' => 'El objeto o solicitud ha sido creada recientemente.'
        ]);
        Status::create([
            'name' => 'En revisión',
            'description' => 'El proceso o solicitud está siendo revisado antes de tomar una decisión.'
        ]);
        Status::create([
            'name' => 'Resuelto',
            'description' => 'El proceso o solicitud ha sido solucionado o cerrado.'
        ]);
        Status::create([
            'name' => 'Expirado',
            'description' => 'El proceso o entidad ha alcanzado su fecha de expiración.'
        ]);
        Status::create([
            'name' => 'Confirmado',
            'description' => 'El proceso o solicitud ha sido confirmado.'
        ]);
        Status::create([
            'name' => 'No confirmado',
            'description' => 'El proceso o solicitud aún no ha sido confirmado.'
        ]);
        Status::create([
            'name' => 'Bloqueado',
            'description' => 'El proceso o entidad ha sido bloqueada temporalmente.'
        ]);
        Status::create([
            'name' => 'Planeado',
            'description' => 'El proceso o tarea ha sido planeado.'
        ]);
        Status::create([
            'name' => 'No planeado',
            'description' => 'El proceso o tarea no tiene una planificación definida.'
        ]);
    }
}
