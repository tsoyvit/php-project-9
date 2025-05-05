<?php

namespace App\Domain;

class UrlCheck
{
    private ?int $id = null;
    private int $urlId;
    private ?int $statusCode = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $createdAt  = null;

    public static function fromArrayAndUrlId(array $checkData, int $urlId): UrlCheck
    {
        $check = new UrlCheck();
        $check->setUrlId($urlId);
        $check->setId($checkData['id'] ?? null);
        $check->setStatusCode($checkData['status_code'] ?? null);
        $check->setH1($checkData['h1'] ?? null);
        $check->setTitle($checkData['title'] ?? null);
        $check->setDescription($checkData['description'] ?? null);
        $check->setCreatedAt($checkData['created_at'] ?? null);
        return $check;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function setH1(?string $h1): void
    {
        $this->h1 = $h1;
    }

    public function getUrlId(): ?int
    {
        return $this->urlId;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }
}
