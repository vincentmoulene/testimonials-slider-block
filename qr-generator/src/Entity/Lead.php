<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LeadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * One row per email address that generated at least one code.
 *
 * Repeat generations update the counters instead of creating duplicates, so the
 * table stays a clean mailing list rather than an event log.
 */
#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\Table(name: 'leads')]
#[ORM\UniqueConstraint(name: 'uniq_leads_email', columns: ['email'])]
#[ORM\Index(name: 'idx_leads_created_at', columns: ['created_at'])]
class Lead
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 5)]
    private string $locale;

    /** Generator used the first time, then the most recent one. */
    #[ORM\Column(length: 32)]
    private string $tool;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $source = null;

    /**
     * Salted hash of the IP address. Enough to prove where a consent came from
     * and to spot abuse, without storing an identifier we do not need.
     */
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $ipHash = null;

    #[ORM\Column]
    private bool $consentMarketing = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $lastSeenAt;

    #[ORM\Column]
    private int $generationCount = 1;

    public function __construct(string $email, string $locale, string $tool)
    {
        $this->email = $email;
        $this->locale = $locale;
        $this->tool = $tool;
        $this->createdAt = new \DateTimeImmutable();
        $this->lastSeenAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTool(): string
    {
        return $this->tool;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = null !== $source ? mb_substr($source, 0, 512) : null;

        return $this;
    }

    public function getIpHash(): ?string
    {
        return $this->ipHash;
    }

    public function setIpHash(?string $ipHash): self
    {
        $this->ipHash = $ipHash;

        return $this;
    }

    public function hasConsentedToMarketing(): bool
    {
        return $this->consentMarketing;
    }

    public function setConsentMarketing(bool $consentMarketing): self
    {
        // Consent is only ever added, never silently withdrawn by a later
        // download where the box happened to be unticked.
        $this->consentMarketing = $this->consentMarketing || $consentMarketing;

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

    public function getGenerationCount(): int
    {
        return $this->generationCount;
    }

    public function recordGeneration(string $locale, string $tool): self
    {
        $this->locale = $locale;
        $this->tool = $tool;
        $this->lastSeenAt = new \DateTimeImmutable();
        ++$this->generationCount;

        return $this;
    }
}
