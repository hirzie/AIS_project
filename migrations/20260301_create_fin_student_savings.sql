-- Create fin_student_savings table if missing
CREATE TABLE IF NOT EXISTS fin_student_savings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  transaction_type ENUM('DEPOSIT','WITHDRAW') NOT NULL,
  amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance_before DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  balance_after DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  description VARCHAR(255) DEFAULT NULL,
  trans_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  request_id VARCHAR(64) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_student_date (student_id, trans_date),
  UNIQUE KEY uniq_fin_sav_req (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: add request_id unique index if not present (guard)
SET @has_idx := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fin_student_savings' AND INDEX_NAME = 'uniq_fin_sav_req'
);
SET @sql := IF(@has_idx = 0, 'ALTER TABLE fin_student_savings ADD UNIQUE KEY uniq_fin_sav_req (request_id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
