-- 016_add_letter_type_slug_to_letters.sql
-- Menambahkan field jenis surat final agar generator tidak bergantung pada subject.

ALTER TABLE letters
  ADD COLUMN IF NOT EXISTS letter_type_slug VARCHAR(64) NULL AFTER letter_type_id;

UPDATE letters l
LEFT JOIN letter_types lt ON lt.id = l.letter_type_id
SET l.letter_type_slug = CASE
  WHEN LOWER(COALESCE(l.subject, '')) LIKE '%izin%' AND LOWER(COALESCE(l.subject, '')) LIKE '%pengabdian%' THEN 'surat_izin_pengabdian'
  WHEN LOWER(COALESCE(l.subject, '')) LIKE '%izin%' THEN 'surat_izin_penelitian'
  WHEN LOWER(COALESCE(l.subject, '')) LIKE '%tugas%' AND LOWER(COALESCE(l.subject, '')) LIKE '%pengabdian%' THEN 'surat_tugas_pengabdian'
  WHEN LOWER(COALESCE(l.subject, '')) LIKE '%tugas%' THEN 'surat_tugas_penelitian'
  WHEN LOWER(COALESCE(l.subject, '')) LIKE '%kontrak%' AND LOWER(COALESCE(l.subject, '')) LIKE '%pengabdian%' THEN 'surat_kontrak_pengabdian'
  WHEN LOWER(COALESCE(l.subject, '')) LIKE '%kontrak%' THEN 'surat_kontrak_penelitian'
  WHEN UPPER(COALESCE(lt.code, '')) = 'TUGAS' AND LOWER(COALESCE(l.subject, '')) LIKE '%pengabdian%' THEN 'surat_tugas_pengabdian'
  WHEN UPPER(COALESCE(lt.code, '')) = 'TUGAS' THEN 'surat_tugas_penelitian'
  WHEN UPPER(COALESCE(lt.code, '')) IN ('KONTRAK', 'PENGANTAR') AND LOWER(COALESCE(l.subject, '')) LIKE '%pengabdian%' THEN 'surat_kontrak_pengabdian'
  WHEN UPPER(COALESCE(lt.code, '')) IN ('KONTRAK', 'PENGANTAR') THEN 'surat_kontrak_penelitian'
  WHEN UPPER(COALESCE(lt.code, '')) = 'IZIN' AND LOWER(COALESCE(l.subject, '')) LIKE '%pengabdian%' THEN 'surat_izin_pengabdian'
  ELSE 'surat_izin_penelitian'
END
WHERE COALESCE(TRIM(l.letter_type_slug), '') = '';

CREATE INDEX idx_letters_type_slug ON letters(letter_type_slug);

