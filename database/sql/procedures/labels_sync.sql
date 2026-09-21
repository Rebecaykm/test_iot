CREATE PROCEDURE dbo.labels_sync
    @PlannedDate DATE = NULL,
    @Opc INT = 0 OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    SET LOCK_TIMEOUT 10000;

    -- DECLARATIONS
    DECLARE @Date           AS DATE = CAST('2026-09-21' AS DATE);
    DECLARE @StartDate      AS DATE = ISNULL(@PlannedDate, CAST(GETDATE() AS DATE));
    DECLARE @LockResult     AS INT;
    DECLARE @LaborMinutes   AS SMALLINT = 576;

    SET @StartDate = IIF(@StartDate < @Date, @Date, @StartDate);

    BEGIN TRY
        BEGIN TRANSACTION;

        EXECUTE @LockResult = sp_getapplock 
            @Resource = 'labels_sync', 
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
        DROP TABLE IF EXISTS #Records;
        CREATE TABLE #Records (
            ShiftId BIGINT NOT NULL,
            WorkcenterId BIGINT NOT NULL,
            ProductionRecordId BIGINT NOT NULL,
            ProductId BIGINT NOT NULL,
            ProductionOrder SMALLINT NOT NULL,
            ShopOrder VARCHAR (50) NULL,
            PlannedQuantity DECIMAL (18, 4) NOT NULL,
            StandardPackQuantity DECIMAL (18, 4) NOT NULL,
            TotalLabels INT NOT NULL,
            PlannedDate DATE NOT NULL,
            RN INT NOT NULL,
            LaborMinutes INT NOT NULL,
            StartTime TIME (0) NOT NULL,
            EndTime TIME (0) NOT NULL,
            PRIMARY KEY CLUSTERED (ProductionRecordId)
        );
        DROP TABLE IF EXISTS #ShiftSummary;
        CREATE TABLE #ShiftSummary (
            ShiftId BIGINT NOT NULL,
            WorkcenterId BIGINT NOT NULL,
            PlannedDate DATE NOT NULL,
            TotalLabels BIGINT NOT NULL,
            LaborMinutes INT NOT NULL,
            StartTime TIME (0) NOT NULL,
            EndTime TIME (0) NOT NULL,
            MinutesPerLabel DECIMAL (18, 9) NOT NULL,
            ShiftStartAt DATETIME2 (3) NOT NULL,
            ShiftEndAt DATETIME2 (3) NOT NULL,
            PRIMARY KEY (ShiftId, WorkcenterId, PlannedDate)
        );
        DROP TABLE IF EXISTS #Labels;
        CREATE TABLE #Labels (
            ProductionRecordId BIGINT NOT NULL,
            ProductId BIGINT NOT NULL,
            ProductionOrder SMALLINT NOT NULL,
            ShopOrderNumber NVARCHAR (8) NULL,
            LabelSequence SMALLINT NOT NULL,
            LabelQuantity DECIMAL (11, 3) NOT NULL,
            StartQuantity DECIMAL (11, 3) NOT NULL,
            EndQuantity DECIMAL (11, 3) NOT NULL,
            StandardPackQuantity INT NOT NULL,
            RN INT NOT NULL,
            ShiftId BIGINT NOT NULL,
            WorkcenterId BIGINT NOT NULL,
            PlannedDate DATE NOT NULL,
            ShiftLabelSequence INT NOT NULL,
            GlobalSequence INT NOT NULL,
            ExpectedCompletionAt DATETIME NOT NULL,
            PRIMARY KEY (ProductionRecordId, LabelSequence)
        );
        -- Indexes
        CREATE NONCLUSTERED INDEX IX_Records_Order
            ON #Records(PlannedDate, WorkcenterId, RN);
        -- Insert records into #Records temporary table
        INSERT INTO #Records (ShiftId, WorkcenterId, ProductionRecordId, ProductId, ProductionOrder, ShopOrder, PlannedQuantity, StandardPackQuantity, TotalLabels, PlannedDate, RN, LaborMinutes, StartTime, EndTime)
        SELECT pr.shift_id AS ShiftId,
               pn.work_center_id AS WorkcenterId,
               pr.id AS ProductionRecordId,
               pn.id AS ProductId,
               ISNULL(pn.production_order, 99) AS ProductionOrder,
               pr.shop_order_number AS ShopOrder,
               pr.planned_quantity AS PlannedQuantity,
               pn.standard_pack_quantity AS StandardPackQuantity,
               CAST (CEILING(pr.planned_quantity / NULLIF (pn.standard_pack_quantity, 0)) AS INT) AS TotalLabels,
               pr.planned_date AS PlannedDate,
               ROW_NUMBER() OVER (PARTITION BY pn.work_center_id, pr.planned_date ORDER BY pr.shift_id, ISNULL(pn.production_order, 99), pn.number, pr.id) AS RN,
               @LaborMinutes AS LaborMinutes,
               s.start_time AS StartTime,
               s.end_time AS EndTime
        FROM   dbo.production_records AS pr
               INNER JOIN
               dbo.part_numbers AS pn
               ON pn.id = pr.part_number_id
               INNER JOIN
               dbo.shifts AS s
               ON s.id = pr.shift_id
        WHERE  pr.planned_date >= @StartDate
               AND pr.status_id <> 24 -- Exclude "Not Planned" records
               AND pr.planned_quantity > 0 -- Avoid division by zero when calculating TotalLabels
               AND pn.standard_pack_quantity > 0; -- Avoid division by zero when calculating TotalLabels.
        -- Insert summarized shift data into #ShiftSummary temporary table
        INSERT INTO #ShiftSummary (ShiftId, WorkcenterId, PlannedDate, TotalLabels, LaborMinutes, StartTime, EndTime, MinutesPerLabel, ShiftStartAt, ShiftEndAt)
        SELECT   r.ShiftId,
                 r.WorkcenterId,
                 r.PlannedDate,
                 SUM(CONVERT (BIGINT, r.TotalLabels)) AS TotalLabels,
                 MAX(r.LaborMinutes) AS LaborMinutes,
                 MIN(r.StartTime) AS StartTime,
                 MAX(r.EndTime) AS EndTime,
                 CAST (CAST (MAX(r.LaborMinutes) AS DECIMAL (18, 9)) / NULLIF (SUM(CAST (r.TotalLabels AS DECIMAL (18, 9))), 0) AS DECIMAL (18, 9)) AS MinutesPerLabel,
                 CAST (CONCAT(CONVERT (VARCHAR (10), r.PlannedDate, 120), ' ', CONVERT (VARCHAR (8), MIN(r.StartTime), 108)) AS DATETIME2 (3)) AS ShiftStartAt,
                 DATEADD(DAY, CASE WHEN MAX(r.EndTime) <= MIN(r.StartTime) THEN 1 ELSE 0 END, CAST (CONCAT(CONVERT (VARCHAR (10), r.PlannedDate, 120), ' ', CONVERT (VARCHAR (8), MAX(r.EndTime), 108)) AS DATETIME2 (3))) AS ShiftEndAt
        FROM     #Records AS r
        GROUP BY r.ShiftId, r.WorkcenterId, r.PlannedDate;
        -- Generate individual labels based on summarized shift data
        WITH GeneratedLabels
        AS   (SELECT r.ProductionRecordId,
                     r.ProductId,
                     r.ProductionOrder,
                     CONVERT (NVARCHAR (50), r.ShopOrder) AS ShopOrderNumber,
                     CONVERT (SMALLINT, n.Number) AS LabelSequence,
                     CONVERT (DECIMAL (11, 3), CASE WHEN n.Number < r.TotalLabels THEN r.StandardPackQuantity ELSE r.PlannedQuantity - (r.StandardPackQuantity * (r.TotalLabels - 1)) END) AS LabelQuantity,
                     CONVERT (DECIMAL (11, 3), ((n.Number - 1) * r.StandardPackQuantity) + 1) AS StartQuantity,
                     CONVERT (DECIMAL (11, 3), CASE WHEN n.Number < r.TotalLabels THEN n.Number * r.StandardPackQuantity ELSE r.PlannedQuantity END) AS EndQuantity,
                     CONVERT (INT, r.StandardPackQuantity) AS StandardPackQuantity,
                     r.RN,
                     r.ShiftId,
                     r.WorkcenterId,
                     r.PlannedDate,
                     ss.StartTime,
                     ss.EndTime,
                     ss.MinutesPerLabel,
                     ss.ShiftStartAt,
                     ss.ShiftEndAt
              FROM   #Records AS r
                     INNER JOIN
                     dbo.numbers AS n
                     ON n.Number <= r.TotalLabels
                     INNER JOIN
                     #ShiftSummary AS ss
                     ON ss.ShiftId = r.ShiftId
                        AND ss.WorkcenterId = r.WorkcenterId
                        AND ss.PlannedDate = r.PlannedDate),
             ShiftSequenced
        AS   (SELECT g.*,
                     ROW_NUMBER() OVER (PARTITION BY g.WorkcenterId, g.PlannedDate, g.ShiftId ORDER BY g.RN, g.ProductionRecordId, g.LabelSequence) AS ShiftLabelSequence
              FROM   GeneratedLabels AS g),
             GlobalSequenced
        AS   (SELECT s.*,
                     ROW_NUMBER() OVER (PARTITION BY s.WorkcenterId, s.PlannedDate ORDER BY s.StartTime, s.ShiftId, s.RN, s.ProductionRecordId, s.LabelSequence) AS GlobalSequence
              FROM   ShiftSequenced AS s),
             CalculatedTime
        AS   (SELECT g.*,
                     g.ShiftLabelSequence * g.MinutesPerLabel AS ElapsedMinutes
              FROM   GlobalSequenced AS g)
        -- Insert calculated label times into #Labels temporary table
        INSERT INTO #Labels (ProductionRecordId, ProductId, ProductionOrder, LabelSequence, LabelQuantity, StandardPackQuantity, RN, ShiftId, WorkcenterId, PlannedDate, ExpectedCompletionAt, ShopOrderNumber, StartQuantity, EndQuantity, ShiftLabelSequence, GlobalSequence)
        SELECT c.ProductionRecordId,
               c.ProductId,
               c.ProductionOrder,
               c.LabelSequence,
               c.LabelQuantity,
               c.StandardPackQuantity,
               c.RN,
               c.ShiftId,
               c.WorkcenterId,
               c.PlannedDate,
               CONVERT (DATETIME, CASE WHEN DATEADD(SECOND, CONVERT (BIGINT, ROUND(c.ElapsedMinutes * 60.0, 0)), c.ShiftStartAt) > c.ShiftEndAt THEN c.ShiftEndAt ELSE DATEADD(SECOND, CONVERT (BIGINT, ROUND(c.ElapsedMinutes * 60.0, 0)), c.ShiftStartAt) END) AS ExpectedCompletionAt,
               c.ShopOrderNumber,
               c.StartQuantity,
               c.EndQuantity,
               c.ShiftLabelSequence,
               CONVERT (INT, c.GlobalSequence)
        FROM   CalculatedTime AS c;
        -- Insert calculated labels into the production_labels table
        DECLARE @Inserted TABLE (
            ProductionLabelId BIGINT NOT NULL);
        INSERT INTO dbo.production_labels (production_record_id, product_id, production_order, label_sequence, label_quantity, produced_quantity, starting_quantity, ending_quantity, standard_pack_quantity, is_produced, created_at, updated_at, expected_completion_at, global_sequence)
        OUTPUT INSERTED.id INTO @Inserted (ProductionLabelId)
        SELECT l.ProductionRecordId,
               l.ProductId,
               l.ProductionOrder,
               l.LabelSequence,
               l.LabelQuantity,
               CONVERT (DECIMAL (11, 3), 0),
               l.StartQuantity,
               l.EndQuantity,
               l.StandardPackQuantity,
               CONVERT (BIT, 0),
               SYSDATETIME(),
               SYSDATETIME(),
               l.ExpectedCompletionAt,
               l.GlobalSequence
        FROM   #Labels AS l
        WHERE  NOT EXISTS (SELECT 1
                           FROM   dbo.production_labels AS pl WITH (UPDLOCK, HOLDLOCK)
                           WHERE  pl.production_record_id = l.ProductionRecordId
                                  AND pl.label_sequence = l.LabelSequence);
        -- Update existing production_labels records with new calculated values
        UPDATE pl
        SET    pl.global_sequence        = l.GlobalSequence,
               pl.expected_completion_at = l.ExpectedCompletionAt,
               pl.updated_at             = SYSDATETIME()
        FROM   dbo.production_labels AS pl
               INNER JOIN
               #Labels AS l
               ON l.ProductionRecordId = pl.production_record_id
                  AND l.LabelSequence = pl.label_sequence
        WHERE  ISNULL(pl.global_sequence, -1) <> l.GlobalSequence
               OR ISNULL(pl.expected_completion_at, CONVERT (DATETIME, '19000101')) <> l.ExpectedCompletionAt;
        -- (end block)
        
        -- Return the number of inserted production_labels records
        SELECT @Opc = COUNT(*)
        FROM   @Inserted;

        COMMIT TRANSACTION;
    END TRY
    BEGIN CATCH
        SET @Opc = -1;
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
    END CATCH
END