<?php

require_once __DIR__ . '/Conexion.php';

class ExpedienteQueue
{
    private const TABLE = 'expediente_queue';
    private const MAX_ATTEMPTS = 3;

    private PDO $pdo;

    public function __construct()
    {
        $db = new Conexion();
        $this->pdo = $db->pdo;
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $create = sprintf(
            'CREATE TABLE IF NOT EXISTS %s (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                filepath VARCHAR(512) NOT NULL,
                id_expediente INT NULL,
                status ENUM("pending","processing","done","failed") NOT NULL DEFAULT "pending",
                attempts INT NOT NULL DEFAULT 0,
                last_error TEXT NULL,
                result_payload LONGTEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                processed_at DATETIME NULL,
                KEY status_idx (status),
                KEY expediente_idx (id_expediente)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            self::TABLE
        );

        $this->pdo->exec($create);
    }

    public function enqueue(array $payload): int
    {
        $sql = sprintf(
            'INSERT INTO %s (filename, filepath, id_expediente, status, attempts, created_at, updated_at) 
             VALUES (:filename, :filepath, :id_expediente, "pending", 0, NOW(), NOW())',
            self::TABLE
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':filename' => $payload['filename'] ?? 'expediente.pdf',
            ':filepath' => $payload['filepath'] ?? '',
            ':id_expediente' => $payload['id_expediente'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function claimNextPending(): ?array
    {
        $this->pdo->beginTransaction();

        $query = sprintf(
            'SELECT * FROM %s 
             WHERE status = "pending" OR (status = "failed" AND attempts < :max_attempts)
             ORDER BY created_at ASC
             LIMIT 1 FOR UPDATE',
            self::TABLE
        );

        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':max_attempts' => self::MAX_ATTEMPTS]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $this->pdo->commit();
            return null;
        }

        $update = sprintf(
            'UPDATE %s SET status = "processing", attempts = attempts + 1, updated_at = NOW() WHERE id = :id',
            self::TABLE
        );
        $this->pdo->prepare($update)->execute([':id' => $job['id']]);
        $this->pdo->commit();

        $job['status'] = 'processing';
        $job['attempts'] = (int) $job['attempts'] + 1;

        return $job;
    }

    public function acquireJobForProcessing(int $id): ?array
    {
        $this->pdo->beginTransaction();

        $query = sprintf('SELECT * FROM %s WHERE id = :id LIMIT 1 FOR UPDATE', self::TABLE);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $this->pdo->commit();
            return null;
        }

        if ($job['status'] === 'done') {
            $this->pdo->commit();
            return null;
        }

        if ((int) $job['attempts'] >= self::MAX_ATTEMPTS && $job['status'] === 'failed') {
            $this->pdo->commit();
            return null;
        }

        $update = sprintf(
            'UPDATE %s SET status = "processing", attempts = attempts + 1, updated_at = NOW() WHERE id = :id',
            self::TABLE
        );
        $this->pdo->prepare($update)->execute([':id' => $job['id']]);
        $this->pdo->commit();

        $job['status'] = 'processing';
        $job['attempts'] = (int) $job['attempts'] + 1;

        return $job;
    }

    public function markCompleted(int $id, array $result = []): void
    {
        $sql = sprintf(
            'UPDATE %s SET status = "done", last_error = NULL, result_payload = :payload, 
             updated_at = NOW(), processed_at = NOW() WHERE id = :id',
            self::TABLE
        );
        $this->pdo->prepare($sql)->execute([
            ':id' => $id,
            ':payload' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);
    }

    public function markFailed(int $id, string $error, array $result = []): void
    {
        $sql = sprintf(
            'UPDATE %s SET status = "failed", last_error = :error, result_payload = :payload, updated_at = NOW() WHERE id = :id',
            self::TABLE
        );
        $this->pdo->prepare($sql)->execute([
            ':id' => $id,
            ':error' => $error,
            ':payload' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);
    }

    public function getStatus(int $id): ?array
    {
        $sql = sprintf('SELECT * FROM %s WHERE id = :id LIMIT 1', self::TABLE);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        return $job ?: null;
    }
}
