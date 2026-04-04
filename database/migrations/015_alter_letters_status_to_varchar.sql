-- 015_alter_letters_status_to_varchar.sql
-- Memastikan status surat mendukung nilai baru seperti surat_terbit dan lainnya.

ALTER TABLE letters
  MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'draft';

