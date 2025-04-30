<?php

namespace App;

use Carbon\Carbon;
use Monolog\Logger;

class UrlCheckRepository
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
        $sql = "SELECT * FROM url_checks WHERE url_id = ? ORDER BY created_at DESC";
        if ($limit > 0) {
            $sql .= " LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$urlId, $limit]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$urlId]);
        }
        while ($row = $stmt->fetch()) {
            $check = UrlCheck::fromArray([
                'id' => $row['id'],
                'status_code' => $row['status_code'],
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
        $sql = "SELECT urls.id, urls.name, c.created_at, c.status_code
FROM urls
    LEFT JOIN (
    SELECT DISTINCT ON (url_id) *
    FROM url_checks
    ORDER BY url_id, created_at DESC) c ON urls.id = c.url_id
ORDER BY c.created_at";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $lastChecks[] = $row;
        }
        return $lastChecks;
    }

    public function saveCheck(UrlCheck $check): void
    {
        if ($check->exists()) {
            $this->updateCheck($check);
        } else {
            $this->createCheck($check);
        }
    }

    public function getCheckById(int $id): ?UrlCheck
    {
        $stmt = $this->pdo->prepare("SELECT * FROM url_checks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        if ($row = $stmt->fetch()) {
            return UrlCheck::fromArray([
                'id' => $row['id'],
                'status_code' => $row['status_code'],
                'h1' => $row['h1'],
                'title' => $row['title'],
                'description' => $row['description'],
                'created_at' => $row['created_at']
            ], $row['url_id']);
        }
        return null;
    }

    private function updateCheck(UrlCheck $check): void
    {
        $sql = "UPDATE url_checks 
            SET status_code = :status_code, h1 = :h1, title = :title, description = :description WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $check->getId(),
            'status_code' => $check->getStatusCode(),
            'h1' => $check->getH1(),
            'title' => $check->getTitle(),
            'description' => $check->getDescription(),
        ]);
    }

    public function deleteCheck(UrlCheck $check): void
    {
        $sql = "DELETE FROM url_checks WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $check->getId()]);
    }

    private function createCheck(UrlCheck $check): void
    {
        try {
            $created_at = Carbon::now()->toDateTimeString();
            $sql = "INSERT INTO url_checks (status_code, url_id, h1, title, description, created_at) 
                VALUES (:status_code, :url_id, :h1, :title, :description, :created_at)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'status_code' => $check->getStatusCode() ?? 0,
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
