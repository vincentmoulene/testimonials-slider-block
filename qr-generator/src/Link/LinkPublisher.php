<?php

declare(strict_types=1);

namespace App\Link;

use App\Entity\PublicLink;
use App\Repository\PublicLinkRepository;

/**
 * Records the links that may be shown in the public gallery.
 *
 * What is publishable at all is decided by {@see LinkNormaliser}; this class only
 * deals with storing it.
 */
final readonly class LinkPublisher
{
    public function __construct(
        private PublicLinkRepository $links,
        private LinkNormaliser $normaliser,
        private bool $enabled,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return PublicLink|null the recorded link, or null when the payload must stay private
     */
    public function publish(string $payload, string $locale): ?PublicLink
    {
        if (!$this->enabled) {
            return null;
        }

        $url = $this->normaliser->normalise($payload);
        if (null === $url) {
            return null;
        }

        return $this->links->record($url, (string) parse_url($url, \PHP_URL_HOST), $locale);
    }
}
