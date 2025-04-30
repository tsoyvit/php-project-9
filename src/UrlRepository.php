<?php

namespace App;

use Carbon\Carbon;
use Monolog\Logger;

class UrlRepository
{
    private \PDO $pdo;
    private Logger $logger;
    public function __construct(\PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function getUrls(): array
    {
        $urls = [];
        $stmt = $this->pdo->prepare("SELECT * FROM urls");
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

    public function saveUrl(Url $url): void
    {
        if ($url->exists()) {
            $this->updateUrl($url);
        } else {
            $this->createUrl($url);
        }
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
        try {
            $created_at = Carbon::now()->toDateTimeString();
            $sql = "INSERT INTO urls (name, created_at) VALUES (:name, :created_at)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'name' => $url->getName(),
                'created_at' => $created_at
            ]);

            $id = (int) $this->pdo->lastInsertId();
            $url->setId($id);
        } catch (\PDOException $e) {
            throw new \RuntimeException('Ошибка базы данных при сохранении.', 0, $e);
        }
    }

    public function updateUrl(Url $url): void
    {
        $sql = "UPDATE urls SET name = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$url->getName(), $url->getId()]);
    }

    public function deleteUrl(Url $url): void
    {
        $sql = "DELETE FROM urls WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$url->getId()]);
    }
}
