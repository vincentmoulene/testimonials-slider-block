<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PublicLinkRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A link someone turned into a QR code, kept so it can be listed and found again.
 *
 * Only http(s) links ever reach this table: a Wi-Fi password, a vCard or a plain
 * text is never public, whatever the visitor encoded.
 */
#[ORM\Entity(repositoryClass: PublicLinkRepository::class)]
#[ORM\Table(name: 'public_links')]
#[ORM\UniqueConstraint(name: 'uniq_public_links_hash', columns: ['url_hash'])]
#[ORM\Index(name: 'idx_public_links_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_public_links_host', columns: ['host'])]
class PublicLink
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1024)]
    private string $url;

    /** SHA-256 of the URL: the unique key, since the URL itself is too long to index. */
    #[ORM\Column(length: 64, unique: true)]
    private string $urlHash;

    #[ORM\Column(length: 255)]
    private string $host;

    #[ORM\Column(length: 5)]
    private string $locale;

    #[ORM\Column]
    private int $hits = 1;

    /** Hidden from every listing. Set by moderation, never by a visitor. */
    #[ORM\Column]
    private bool $blocked = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $lastSeenAt;

    public function __construct(string $url, string $host, string $locale)
    {
        $this->url = $url;
        $this->urlHash = hash('sha256', $url);
        $this->host = $host;
        $this->locale = $locale;
        $this->createdAt = new \DateTimeImmutable();
        $this->lastSeenAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getUrlHash(): string
    {
        return $this->urlHash;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    public function block(bool $blocked = true): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function recordHit(): self
    {
        ++$this->hits;
        $this->lastSeenAt = new \DateTimeImmutable();

        return $this;
    }

    /** Shortened for display, so one long URL cannot blow up the layout. */
    public function getShortUrl(int $length = 60): string
    {
        $withoutScheme = preg_replace('#^https?://#', '', $this->url) ?? $this->url;

        return mb_strlen($withoutScheme) > $length ? mb_substr($withoutScheme, 0, $length - 1).'…' : $withoutScheme;
    }
}
