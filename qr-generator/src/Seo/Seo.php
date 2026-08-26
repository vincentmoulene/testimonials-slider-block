<?php

declare(strict_types=1);

namespace App\Seo;

/**
 * Everything the <head> needs for one page: metadata, canonical, hreflang
 * alternates, breadcrumbs and JSON-LD structured data.
 */
final class Seo
{
    /**
     * @param array<string, string>       $alternates locale => absolute url
     * @param array<int, array{0: string, 1: string}> $breadcrumbs [label, absolute url]
     * @param array<int, array<mixed>>    $jsonLd     additional schema.org nodes
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public array $alternates = [],
        public array $breadcrumbs = [],
        public array $jsonLd = [],
        public string $type = 'website',
        public ?string $image = null,
        public string $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        public ?string $publishedAt = null,
        public ?string $updatedAt = null,
    ) {
    }

    /** @param array<mixed> $node */
    public function addJsonLd(array $node): self
    {
        $this->jsonLd[] = $node;

        return $this;
    }
}
