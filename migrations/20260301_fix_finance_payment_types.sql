-- Migration: 2026-03-01 Fix Duplicate "Uang Tahunan" Payment Type

-- 1. Migrate Student Bills (Tagihan)
-- Move bills from Duplicate ID 20 to Valid ID 2
UPDATE fin_student_bills SET payment_type_id = 2 WHERE payment_type_id = 20;

-- 2. Migrate Class Tariffs (Tarif Kelas)
-- Delete duplicates if target (ID 2) already exists for the class
DELETE FROM fin_class_tariffs 
WHERE payment_type_id = 20 
AND class_id IN (
    SELECT class_id FROM (SELECT class_id FROM fin_class_tariffs WHERE payment_type_id = 2) AS sub
);

-- Update remaining tariffs (ID 20 -> ID 2)
UPDATE fin_class_tariffs SET payment_type_id = 2 WHERE payment_type_id = 20;

-- 3. Rename Duplicate Payment Type
-- Mark ID 20 as duplicate to prevent future usage
UPDATE fin_payment_types 
SET name = CONCAT(name, ' (DUPLICATE - DO NOT USE)') 
WHERE id = 20 AND name NOT LIKE '%(DUPLICATE%';

-- 4. Fix Discount Account for "Uang Tahunan" (ID 2)
-- Previously pointed to "Diskon Calon Siswa" (35), changed to "Diskon Siswa" (32)
UPDATE fin_payment_types SET account_discount_id = 32 WHERE id = 2;

-- 5. Data Fix: Create Journal for Transaction SD26I000002 (Conditional)
-- This part is specific to fix one missing journal entry on 2026-03-01
-- INSERT INTO fin_journals (unit_id, journal_number, journal_date, description, reference_type, created_at)
-- SELECT unit_id, CONCAT('J', DATE_FORMAT(trans_date, '%y%m%d'), LPAD(FLOOR(RAND() * 9999), 4, '0')), trans_date, description, trans_number, NOW()
-- FROM fin_transactions WHERE trans_number = 'SD26I000002' AND NOT EXISTS (SELECT 1 FROM fin_journals WHERE reference_type = 'SD26I000002');
