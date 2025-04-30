<?php

namespace App;

use Carbon\Carbon;
use Monolog\Logger;

class CheckRepository
{
    private \PDO $pdo;
    private Logger $logger;
    public function __construct(\PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getChecks(int $urlId, int $limit = 0): array
    {
        $checks = [];
        $sql = "SELECT * FROM checks WHERE url_id = ? ORDER BY created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$urlId, $limit]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$urlId]);
        }
        while ($row = $stmt->fetch()) {
            $check = Check::fromArray([
                'id' => $row['id'],
                'code' => $row['code'],
                'h1' => $row['h1'],
                'title' => $row['title'],
                'description' => $row['description'],
                'created_at' => $row['created_at']
            ], $row['url_id']);
            $checks[] = $check;
        }
        return $checks;
    }

    public function getLastChecks()
    {
        $lastChecks = [];
        $sql = "SELECT urls.id, urls.name, c.created_at, c.code
FROM urls
    LEFT JOIN (
    SELECT DISTINCT ON (url_id) *
    FROM checks
    ORDER BY url_id, created_at DESC) c ON urls.id = c.url_id
ORDER BY c.created_at";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $lastChecks[] = $row;
        }
        return $lastChecks;
    }

    public function saveCheck(Check $check): void
    {
        if ($check->exists()) {
            $this->updateCheck($check);
        } else {
            $this->createCheck($check);
        }
    }

    public function getCheckById(int $id): ?Check
    {
        $stmt = $this->pdo->prepare("SELECT * FROM checks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if ($row = $stmt->fetch()) {
            return Check::fromArray([
                'id' => $row['id'],
                'code' => $row['code'],
                'h1' => $row['h1'],
                'title' => $row['title'],
                'description' => $row['description'],
                'created_at' => $row['created_at']
            ], $row['url_id']);
        }
        return null;
    }

    private function updateCheck(Check $check): void
    {
        $sql = "UPDATE checks SET code = :code, h1 = :h1, title = :title, description = :description WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $check->getId(),
            'code' => $check->getCode(),
            'h1' => $check->getH1(),
            'title' => $check->getTitle(),
            'description' => $check->getDescription(),
        ]);
    }

    public function deleteCheck(Check $check): void
    {
        $sql = "DELETE FROM checks WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $check->getId()]);
    }

    private function createCheck(Check $check): void
    {
        try {
            $created_at = Carbon::now()->toDateTimeString();
            $sql = "INSERT INTO checks (code, url_id, h1, title, description, created_at) 
                VALUES (:code, :url_id, :h1, :title, :description, :created_at)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'code' => $check->getCode() ?? 0,
                'url_id' => $check->getUrlId(),
                'h1' => $check->getH1() ?? '',
                'title' => $check->getTitle() ?? '',
                'description' => $check->getDescription() ?? '',
                'created_at' => $created_at
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $check->setId($id);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Ошибка базы данных при сохранении.', 0, $e);
        }
    }
}
