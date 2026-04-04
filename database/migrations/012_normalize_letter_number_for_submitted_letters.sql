-- Normalisasi data lama:
-- kosongkan letter_number untuk surat yang masih diajukan tetapi sudah terlanjur punya nomor.

START TRANSACTION;

-- Pastikan kolom letter_number boleh NULL agar pola "nomor dibuat saat approve" bisa dipakai.
ALTER TABLE letters
  MODIFY COLUMN letter_number VARCHAR(100) NULL;

-- Bersihkan data kosong lama ke NULL (agar tidak bentrok unique key).
UPDATE letters
SET letter_number = NULL
WHERE letter_number IS NOT NULL
  AND TRIM(letter_number) = '';

-- Cek kandidat sebelum update
SELECT
  id,
  letter_number,
  subject,
  status,
  letter_date
FROM letters
WHERE LOWER(status) = 'diajukan'
  AND letter_number IS NOT NULL
  AND TRIM(letter_number) <> ''
ORDER BY id ASC;

-- Update normalisasi
UPDATE letters
SET letter_number = NULL,
    updated_at = NOW()
WHERE LOWER(status) = 'diajukan'
  AND letter_number IS NOT NULL
  AND TRIM(letter_number) <> '';

-- Cek hasil setelah update
SELECT
  id,
  letter_number,
  subject,
  status,
  letter_date
FROM letters
WHERE LOWER(status) = 'diajukan'
ORDER BY id ASC;

COMMIT;
