<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/LetterHelper.php';

class ActivityOutputModel extends BaseModel
{
    private function normalizeActivityType(string $activityType): string
    {
        return strtolower(trim($activityType));
    }

    public function __construct()
    {
        parent::__construct();
        if ($this->shouldAutoManageSchema()) {
            $this->ensureTable();
        }
    }

    public function getOutputsByActivity(string $activityType, int $activityId): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                ao.id,
                ao.activity_type,
                ao.activity_id,
                ao.output_type_id,
                ao.status,
                ao.evidence_link,
                ao.evidence_notes,
                ao.evidence_file,
                ao.validated_by,
                ao.validated_at,
                ao.created_at,
                ao.updated_at,
                ot.code AS output_code,
                ot.name AS output_name
             FROM activity_outputs ao
             LEFT JOIN output_types ot ON ot.id = ao.output_type_id
             WHERE ao.activity_type = :activity_type
               AND ao.activity_id = :activity_id
             ORDER BY ao.output_type_id ASC'
        );
        $stmt->execute([
            ':activity_type' => $this->normalizeActivityType($activityType),
            ':activity_id' => $activityId,
        ]);

        return $stmt->fetchAll() ?: [];
    }

    public function findByActivityAndOutputType(string $activityType, int $activityId, int $outputTypeId): ?array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT *
             FROM activity_outputs
             WHERE activity_type = :activity_type
               AND activity_id = :activity_id
               AND output_type_id = :output_type_id
             LIMIT 1'
        );
        $stmt->execute([
            ':activity_type' => $this->normalizeActivityType($activityType),
            ':activity_id' => $activityId,
            ':output_type_id' => $outputTypeId,
        ]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function saveOrUpdate(array $payload): int
    {
        $activityType = $this->normalizeActivityType((string) ($payload['activity_type'] ?? ''));
        $activityId = (int) ($payload['activity_id'] ?? 0);
        $outputTypeId = (int) ($payload['output_type_id'] ?? 0);
        $status = strtolower(trim((string) ($payload['status'] ?? 'belum')));

        if (!in_array($activityType, ['penelitian', 'pengabdian', 'hilirisasi'], true)) {
            throw new InvalidArgumentException('activity_type tidak valid.');
        }
        if ($activityId <= 0 || $outputTypeId <= 0) {
            throw new InvalidArgumentException('activity_id/output_type_id tidak valid.');
        }
        if (!in_array($status, ['belum', 'proses', 'selesai'], true)) {
            throw new InvalidArgumentException('status tidak valid.');
        }

        $existing = $this->findByActivityAndOutputType($activityType, $activityId, $outputTypeId);
        $pdo = db_pdo();
        $params = [
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
            ':output_type_id' => $outputTypeId,
            ':status' => $status,
            ':evidence_link' => $this->normalizeNullableString($payload['evidence_link'] ?? null),
            ':evidence_notes' => $this->normalizeNullableString($payload['evidence_notes'] ?? null),
            ':evidence_file' => $this->normalizeNullableString($payload['evidence_file'] ?? null),
            ':validated_by' => isset($payload['validated_by']) ? (int) $payload['validated_by'] : null,
            ':validated_at' => $this->normalizeNullableString($payload['validated_at'] ?? null),
        ];

        if ($existing === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO activity_outputs
                    (activity_type, activity_id, output_type_id, status, evidence_link, evidence_notes, evidence_file, validated_by, validated_at, created_at, updated_at)
                 VALUES
                    (:activity_type, :activity_id, :output_type_id, :status, :evidence_link, :evidence_notes, :evidence_file, :validated_by, :validated_at, NOW(), NOW())'
            );
            $stmt->execute($params);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE activity_outputs
             SET status = :status,
                 evidence_link = :evidence_link,
                 evidence_notes = :evidence_notes,
                 evidence_file = :evidence_file,
                 validated_by = :validated_by,
                 validated_at = :validated_at,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => $params[':status'],
            ':evidence_link' => $params[':evidence_link'],
            ':evidence_notes' => $params[':evidence_notes'],
            ':evidence_file' => $params[':evidence_file'],
            ':validated_by' => $params[':validated_by'],
            ':validated_at' => $params[':validated_at'],
            ':id' => (int) ($existing['id'] ?? 0),
        ]);

        return (int) ($existing['id'] ?? 0);
    }

    public function countCompletedByActivity(string $activityType, int $activityId): int
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM activity_outputs
             WHERE activity_type = :activity_type
               AND activity_id = :activity_id
               AND LOWER(status) = :status'
        );
        $stmt->execute([
            ':activity_type' => $this->normalizeActivityType($activityType),
            ':activity_id' => $activityId,
            ':status' => 'selesai',
        ]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function ensureTable(): void
    {
        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS activity_outputs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                activity_type VARCHAR(50) NOT NULL,
                activity_id INT NOT NULL,
                output_type_id INT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT "belum",
                evidence_link VARCHAR(500) NULL,
                evidence_notes TEXT NULL,
                evidence_file VARCHAR(255) NULL,
                validated_by INT NULL,
                validated_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY uq_activity_output_unique (activity_type, activity_id, output_type_id),
                INDEX idx_activity_outputs_activity_type (activity_type),
                INDEX idx_activity_outputs_activity_id (activity_id),
                INDEX idx_activity_outputs_output_type_id (output_type_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));
        return $normalized === '' ? null : $normalized;
    }
}
