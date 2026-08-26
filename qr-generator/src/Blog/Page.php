<?php

declare(strict_types=1);

namespace App\Blog;

final readonly class Page
{
    public function __construct(
        public string $name,
        public string $locale,
        public string $title,
        public string $description,
        public string $html,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
