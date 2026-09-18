CREATE PROCEDURE dbo.get_label_tracking_by_sub_component
    @SubComponentId INT,
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
    
    WITH Components
    AS   (SELECT pn.id,
                 pn.number,
                 pn.item_class_id,
                 pn.work_center_id,
                 0 AS bomLevel
          FROM   dbo.part_numbers AS pn
                 INNER JOIN
                 dbo.item_classes AS ic
                 ON ic.id = pn.item_class_id
          WHERE  pn.id = @SubComponentId
                 AND ic.abbreviation IN ('F1', 'M1', 'M2', 'M3', 'M4')
          UNION ALL
          SELECT child.id,
                 child.number,
                 child.item_class_id,
                 child.work_center_id,
                 CASE ic_child.abbreviation WHEN 'M1' THEN 1 WHEN 'M2' THEN 2 WHEN 'M3' THEN 3 WHEN 'M4' THEN 4 WHEN 'F1' THEN 5 END AS bomLevel
          FROM   Components AS c
                 INNER JOIN
                 dbo.part_number_relations AS pnr
                 ON pnr.parent_part_number_id = c.id
                    AND pnr.is_active = 1
                 INNER JOIN
                 dbo.part_numbers AS child
                 ON child.id = pnr.child_part_number_id
                 INNER JOIN
                 dbo.item_classes AS ic_child
                 ON ic_child.id = child.item_class_id
          WHERE  ic_child.abbreviation IN ('F1', 'M1', 'M2', 'M3', 'M4')),
         UniqueComponents
    AS   (SELECT DISTINCT id AS PartNumberId,
                          bomLevel AS Level
          FROM   Components)
    INSERT INTO @ProductionRecords (ProductionRecordId)
    SELECT DISTINCT pr.id
    FROM   dbo.production_records AS pr
           INNER JOIN
           UniqueComponents AS uc
           ON uc.PartNumberId = pr.part_number_id
           INNER JOIN
           dbo.part_numbers AS pn
           ON pn.id = pr.part_number_id
    WHERE  pr.planned_date = @Date
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