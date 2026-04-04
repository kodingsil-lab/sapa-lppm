ALTER TABLE surat_tugas_penelitian
    ADD COLUMN IF NOT EXISTS activity_id INT NOT NULL DEFAULT 0 AFTER activity_type;

UPDATE surat_tugas_penelitian
SET activity_id = penelitian_id
WHERE COALESCE(activity_id, 0) = 0;

ALTER TABLE surat_tugas_penelitian
    ADD INDEX IF NOT EXISTS idx_surat_tugas_penelitian_activity (activity_id);

