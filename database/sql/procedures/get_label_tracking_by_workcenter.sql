CREATE PROCEDURE dbo.get_label_tracking_by_workcenter
    @WorkcenterId INT,
    @Date DATE,
    @CustomerId INT = NULL,
    @ProjectIds NVARCHAR(500) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    SET @Date = IIF (@Date < '2026-09-21', '2026-09-21', @Date);

    DECLARE @Ids TABLE (
        Id INT NOT NULL PRIMARY KEY);
    
    INSERT INTO @Ids (Id)
    SELECT DISTINCT TRY_CONVERT (INT, LTRIM(RTRIM(value)))
    FROM   STRING_SPLIT (@ProjectIds, ',')
    WHERE  TRY_CONVERT (INT, LTRIM(RTRIM(value))) IS NOT NULL;
    
    DECLARE @ProductionRecords AS dbo.ProductionRecordIdList;
    
    INSERT INTO @ProductionRecords (ProductionRecordId)
    SELECT pr.id
    FROM   dbo.production_records AS pr
           INNER JOIN
           dbo.part_numbers AS pn
           ON pn.id = pr.part_number_id
    WHERE  pr.planned_date = @Date
           AND pn.work_center_id = @WorkcenterId
           AND (@CustomerId IS NULL
                OR EXISTS (SELECT 1
                           FROM   dbo.part_number_project AS pnp
                                  INNER JOIN
                                  dbo.projects AS pj
                                  ON pj.id = pnp.project_id
                           WHERE  pnp.part_number_id = pn.id
                                  AND pj.client_id = @CustomerId))
           AND (NOT EXISTS (SELECT 1
                            FROM   @Ids)
                OR EXISTS (SELECT 1
                           FROM   dbo.part_number_project AS pnp
                                  INNER JOIN
                                  @Ids AS ids
                                  ON ids.Id = pnp.project_id
                           WHERE  pnp.part_number_id = pn.id));
    
    EXECUTE dbo.get_label_tracking @ProductionRecords;
END;