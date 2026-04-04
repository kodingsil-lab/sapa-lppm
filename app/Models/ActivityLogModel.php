<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../Helpers/DatabaseHelper.php';

class ActivityLogModel extends BaseModel
{
    public function getAllWithUser(int $limit = 1000): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                al.id,
                al.user_id,
                al.action,
                al.module,
                al.data_id,
                al.created_at,
                u.name AS user_name,
                u.role AS user_role
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.id DESC
             LIMIT :limit_rows'
        );
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function getRecentWithUser(int $limit = 10): array
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT
                al.id,
                al.user_id,
                al.action,
                al.module,
                al.data_id,
                al.created_at,
                u.name AS user_name,
                u.role AS user_role
             FROM activity_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.id DESC
             LIMIT :limit_rows'
        );
        $stmt->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll() ?: [];
    }

    public function countToday(): int
    {
        $pdo = db_pdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()');
        $stmt->execute();

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function deleteBulkByIds(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return 0;
        }

        $pdo = db_pdo();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare('DELETE FROM activity_logs WHERE id IN (' . $placeholders . ')');
        $stmt->execute($ids);

        return (int) $stmt->rowCount();
    }
}

