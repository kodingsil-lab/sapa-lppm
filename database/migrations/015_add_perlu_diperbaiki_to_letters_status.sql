-- Tambah status resmi "perlu_diperbaiki" pada kolom letters.status
-- dan normalisasi data lama agar konsisten dengan alur revisi.

ALTER TABLE letters
    MODIFY COLUMN status ENUM(
        'draft',
        'diajukan',
        'approved',
        'rejected',
        'archived',
        'submitted',
        'disetujui',
        'ditolak',
        'perlu_diperbaiki'
    ) NOT NULL DEFAULT 'draft';

UPDATE letters
SET status = 'perlu_diperbaiki',
    updated_at = NOW()
WHERE status IN ('ditolak', 'rejected')
   OR TRIM(COALESCE(status, '')) = '';
