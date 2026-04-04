<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class ActivityMemberModel extends BaseModel
{
    private ?bool $relationTableAvailable = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function syncMembers(string $activityType, int $activityId, int $ownerUserId, array $members): void
    {
        $activityType = $this->normalizeActivityType($activityType);
        if ($activityType === '' || $activityId <= 0) {
            return;
        }
        if (!$this->relationTableAvailable()) {
            throw new RuntimeException('Tabel relasi anggota belum tersedia. Jalankan script database/sql/activity_member_relations.sql terlebih dahulu.');
        }

        $pdo = db_pdo();
        $pdo->beginTransaction();

        try {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM activity_member_relations
                 WHERE activity_type = :activity_type
                   AND activity_id = :activity_id'
            );
            $deleteStmt->execute([
                ':activity_type' => $activityType,
                ':activity_id' => $activityId,
            ]);

            $insertStmt = $pdo->prepare(
                'INSERT INTO activity_member_relations
                 (activity_type, activity_id, owner_user_id, member_user_id, member_name, created_at, updated_at)
                 VALUES
                 (:activity_type, :activity_id, :owner_user_id, :member_user_id, :member_name, NOW(), NOW())'
            );

            $seen = [];
            foreach ($members as $member) {
                $name = trim((string) ($member['name'] ?? ''));
                $memberUserId = (int) ($member['user_id'] ?? 0);

                if ($name === '') {
                    continue;
                }
                if ($memberUserId > 0 && $memberUserId === $ownerUserId) {
                    continue;
                }

                $dedupeKey = $memberUserId > 0
                    ? 'user:' . $memberUserId
                    : 'name:' . strtolower(preg_replace('/\s+/', ' ', $name) ?? $name);
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;

                $insertStmt->execute([
                    ':activity_type' => $activityType,
                    ':activity_id' => $activityId,
                    ':owner_user_id' => $ownerUserId,
                    ':member_user_id' => $memberUserId > 0 ? $memberUserId : null,
                    ':member_name' => $name,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function deleteByActivity(string $activityType, int $activityId): void
    {
        $activityType = $this->normalizeActivityType($activityType);
        if ($activityType === '' || $activityId <= 0 || !$this->relationTableAvailable()) {
            return;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'DELETE FROM activity_member_relations
             WHERE activity_type = :activity_type
               AND activity_id = :activity_id'
        );
        $stmt->execute([
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
        ]);
    }

    public function getMembersForActivity(string $activityType, int $activityId): array
    {
        $activityType = $this->normalizeActivityType($activityType);
        if ($activityType === '' || $activityId <= 0 || !$this->relationTableAvailable()) {
            return [];
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT member_user_id, member_name
             FROM activity_member_relations
             WHERE activity_type = :activity_type
               AND activity_id = :activity_id
             ORDER BY id ASC'
        );
        $stmt->execute([
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
        ]);

        return $stmt->fetchAll() ?: [];
    }

    public function hasMemberAccess(string $activityType, int $activityId, int $userId): bool
    {
        $activityType = $this->normalizeActivityType($activityType);
        if ($activityType === '' || $activityId <= 0 || $userId <= 0 || !$this->relationTableAvailable()) {
            return false;
        }

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM activity_member_relations
             WHERE activity_type = :activity_type
               AND activity_id = :activity_id
               AND member_user_id = :member_user_id'
        );
        $stmt->execute([
            ':activity_type' => $activityType,
            ':activity_id' => $activityId,
            ':member_user_id' => $userId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function relationTableAvailable(): bool
    {
        if ($this->relationTableAvailable !== null) {
            return $this->relationTableAvailable;
        }

        $pdo = db_pdo();
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'activity_member_relations'");
            $this->relationTableAvailable = $stmt !== false && $stmt->fetchColumn() !== false;
        } catch (Throwable $e) {
            $this->relationTableAvailable = false;
        }

        return $this->relationTableAvailable;
    }

    private function normalizeActivityType(string $activityType): string
    {
        $normalized = strtolower(trim($activityType));

        return in_array($normalized, ['penelitian', 'pengabdian', 'hilirisasi'], true) ? $normalized : '';
    }
}
