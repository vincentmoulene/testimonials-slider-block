<?php

declare(strict_types=1);

namespace App\Seo;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds absolute URLs from the *configured* site URL rather than from the
 * incoming request host, so canonical/hreflang tags never leak a staging or
 * proxy hostname into the index.
 */
final readonly class SeoFactory
{
    /** @param array<int, string> $enabledLocales */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private array $enabledLocales,
        private string $siteUrl,
    ) {
    }

    /** @param array<string, mixed> $params */
    public function url(string $route, array $params = []): string
    {
        return $this->absolute($this->urlGenerator->generate($route, $params));
    }

    public function absolute(string $path): string
    {
        return rtrim($this->siteUrl, '/').$path;
    }

    /**
     * @param array<string, array<string, mixed>>|array<string, mixed> $params
     *        Either shared route params, or a locale => params map when the
     *        slug itself is translated.
     *
     * @return array<string, string> locale => absolute url
     */
    public function alternates(string $route, array $params = [], bool $paramsPerLocale = false): array
    {
        $alternates = [];
        foreach ($this->enabledLocales as $locale) {
            $localeParams = $paramsPerLocale ? ($params[$locale] ?? null) : $params;
            if (null === $localeParams) {
                continue;
            }
            $localeParams['_locale'] = $locale;
            $alternates[$locale] = $this->url($route, $localeParams);
        }

        return $alternates;
    }
}
