SET @acc_kas := (SELECT id FROM fin_accounts WHERE code = '112' LIMIT 1);
SET @acc_simpanan := (SELECT id FROM fin_accounts WHERE code = '211' LIMIT 1);
SET @default_unit := (SELECT id FROM core_units WHERE code != 'YAYASAN' ORDER BY id LIMIT 1);

INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, reference_id, created_at)
SELECT
  COALESCE(
    cp.unit_id,
    (SELECT u.id
     FROM acad_student_classes sc
     JOIN acad_classes c ON sc.class_id = c.id
     JOIN core_units u ON c.unit_id = u.id
     WHERE sc.student_id = s.student_id AND sc.status = 'ACTIVE'
     LIMIT 1),
    @default_unit
  ) AS unit_id,
  CONCAT('J', DATE_FORMAT(CURDATE(), '%y%m%d'), LPAD(FLOOR(RAND()*999999), 6, '0')) AS journal_number,
  COALESCE(s.trans_date, NOW()) AS journal_date,
  CONCAT('Tabungan ', IF(s.transaction_type = 'DEPOSIT', 'Setoran', 'Penarikan'), ' - ',
         (SELECT name FROM core_people WHERE id = s.student_id LIMIT 1)) AS description,
  'SAVINGS' AS reference_type,
  s.id AS reference_id,
  NOW() AS created_at
FROM fin_student_savings s
LEFT JOIN fin_journals j ON j.reference_type = 'SAVINGS' AND j.reference_id = s.id
LEFT JOIN core_people cp ON cp.id = s.student_id
WHERE j.id IS NULL;

INSERT INTO fin_journal_items (journal_id, account_id, debit, credit)
SELECT
  j.id AS journal_id,
  IF(s.transaction_type = 'DEPOSIT', @acc_kas, @acc_simpanan) AS account_id,
  s.amount AS debit,
  0 AS credit
FROM fin_journals j
JOIN fin_student_savings s ON j.reference_type = 'SAVINGS' AND j.reference_id = s.id
WHERE NOT EXISTS (SELECT 1 FROM fin_journal_items ji WHERE ji.journal_id = j.id)
  AND @acc_kas IS NOT NULL AND @acc_simpanan IS NOT NULL;

INSERT INTO fin_journal_items (journal_id, account_id, debit, credit)
SELECT
  j.id AS journal_id,
  IF(s.transaction_type = 'DEPOSIT', @acc_simpanan, @acc_kas) AS account_id,
  0 AS debit,
  s.amount AS credit
FROM fin_journals j
JOIN fin_student_savings s ON j.reference_type = 'SAVINGS' AND j.reference_id = s.id
WHERE NOT EXISTS (SELECT 1 FROM fin_journal_items ji WHERE ji.journal_id = j.id)
  AND @acc_kas IS NOT NULL AND @acc_simpanan IS NOT NULL;
