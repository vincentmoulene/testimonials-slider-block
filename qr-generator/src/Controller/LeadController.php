<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lead\InvalidLeadException;
use App\Lead\LeadCapture;
use App\Tool\ToolRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LeadController extends AbstractController
{
    /** @param array<int, string> $enabledLocales */
    public function __construct(
        private readonly LeadCapture $leadCapture,
        private readonly ToolRegistry $tools,
        private readonly TranslatorInterface $translator,
        private readonly array $enabledLocales,
        #[Target('lead')]
        private readonly RateLimiterFactoryInterface $limiter,
    ) {
    }

    #[Route('/api/lead', name: 'app_api_lead', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        if (!$this->leadCapture->isEnabled()) {
            return new JsonResponse(['ok' => false], Response::HTTP_NOT_FOUND);
        }

        $limit = $this->limiter->create($request->getClientIp() ?? 'anonymous')->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time());
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent() ?: '{}', true, flags: \JSON_THROW_ON_ERROR | \JSON_INVALID_UTF8_SUBSTITUTE);

        $locale = \in_array($payload['locale'] ?? null, $this->enabledLocales, true) ? (string) $payload['locale'] : $request->getLocale();

        // Honeypot: a field hidden from humans. Bots fill it, and get a polite
        // success they cannot distinguish from the real thing.
        if ('' !== trim((string) ($payload['company'] ?? ''))) {
            return new JsonResponse(['ok' => true]);
        }

        $tool = $this->tools->get((string) ($payload['tool'] ?? '')) ?? $this->tools->generic();

        try {
            $this->leadCapture->capture(
                email: (string) ($payload['email'] ?? ''),
                locale: $locale,
                tool: $tool->id,
                source: \is_string($payload['source'] ?? null) ? $payload['source'] : $request->headers->get('referer'),
                consentMarketing: (bool) ($payload['consent'] ?? false),
                request: $request,
            );
        } catch (InvalidLeadException $e) {
            return new JsonResponse([
                'ok' => false,
                'error' => $this->translator->trans($e->translationKey, [], locale: $locale),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['ok' => true]);
    }
}
