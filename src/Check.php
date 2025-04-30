<?php

namespace App;

class Check
{
    private ?int $id = null;
    private int $url_id;
    private ?int $code = null;
    private ?string $h1 = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?string $created_at  = null;

    public static function fromArray(array $checkData, int $urlId): Check
    {
        $check = new Check();
        $check->setUrlId($urlId);
        $check->setId($checkData['id'] ?? null);
        $check->setCode($checkData['code'] ?? null);
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

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setCode(?int $code): void
    {
        $this->code = $code;
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
        return $this->created_at;
    }

    public function setCreatedAt(?string $created_at): void
    {
        $this->created_at = $created_at;
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
        return $this->url_id;
    }

    public function setUrlId(?int $urlId): void
    {
        $this->url_id = $urlId;
    }
}
