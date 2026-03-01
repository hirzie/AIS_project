-- Deduplicate and enforce single fiscal year per name

-- 1) Add unique index on fiscal year name to prevent future duplicates
ALTER TABLE fin_fiscal_years
  ADD UNIQUE KEY uniq_fiscal_name (name);

-- 2) Remove duplicate rows, keep the smallest id per name
DELETE f1 FROM fin_fiscal_years f1
JOIN fin_fiscal_years f2
  ON f1.name = f2.name AND f1.id > f2.id;

-- 3) Ensure only one active fiscal year (based on current date)
UPDATE fin_fiscal_years SET is_active = 0;
UPDATE fin_fiscal_years
SET is_active = 1
WHERE CURDATE() BETWEEN start_date AND end_date
LIMIT 1;

-- 4) If no current period matches today, activate latest by start_date
SET @fallback_id := (
  SELECT id FROM fin_fiscal_years ORDER BY start_date DESC LIMIT 1
);
UPDATE fin_fiscal_years SET is_active = 1 WHERE id = @fallback_id AND
  (SELECT COUNT(*) FROM fin_fiscal_years WHERE is_active = 1) = 0;

