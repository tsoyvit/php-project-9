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
        ?int $statusCode,
        ?string $h1,
        ?string $title,
        ?string $description,
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
}
