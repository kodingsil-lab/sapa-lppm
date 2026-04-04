<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseModel.php';

class PasswordResetTokenModel extends BaseModel
{
    private bool $tableEnsured = false;

    public function createForUser(int $userId, string $email, int $ttlMinutes = 30): string
    {
        $this->ensureTable();

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $pdo = db_pdo();
        $pdo->beginTransaction();

        try {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM password_reset_tokens
                 WHERE user_id = :user_id OR email = :email OR expires_at < NOW() OR used_at IS NOT NULL'
            );
            $deleteStmt->execute([
                ':user_id' => $userId,
                ':email' => $email,
            ]);

            $insertStmt = $pdo->prepare(
                'INSERT INTO password_reset_tokens (user_id, email, token_hash, expires_at, created_at)
                 VALUES (:user_id, :email, :token_hash, DATE_ADD(NOW(), INTERVAL :ttl MINUTE), NOW())'
            );
            $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $insertStmt->bindValue(':email', $email, PDO::PARAM_STR);
            $insertStmt->bindValue(':token_hash', $tokenHash, PDO::PARAM_STR);
            $insertStmt->bindValue(':ttl', $ttlMinutes, PDO::PARAM_INT);
            $insertStmt->execute();

            $pdo->commit();

            return $rawToken;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public function findValidByToken(string $rawToken): ?array
    {
        $this->ensureTable();

        $tokenHash = hash('sha256', $rawToken);
        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'SELECT *
             FROM password_reset_tokens
             WHERE token_hash = :token_hash
               AND used_at IS NULL
               AND expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $data = $stmt->fetch();

        return $data !== false ? $data : null;
    }

    public function markAsUsed(int $id): void
    {
        $this->ensureTable();

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'UPDATE password_reset_tokens
             SET used_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }

    public function deleteExpired(): void
    {
        $this->ensureTable();

        $pdo = db_pdo();
        $stmt = $pdo->prepare(
            'DELETE FROM password_reset_tokens
             WHERE expires_at < NOW() OR used_at IS NOT NULL'
        );
        $stmt->execute();
    }

    private function ensureTable(): void
    {
        if ($this->tableEnsured) {
            return;
        }

        if (!$this->shouldAutoManageSchema()) {
            $this->tableEnsured = true;
            return;
        }

        $pdo = db_pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_password_reset_user_id (user_id),
                UNIQUE KEY uq_password_reset_token_hash (token_hash)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->tableEnsured = true;
    }
}
