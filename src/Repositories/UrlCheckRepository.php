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
            $check = UrlCheck::fromArrayAndUrlId([
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

    public function getLastChecks(): array
    {
        $lastChecks = [];
        $sql = "SELECT urls.id, urls.name, c.created_at, c.status_code
                FROM urls
                LEFT JOIN (
                SELECT DISTINCT ON (url_id) *
                FROM url_checks
                ORDER BY url_id, created_at DESC
                ) c ON urls.id = c.url_id
                ORDER BY urls.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $lastChecks[] = $row;
        }
        return $lastChecks;
    }

    public function createCheck(UrlCheck $check): void
    {
        try {
            $created_at = Carbon::now()->toDateTimeString();

            $fields = [
                'status_code' => $check->getStatusCode(),
                'url_id' => $check->getUrlId(),
                'created_at' => $created_at,
            ];

            if ($check->getH1() !== null) {
                $fields['h1'] = $check->getH1();
            }
            if ($check->getTitle() !== null) {
                $fields['title'] = $check->getTitle();
            }
            if ($check->getDescription() !== null) {
                $fields['description'] = $check->getDescription();
            }

            $columns = implode(', ', array_keys($fields));
            $placeholders = implode(', ', array_map(fn($key) => ":$key", array_keys($fields)));

            $sql = "INSERT INTO url_checks ($columns) VALUES ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($fields);

            $check->setId((int)$this->pdo->lastInsertId());
        } catch (\PDOException $e) {
            throw new \RuntimeException('Ошибка базы данных при сохранении.', 0, $e);
        }
    }
}
