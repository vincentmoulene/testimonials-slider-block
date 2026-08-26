<?php

declare(strict_types=1);

namespace App\Controller;

use App\Generator\CodeRenderer;
use App\Generator\FieldValues;
use App\Generator\InvalidPayloadException;
use App\Generator\PayloadFactory;
use App\Generator\RenderOptions;
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

/**
 * Stateless generation endpoints, shared by the live preview (JSON) and the
 * download buttons / no-JavaScript fallback (image).
 */
final class GenerateController extends AbstractController
{
    /** @param array<int, string> $enabledLocales */
    public function __construct(
        private readonly array $enabledLocales,
        private readonly ToolRegistry $registry,
        private readonly PayloadFactory $payloadFactory,
        private readonly CodeRenderer $renderer,
        private readonly TranslatorInterface $translator,
        #[Target('generate')]
        private readonly RateLimiterFactoryInterface $limiter,
    ) {
    }

    #[Route('/q/{format}', name: 'app_generate', requirements: ['format' => 'png|svg|webp'], methods: ['GET'])]
    public function image(Request $request, string $format): Response
    {
        $this->throttle($request);

        $tool = $this->registry->get((string) $request->query->get('t', 'url'));
        if (null === $tool) {
            throw $this->createNotFoundException();
        }

        $values = FieldValues::fromRequest($tool, $request);
        $options = RenderOptions::fromRequest($request, $format);

        try {
            $code = $this->renderer->render($tool, $this->payloadFactory->build($tool, $values), $options, $values);
        } catch (InvalidPayloadException $e) {
            return new Response($this->message($e, $request->getLocale()), Response::HTTP_BAD_REQUEST, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $response = new Response($code->data, Response::HTTP_OK, [
            'Content-Type' => $code->mimeType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPublic();
        $response->setMaxAge(31536000);
        $response->setSharedMaxAge(31536000);
        $response->setImmutable();
        $response->setEtag(md5($code->data));
        $response->isNotModified($request);

        if ($request->query->getBoolean('download')) {
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
                'attachment',
                \sprintf('%s-%s.%s', $tool->kind, $tool->id, $code->extension),
            ));
        }

        return $response;
    }

    /**
     * Used by the live preview: returns the image inline as a data URI so the
     * browser never fires a second request while the visitor is typing.
     */
    #[Route('/api/generate', name: 'app_api_generate', methods: ['POST'])]
    public function api(Request $request): JsonResponse
    {
        $this->throttle($request);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent() ?: '{}', true, flags: \JSON_THROW_ON_ERROR | \JSON_INVALID_UTF8_SUBSTITUTE);

        $locale = \in_array($payload['locale'] ?? null, $this->enabledLocales, true) ? (string) $payload['locale'] : $request->getLocale();

        $tool = $this->registry->get((string) ($payload['tool'] ?? ''));
        if (null === $tool) {
            return new JsonResponse(['ok' => false, 'error' => $this->translator->trans('error.unknown_tool', [], locale: $locale)], Response::HTTP_BAD_REQUEST);
        }

        $values = FieldValues::fromArray($tool, (array) ($payload['values'] ?? []));
        $ecc = strtoupper((string) ($payload['ecc'] ?? 'M'));
        $options = new RenderOptions(
            format: 'svg',
            size: 512,
            margin: max(0, min(80, (int) ($payload['margin'] ?? 16))),
            ecc: \in_array($ecc, RenderOptions::ECC_LEVELS, true) ? $ecc : 'M',
            foreground: $this->hexColor($payload['fg'] ?? null, '#111111'),
            background: $this->hexColor($payload['bg'] ?? null, '#ffffff'),
            transparent: (bool) ($payload['transparent'] ?? false),
        );

        try {
            $data = $this->payloadFactory->build($tool, $values);
            $code = $this->renderer->render($tool, $data, $options, $values);
        } catch (InvalidPayloadException $e) {
            return new JsonResponse(['ok' => false, 'error' => $this->message($e, $locale)]);
        }

        return new JsonResponse([
            'ok' => true,
            'image' => $code->dataUri(),
            'payload' => $data,
        ]);
    }

    private function hexColor(mixed $value, string $fallback): string
    {
        if (!\is_string($value)) {
            return $fallback;
        }
        $value = '#'.ltrim(trim($value), '#');

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value) ? strtolower($value) : $fallback;
    }

    private function throttle(Request $request): void
    {
        $limit = $this->limiter->create($request->getClientIp() ?? 'anonymous')->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException($limit->getRetryAfter()->getTimestamp() - time(), 'Too many requests.');
        }
    }

    private function message(InvalidPayloadException $e, string $locale): string
    {
        return $this->translator->trans($e->translationKey, array_map(
            fn (string $v) => str_starts_with($v, 'field.') ? $this->translator->trans($v, [], locale: $locale) : $v,
            $e->parameters,
        ), locale: $locale);
    }
}
