DROP TEMPORARY TABLE IF EXISTS tmp_keep;
CREATE TEMPORARY TABLE tmp_keep AS
  SELECT MIN(id) AS id FROM fin_fiscal_years GROUP BY name;
DELETE FROM fin_fiscal_years WHERE id NOT IN (SELECT id FROM tmp_keep);
DROP TEMPORARY TABLE IF EXISTS tmp_keep;
SET @has_index := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_fiscal_years' AND INDEX_NAME = 'uniq_fiscal_name'
);
SET @sql := IF(@has_index = 0, 'ALTER TABLE fin_fiscal_years ADD UNIQUE KEY uniq_fiscal_name (name)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE fin_fiscal_years SET is_active = 0;
SET @active_id := (SELECT id FROM fin_fiscal_years WHERE CURDATE() BETWEEN start_date AND end_date LIMIT 1);
UPDATE fin_fiscal_years SET is_active = 1 WHERE id = @active_id;
SET @has_active := (SELECT COUNT(*) FROM fin_fiscal_years WHERE is_active = 1);
SET @fallback_id := (SELECT id FROM fin_fiscal_years ORDER BY start_date DESC LIMIT 1);
UPDATE fin_fiscal_years SET is_active = 1 WHERE id = @fallback_id AND @has_active = 0;
