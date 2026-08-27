<?php

declare(strict_types=1);

namespace App\Lead;

use App\Entity\Lead;
use App\Repository\LeadRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Collects the email addresses of people who download a code.
 *
 * Two things are deliberately separated: the address itself, which is needed to
 * deliver the download the visitor asked for, and the marketing consent, which
 * is a distinct, explicit opt-in. Only the second one authorises a newsletter.
 */
final readonly class LeadCapture
{
    public function __construct(
        private LeadRepository $leads,
        private ValidatorInterface $validator,
        private LeadCaptureMode $mode,
        private string $secret,
    ) {
    }

    public function mode(): LeadCaptureMode
    {
        return $this->mode;
    }

    public function isEnabled(): bool
    {
        return LeadCaptureMode::Off !== $this->mode;
    }

    /**
     * @throws InvalidLeadException
     */
    public function capture(string $email, string $locale, string $tool, ?string $source, bool $consentMarketing, Request $request): Lead
    {
        $email = mb_strtolower(trim($email));

        $violations = $this->validator->validate($email, [
            new Assert\NotBlank(),
            new Assert\Length(max: 180),
            new Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT),
        ]);

        if (\count($violations) > 0) {
            throw new InvalidLeadException('error.invalid_email');
        }

        return $this->leads->record(
            email: $email,
            locale: $locale,
            tool: $tool,
            source: $source,
            ipHash: $this->hashIp($request->getClientIp()),
            consentMarketing: $consentMarketing,
        );
    }

    /**
     * Keyed hash: the value is useless to anyone who does not also have the
     * application secret, and it cannot be reversed by scanning the IPv4 space.
     */
    private function hashIp(?string $ip): ?string
    {
        return null !== $ip ? hash_hmac('sha256', $ip, $this->secret) : null;
    }
}
