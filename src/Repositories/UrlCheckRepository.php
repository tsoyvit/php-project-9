<?php

namespace App\Repositories;

use App\Domain\UrlCheck;
use Carbon\Carbon;

class UrlCheckRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
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
            $check = new UrlCheck(
                urlId: $row['url_id'],
                id: $row['id'],
                statusCode: $row['status_code'],
                h1: $row['h1'],
                title: $row['title'],
                description: $row['description'],
                createdAt: $row['created_at']
            );
            $checks[] = $check;
        }
        return $checks;
    }

    public function getLastChecks(): array
    {
        $lastChecks = [];
        $sql = "WITH latest_checks AS (
                    SELECT DISTINCT ON (url_id) status_code, url_id, created_at
                    FROM url_checks
                    ORDER BY url_id, created_at DESC
                )
                SELECT urls.id, urls.name, c.created_at, c.status_code
                FROM urls
                    LEFT JOIN latest_checks c ON urls.id = c.url_id
                ORDER BY urls.created_at DESC
                ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $lastChecks[] = $row;
        }
        return $lastChecks;
    }

    public function createCheck(UrlCheck $check): void
    {
        $createdAt = Carbon::now()->toDateTimeString();

        $sql = "INSERT INTO url_checks 
                    (url_id, h1, status_code, title, description, created_at)
                VALUES 
                    (:url_id, :h1, :status_code, :title, :description, :created_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'url_id' => $check->getUrlId(),
            'h1' => $check->getH1(),
            'status_code' => $check->getStatusCode(),
            'title' => $check->getTitle(),
            'description' => $check->getDescription(),
            'created_at' => $createdAt
        ]);

        $check->setId((int)$this->pdo->lastInsertId());
        $check->setCreatedAt($createdAt);
    }
}
