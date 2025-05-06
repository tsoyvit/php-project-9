<?php

namespace App\Repositories;

use App\Domain\Url;
use Carbon\Carbon;

class UrlRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getUrls(): array
    {
        $urls = [];
        $stmt = $this->pdo->prepare("SELECT * FROM urls ORDER BY created_at DESC");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $url = Url::fromArray([
                'id' => $row['id'],
                'name' => $row['name'],
                'created_at' => $row['created_at']
            ]);
            $urls[] = $url;
        }
        return $urls;
    }

    public function existsByName(string $name): ?Url
    {
        $stmt = $this->pdo->prepare("SELECT * FROM urls WHERE name = ?");
        $stmt->execute([$name]);
        if ($row = $stmt->fetch()) {
            return Url::fromArray([
                'id' => $row['id'],
                'name' => $row['name'],
                'created_at' => $row['created_at']
            ]);
        }
        return null;
    }

    public function getUrlById(int $id): ?Url
    {
        $sql = "SELECT * FROM urls WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        if ($row = $stmt->fetch()) {
            return Url::fromArray([
                'id' => $row['id'],
                'name' => $row['name'],
                'created_at' => $row['created_at']
            ]);
        }
        return null;
    }

    public function createUrl(Url $url): void
    {
        $createdAt = Carbon::now()->toDateTimeString();
        $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $url->getName(),
            'created_at' => $createdAt
        ]);

        $id = (int)$this->pdo->lastInsertId();
        $url->setId($id);
    }
}
