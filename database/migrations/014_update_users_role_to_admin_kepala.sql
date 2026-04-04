-- Tambah role baru: kepala_lppm dan admin.
-- Jalankan script ini sekali di database existing.

ALTER TABLE users
    MODIFY COLUMN role ENUM('dosen', 'kepala_lppm', 'admin', 'admin_lppm') NOT NULL DEFAULT 'dosen';

UPDATE users
SET role = 'kepala_lppm'
WHERE role = 'admin_lppm';

ALTER TABLE users
    MODIFY COLUMN role ENUM('dosen', 'kepala_lppm', 'admin') NOT NULL DEFAULT 'dosen';
