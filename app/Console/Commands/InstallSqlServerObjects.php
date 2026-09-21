<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class InstallSqlServerObjects extends Command
{
    protected $signature = 'database:install-objects';

    protected $description = 'Instala o actualiza el tipo y los procedimientos de SQL Server';

    public function handle(): int
    {
        $object = 'conexion SQL Server';
        $path = null;

        try {
            $connection = DB::connection();

            if ($connection->getDriverName() !== 'sqlsrv') {
                $this->error('Este comando requiere una conexion SQL Server.');

                return self::FAILURE;
            }

            $object = 'dbo.ProductionRecordIdList';
            $this->createProductionRecordIdListType($connection);

            foreach ($this->procedureFiles() as $name => $procedurePath) {
                $object = $name;
                $path = $procedurePath;
                $this->installProcedure($connection, $name, $procedurePath);
            }

            $this->info('Objetos SQL Server instalados correctamente.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);

            $this->error("Fallo al instalar {$object}.");

            if ($path !== null) {
                $this->line("Archivo: {$path}");
            }

            $this->line('Tipo: '.$exception::class);
            $this->line('Mensaje: '.$exception->getMessage());

            if ($exception->getPrevious() !== null) {
                $this->line('Causa: '.$exception->getPrevious()->getMessage());
            }

            $this->line('El detalle completo fue registrado en storage/logs/laravel.log.');

            return self::FAILURE;
        }
    }

    private function createProductionRecordIdListType(Connection $connection): void
    {
        $exists = $connection->selectOne(
            "SELECT TYPE_ID(N'dbo.ProductionRecordIdList') AS type_id"
        );

        if ($exists?->type_id !== null) {
            $this->line('Tipo dbo.ProductionRecordIdList: ya existe.');

            return;
        }

        $connection->unprepared(<<<'SQL'
            EXEC(N'
                CREATE TYPE [dbo].[ProductionRecordIdList] AS TABLE (
                    [ProductionRecordId] BIGINT NOT NULL,
                    PRIMARY KEY CLUSTERED ([ProductionRecordId] ASC)
                )
            ')
        SQL);

        $this->info('Tipo dbo.ProductionRecordIdList: creado.');
    }

    /**
     * @return array<string, string>
     */
    private function procedureFiles(): array
    {
        return [
            'dbo.labels_sync' => database_path('sql/procedures/labels_sync.sql'),
            'dbo.labels_sync_quantity' => database_path('sql/procedures/labels_sync_quantity.sql'),
            'dbo.get_label_tracking' => database_path('sql/procedures/get_label_tracking.sql'),
            'dbo.get_label_tracking_by_workcenter' => database_path('sql/procedures/get_label_tracking_by_workcenter.sql'),
            'dbo.get_label_tracking_by_sub_component' => database_path('sql/procedures/get_label_tracking_by_sub_component.sql'),
        ];
    }

    private function installProcedure(Connection $connection, string $name, string $path): void
    {
        if (! is_file($path)) {
            throw new RuntimeException("No existe el archivo del procedimiento {$path}.");
        }

        $sql = file_get_contents($path);

        if ($sql === false || ! preg_match('/^CREATE\\s+PROCEDURE\\b/i', ltrim($sql))) {
            throw new RuntimeException("El archivo {$path} no contiene un CREATE PROCEDURE valido.");
        }

        $sql = preg_replace('/^CREATE\\s+PROCEDURE\\b/i', 'CREATE OR ALTER PROCEDURE', ltrim($sql), 1);

        if ($sql === null) {
            throw new RuntimeException("No se pudo preparar {$name} para SQL Server.");
        }

        $connection->unprepared($sql);
        $this->info("{$name}: instalado/actualizado.");
    }
}
