CREATE PROCEDURE dbo.get_label_tracking
    @productionRecordList dbo.ProductionRecordIdList READONLY
AS
BEGIN
    SET NOCOUNT ON;

    SELECT   pn.number AS ProductCode,
             COALESCE (MAX(pl.production_order), pn.production_order, 99) AS ProductionOrder,
             ic.abbreviation AS ClassCode,
             ic.name AS ClassName,
             pn.standard_pack_quantity AS StandardPackNumber,
             pn.work_center_id AS WorkcenterId,
             wc.number AS WorkcenterCode,
             wc.name AS WorkcenterName,
             pr.planned_date AS PlannedDate,
             MAX(pr.created_at) AS CreatedAt,
             ROW_NUMBER() OVER (PARTITION BY pn.work_center_id, pr.planned_date ORDER BY COALESCE (MAX(pl.production_order), pn.production_order, 99), pn.number, MAX(pr.created_at)) AS ProductionSequence
    FROM     dbo.production_records AS pr
             INNER JOIN
             @productionRecordList AS fpr
             ON fpr.ProductionRecordId = pr.id
             INNER JOIN
             dbo.part_numbers AS pn
             ON pn.id = pr.part_number_id
             INNER JOIN
             dbo.work_centers AS wc
             ON wc.id = pn.work_center_id
             INNER JOIN
             dbo.item_classes AS ic
             ON ic.id = pn.item_class_id
             LEFT OUTER JOIN
             dbo.production_labels AS pl
             ON pl.production_record_id = pr.id
    GROUP BY pn.number, pn.production_order, ic.abbreviation, ic.name, pn.standard_pack_quantity, pn.work_center_id, wc.number, wc.name, pr.planned_date;
    
    -- 2. Labels
    SELECT   pr.shift_id AS ShiftId,
             pn.work_center_id AS WorkcenterId,
             pr.planned_date AS PlannedDate,
             pn.number AS ProductCode,
             COALESCE (pl.production_order, pn.production_order, 99) AS ProductionOrder,
             pl.id AS LabelId,
             pl.label_sequence AS LabelSequence,
             CONCAT(ISNULL(pr.shop_order_number, 'XXXXXXX'), '-', RIGHT('000' + CAST (pl.label_sequence AS VARCHAR (10)), 3)) AS LabelCode,
             pl.label_quantity AS LabelQuantity,
             pl.produced_quantity AS ProducedQuantity,
             pr.shop_order_number AS ShopOrderNumber,
             pl.is_produced AS LabelIsCompleted,
             CAST (CASE WHEN COALESCE (pl.label_quantity, 0) <= 0 THEN 0 WHEN COALESCE (pl.produced_quantity, 0) >= pl.label_quantity THEN 100 ELSE COALESCE (pl.produced_quantity, 0) * 100.0 / pl.label_quantity END AS DECIMAL (5, 2)) AS ProgressPercentage,
             pl.global_sequence AS GlobalSequence,
             pl.expected_completion_at AS ExpectedCompletionDatetime,
             ROW_NUMBER() OVER (PARTITION BY pn.number ORDER BY pl.expected_completion_at, pl.label_sequence) AS ProductOrder
    FROM     dbo.production_labels AS pl
             INNER JOIN
             @productionRecordList AS fpr
             ON fpr.ProductionRecordId = pl.production_record_id
             INNER JOIN
             dbo.production_records AS pr
             ON pr.id = pl.production_record_id
             INNER JOIN
             dbo.part_numbers AS pn
             ON pn.id = pr.part_number_id
    ORDER BY pn.work_center_id, pr.planned_date, pr.shift_id, ProductionOrder, ProductCode, pl.global_sequence;
END