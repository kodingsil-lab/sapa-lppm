<?php

declare(strict_types=1);

require_once __DIR__ . '/AuthHelper.php';
require_once __DIR__ . '/DatabaseHelper.php';

if (!function_exists('logActivity')) {
    function logActivity(string $module, string $action, ?int $dataId = null, ?int $userId = null): void
    {
        try {
            $pdo = db_pdo();
            $actorId = $userId ?? (int) (authUserId() ?? 0);
            $actorId = $actorId > 0 ? $actorId : null;

            $stmt = $pdo->prepare(
                'INSERT INTO activity_logs (user_id, action, module, data_id, created_at)
                 VALUES (:user_id, :action, :module, :data_id, NOW())'
            );
            $stmt->execute([
                ':user_id' => $actorId,
                ':action' => trim($action),
                ':module' => trim($module),
                ':data_id' => $dataId,
            ]);
        } catch (Throwable $e) {
            // Keep app flow safe even if logging fails.
        }
    }
}
