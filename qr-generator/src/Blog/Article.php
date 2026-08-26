<?php

declare(strict_types=1);

namespace App\Blog;

final readonly class Article
{
    /** @param array<int, string> $tags */
    public function __construct(
        public string $key,
        public string $locale,
        public string $slug,
        public string $title,
        public string $description,
        public \DateTimeImmutable $publishedAt,
        public \DateTimeImmutable $updatedAt,
        public array $tags,
        public string $html,
        public int $readingTime,
        public ?string $relatedTool = null,
    ) {
    }
}
