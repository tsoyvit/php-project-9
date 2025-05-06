<?php

namespace App;

class PageCheckResult
{
    private ?int $statusCode;
    private ?string $h1;
    private ?string $title;
    private ?string $description;
    private ?string $error;

    public function __construct(
        ?int $statusCode = null,
        ?string $h1 = null,
        ?string $title = null,
        ?string $description = null,
        ?string $error = null
    ) {
        $this->statusCode = $statusCode;
        $this->h1 = $h1;
        $this->title = $title;
        $this->description = $description;
        $this->error = $error;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        return [
            'status_code' => $this->statusCode,
            'h1' => $this->h1,
            'title' => $this->title,
            'description' => $this->description,
        ];
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
