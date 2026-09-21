# Migracion de base de datos

Guia para instalar, en orden, la estructura y los objetos relacionados con el seguimiento de etiquetas de produccion.

## 1. Requisitos previos

- Usar SQL Server cuando se necesiten los procedimientos almacenados, el tipo de tabla y el indice filtrado.
- Configurar las variables de conexion de Laravel en `.env`.
- Confirmar que ya existen las tablas que usan las llaves foraneas y los procedimientos:
  - `production_records`
  - `part_numbers`
  - `shifts`
  - `work_centers`
  - `item_classes`
  - `part_number_relations`
  - `part_number_project`
  - `projects`
- Ejecutar los comandos desde la raiz del proyecto.

## 2. Orden recomendado

El orden funcional recomendado es el siguiente:

1. Migraciones base de Laravel.
2. Migraciones de autenticacion de enero de 2025.
3. `2026_09_18_110746_create_production_labels_table.php`.
4. `2026_09_18_124555_create_numbers_table.php`.
5. `NumberSeeder` para poblar `numbers`.
6. Ejecutar `php artisan database:install-sqlserver-objects` para instalar el tipo y los procedimientos en el orden requerido.

## 3. Ejecucion de migraciones

### 3.1 Verificar el estado actual

```bash
php artisan migrate:status
```

### 3.2 Ejecutar las migraciones

```bash
php artisan migrate
```

Laravel ejecuta las migraciones por el timestamp del nombre del archivo. Por tanto, en una instalacion limpia el orden real sera:

1. `0001_01_01_000000_create_users_table.php`
2. `0001_01_01_000001_create_cache_table.php`
3. `0001_01_01_000002_create_jobs_table.php`
4. `2025_01_16_154149_add_two_factor_columns_to_users_table.php`
5. `2025_01_16_154214_create_personal_access_tokens_table.php`
6. `2026_09_18_110746_create_production_labels_table.php`
7. `2026_09_18_124555_create_numbers_table.php`

Los tipos y procedimientos no se ejecutan durante `migrate`; se instalan con el comando despues de completar las migraciones y el seeder.

## 4. Poblar la tabla `numbers`

`labels_sync` usa `numbers` para generar una fila por etiqueta. Despues de crear la tabla, ejecutar:

```bash
php artisan db:seed --class=NumberSeeder
```

El seeder elimina los registros existentes e inserta los numeros del 1 al 1000. No ejecutar este seeder durante la operacion normal si la tabla debe conservar datos agregados por otro proceso.

`DatabaseSeeder` no llama actualmente a `NumberSeeder`; por eso debe ejecutarse con `--class=NumberSeeder` o agregarse explicitamente al metodo `run()`.

## 5. Instalar tipos y procedimientos

Los tipos y procedimientos se administran fuera de las migraciones mediante este comando:

```bash
php artisan database:install-sqlserver-objects
```

El comando requiere SQL Server y ejecuta esta secuencia:

1. Crear `dbo.ProductionRecordIdList` si no existe.
2. Instalar o actualizar `dbo.labels_sync`.
3. Instalar o actualizar `dbo.labels_sync_quantity`.
4. Instalar o actualizar `dbo.get_label_tracking`.
5. Instalar o actualizar `dbo.get_label_tracking_by_workcenter`.
6. Instalar o actualizar `dbo.get_label_tracking_by_sub_component`.

Dependencias:

- `get_label_tracking.sql` depende de `dbo.ProductionRecordIdList` y de `production_labels`.
- `get_label_tracking_by_workcenter.sql` depende de `dbo.ProductionRecordIdList` y ejecuta `dbo.get_label_tracking`.
- `get_label_tracking_by_sub_component.sql` depende de `dbo.ProductionRecordIdList` y ejecuta `dbo.get_label_tracking`.

## 6. Validaciones posteriores

Ejecutar las siguientes comprobaciones en SQL Server:

```sql
SELECT TOP (5) * FROM dbo.numbers ORDER BY number;

SELECT OBJECT_ID('dbo.labels_sync', 'P') AS labels_sync_id,
       OBJECT_ID('dbo.labels_sync_quantity', 'P') AS labels_sync_quantity_id,
       OBJECT_ID('dbo.get_label_tracking', 'P') AS get_label_tracking_id,
       OBJECT_ID('dbo.get_label_tracking_by_workcenter', 'P') AS get_label_tracking_by_workcenter_id,
       OBJECT_ID('dbo.get_label_tracking_by_sub_component', 'P') AS get_label_tracking_by_sub_component_id;

SELECT TYPE_ID('dbo.ProductionRecordIdList') AS production_record_id_list_type_id;

SELECT OBJECT_ID('dbo.production_labels', 'U') AS production_labels_id;
```

Antes de ejecutar `dbo.labels_sync`, comprobar que:

- `numbers` contiene al menos los valores necesarios para el mayor numero de etiquetas.
- Existen registros validos en `production_records`, `part_numbers` y `shifts`.
- `standard_pack_quantity` es mayor que cero.
- `planned_quantity` es mayor que cero.

## 7. Incompatibilidades que deben corregirse antes de ejecutar `labels_sync`

La definicion actual de `production_labels` y el archivo `labels_sync.sql` no coinciden:

- La migracion crea la llave primaria como `id`, pero `labels_sync.sql` usa `INSERTED.production_label_id`.
- `labels_sync.sql` inserta en `label_schedule_id`, pero esa columna no aparece en la migracion.

Antes de ejecutar el procedimiento, elegir una sola convencion y hacer coincidir ambos lados. Por ejemplo, adaptar el procedimiento para usar `INSERTED.id` y eliminar `label_schedule_id` del `INSERT`, si esas son las columnas definitivas de la tabla. No ejecutar el procedimiento en produccion mientras esta diferencia siga pendiente.

## 8. Rollback

Para deshacer las migraciones de Laravel:

```bash
php artisan migrate:rollback
```

El rollback de las migraciones no elimina tipos ni procedimientos. Si es necesario eliminarlos manualmente:

```sql
DROP PROCEDURE IF EXISTS dbo.get_label_tracking_by_sub_component;
DROP PROCEDURE IF EXISTS dbo.get_label_tracking_by_workcenter;
DROP PROCEDURE IF EXISTS dbo.get_label_tracking;
DROP PROCEDURE IF EXISTS dbo.labels_sync_quantity;
DROP PROCEDURE IF EXISTS dbo.labels_sync;
DROP TYPE IF EXISTS dbo.ProductionRecordIdList;
```

El rollback de `production_labels` elimina la tabla y sus indices y llaves asociadas. Respaldar los datos antes de ejecutar cualquier rollback.
