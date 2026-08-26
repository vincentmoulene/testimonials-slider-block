<?php

declare(strict_types=1);

namespace App\Seo;

final readonly class SitemapEntry
{
    /** @param array<string, string> $alternates locale => absolute url */
    public function __construct(
        public string $loc,
        public array $alternates = [],
        public ?\DateTimeImmutable $lastModified = null,
        public string $changeFrequency = 'weekly',
        public float $priority = 0.6,
    ) {
    }
}
