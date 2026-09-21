CREATE PROCEDURE dbo.labels_sync_quantity
    @Opc INT = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    SET LOCK_TIMEOUT 10000;

    -- DECLARATIONS
    DECLARE @Date       AS DATE = CAST('2026-09-21' AS DATE);
    DECLARE @LockResult AS INT;

    BEGIN TRY
        BEGIN TRANSACTION;

        EXECUTE @LockResult = sp_getapplock 
            @Resource = 'labels_sync_quantity', 
            @LockMode = 'Exclusive',
            @LockOwner = 'Transaction',
            @LockTimeout = 10000;
        
        IF @LockResult < 0
        BEGIN
            SET @Opc = -1;
            ROLLBACK TRANSACTION;
            RETURN;
        END

        -- (block)
        -- Update produced quantities and is_produced status for production_labels based on production_records
        UPDATE lbl
        SET    lbl.produced_quantity = x.new_produced_quantity,
               lbl.is_produced       = CASE WHEN x.new_produced_quantity >= lbl.label_quantity THEN 1 ELSE 0 END, -- Nota: Asumo que la meta por etiqueta se compara contra su propia cantidad o ending_quantity según tu lógica original
               lbl.updated_at        = SYSDATETIME()
        FROM   dbo.production_labels AS lbl
               INNER JOIN
               dbo.production_records AS pr
               ON pr.id = lbl.production_record_id CROSS APPLY (VALUES (CASE WHEN pr.produced_quantity <= 0 THEN 0 WHEN pr.produced_quantity < lbl.starting_quantity THEN 0 WHEN pr.produced_quantity >= lbl.ending_quantity THEN lbl.label_quantity ELSE pr.produced_quantity - lbl.starting_quantity + 1 END)) AS x(new_produced_quantity)
        WHERE  pr.planned_date >= @Date
               AND lbl.is_produced = 0
               AND lbl.produced_quantity <> x.new_produced_quantity;
        -- (end block)

        -- Return the number of updated production_labels records
        SET @Opc = @@ROWCOUNT;
        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        SET @Opc = -1;
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
    END CATCH
END