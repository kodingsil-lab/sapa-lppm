<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class SuratTugasPenelitianModel extends BaseModel
{
    private const REQUIRED_FIELDS = [
        'penelitian_id',
        'lokasi_penugasan',
        'tanggal_mulai',
        'tanggal_selesai',
        'dasar_penugasan',
        'uraian_tugas',
        'status',
    ];

    public function __construct()
    {
        parent::__construct();
        if ($this->shouldAutoManageSchema()) {
            $this->ensureTable();
        }
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS surat_tugas_penelitian (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                letter_id BIGINT UNSIGNED NOT NULL,
                activity_type VARCHAR(30) NOT NULL DEFAULT "penelitian",
                activity_id INT NOT NULL DEFAULT 0,
                penelitian_id INT NOT NULL,
                lokasi_penugasan VARCHAR(255) NOT NULL,
                instansi_tujuan VARCHAR(255) DEFAULT NULL,
                tanggal_mulai DATE NOT NULL,
                tanggal_selesai DATE NOT NULL,
                dasar_penugasan TEXT NOT NULL,
                uraian_tugas TEXT NOT NULL,
                keterangan TEXT DEFAULT NULL,
                file_proposal VARCHAR(255) DEFAULT NULL,
                file_instrumen VARCHAR(255) DEFAULT NULL,
                file_pendukung_lain VARCHAR(255) DEFAULT NULL,
                file_sk VARCHAR(255) DEFAULT NULL,
                nomor_surat VARCHAR(100) DEFAULT NULL,
                status VARCHAR(50) NOT NULL DEFAULT "diajukan",
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_surat_tugas_penelitian_letter (letter_id),
                KEY idx_surat_tugas_penelitian_activity (activity_id),
                KEY idx_surat_tugas_penelitian_penelitian (penelitian_id),
                KEY idx_surat_tugas_penelitian_status (status),
                CONSTRAINT fk_surat_tugas_penelitian_letter
                    FOREIGN KEY (letter_id) REFERENCES letters (id)
                    ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $checkActivityType = $pdo->query("SHOW COLUMNS FROM surat_tugas_penelitian LIKE 'activity_type'")->fetch();
        if ($checkActivityType === false) {
            $pdo->exec("ALTER TABLE surat_tugas_penelitian ADD COLUMN activity_type VARCHAR(30) NOT NULL DEFAULT 'penelitian' AFTER letter_id");
        }
        $checkActivityId = $pdo->query("SHOW COLUMNS FROM surat_tugas_penelitian LIKE 'activity_id'")->fetch();
        if ($checkActivityId === false) {
            $pdo->exec("ALTER TABLE surat_tugas_penelitian ADD COLUMN activity_id INT NOT NULL DEFAULT 0 AFTER activity_type");
            $pdo->exec("UPDATE surat_tugas_penelitian SET activity_id = penelitian_id WHERE COALESCE(activity_id, 0) = 0");
            $pdo->exec("ALTER TABLE surat_tugas_penelitian ADD KEY idx_surat_tugas_penelitian_activity (activity_id)");
        }
        $pdo->exec("UPDATE surat_tugas_penelitian SET activity_id = penelitian_id WHERE COALESCE(activity_id, 0) = 0");
        $checkFileInstrumen = $pdo->query("SHOW COLUMNS FROM surat_tugas_penelitian LIKE 'file_instrumen'")->fetch();
        if ($checkFileInstrumen === false) {
            $pdo->exec("ALTER TABLE surat_tugas_penelitian ADD COLUMN file_instrumen VARCHAR(255) DEFAULT NULL AFTER file_proposal");
        }
        $checkFilePendukung = $pdo->query("SHOW COLUMNS FROM surat_tugas_penelitian LIKE 'file_pendukung_lain'")->fetch();
        if ($checkFilePendukung === false) {
            $pdo->exec("ALTER TABLE surat_tugas_penelitian ADD COLUMN file_pendukung_lain VARCHAR(255) DEFAULT NULL AFTER file_instrumen");
        }
    }

    public function create(array $data): int
    {
        $this->requireKeys($data, array_merge(['letter_id'], self::REQUIRED_FIELDS), 'Create surat tugas');
        $payload = $this->normalizePayload($data);

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO surat_tugas_penelitian
            (letter_id, activity_type, activity_id, penelitian_id, lokasi_penugasan, instansi_tujuan, tanggal_mulai, tanggal_selesai, dasar_penugasan, uraian_tugas, keterangan, file_proposal, file_instrumen, file_pendukung_lain, file_sk, nomor_surat, status, created_at, updated_at)
            VALUES
            (:letter_id, :activity_type, :activity_id, :penelitian_id, :lokasi_penugasan, :instansi_tujuan, :tanggal_mulai, :tanggal_selesai, :dasar_penugasan, :uraian_tugas, :keterangan, :file_proposal, :file_instrumen, :file_pendukung_lain, :file_sk, :nomor_surat, :status, NOW(), NOW())'
        );
        $stmt->execute($payload);

        return (int) $pdo->lastInsertId();
    }

    public function updateByLetterId(int $letterId, array $data): void
    {
        $this->requireKeys($data, self::REQUIRED_FIELDS, 'Update surat tugas');
        $payload = $this->normalizePayload($data);
        $payload[':letter_id'] = $letterId;

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'UPDATE surat_tugas_penelitian
             SET activity_type = :activity_type,
                 activity_id = :activity_id,
                 penelitian_id = :penelitian_id,
                 lokasi_penugasan = :lokasi_penugasan,
                 instansi_tujuan = :instansi_tujuan,
                 tanggal_mulai = :tanggal_mulai,
                 tanggal_selesai = :tanggal_selesai,
                 dasar_penugasan = :dasar_penugasan,
                 uraian_tugas = :uraian_tugas,
                 keterangan = :keterangan,
                 file_proposal = :file_proposal,
                 file_instrumen = :file_instrumen,
                 file_pendukung_lain = :file_pendukung_lain,
                 file_sk = :file_sk,
                 nomor_surat = :nomor_surat,
                 status = :status,
                 updated_at = NOW()
             WHERE letter_id = :letter_id'
        );
        $stmt->execute($payload);
    }

    public function findByLetterId(int $letterId): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                stp.*,
                COALESCE(NULLIF(stp.activity_id, 0), stp.penelitian_id) AS activity_id
             FROM surat_tugas_penelitian stp
             WHERE stp.letter_id = :letter_id
             LIMIT 1'
        );
        $stmt->execute([':letter_id' => $letterId]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    private function normalizePayload(array $data): array
    {
        $penelitianId = $this->readInt($data, 'penelitian_id');
        $activityType = $this->normalizeEnum(
            $this->readString($data, 'activity_type', 'penelitian'),
            ['penelitian', 'pengabdian', 'hilirisasi'],
            'penelitian'
        );
        $activityId = $this->readInt($data, 'activity_id', $penelitianId);
        $status = $this->normalizeEnum(
            $this->readString($data, 'status', 'diajukan'),
            ['draft', 'diajukan', 'submitted', 'diverifikasi', 'approved', 'disetujui', 'rejected', 'ditolak', 'perlu_diperbaiki', 'selesai', 'surat_terbit', 'terbit'],
            'diajukan'
        );

        return [
            ':letter_id' => $this->readInt($data, 'letter_id'),
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
            ':penelitian_id' => $penelitianId,
            ':lokasi_penugasan' => $this->readString($data, 'lokasi_penugasan'),
            ':instansi_tujuan' => $this->readNullableString($data, 'instansi_tujuan'),
            ':tanggal_mulai' => $this->readString($data, 'tanggal_mulai'),
            ':tanggal_selesai' => $this->readString($data, 'tanggal_selesai'),
            ':dasar_penugasan' => $this->readString($data, 'dasar_penugasan'),
            ':uraian_tugas' => $this->readString($data, 'uraian_tugas'),
            ':keterangan' => $this->readNullableString($data, 'keterangan'),
            ':file_proposal' => $this->readNullableString($data, 'file_proposal'),
            ':file_instrumen' => $this->readNullableString($data, 'file_instrumen'),
            ':file_pendukung_lain' => $this->readNullableString($data, 'file_pendukung_lain'),
            ':file_sk' => $this->readNullableString($data, 'file_sk'),
            ':nomor_surat' => $this->readNullableString($data, 'nomor_surat'),
            ':status' => $status,
        ];
    }
}
