<?php

declare(strict_types=1);

namespace App\Controller;

use App\Seo\Seo;
use App\Seo\SeoFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class SiteController extends AbstractController
{
    public function __construct(
        protected readonly SeoFactory $seo,
        protected readonly TranslatorInterface $translator,
        protected readonly string $siteName,
    ) {
    }

    protected function title(string $title): string
    {
        return $title.' | '.$this->siteName;
    }

    /** @return array<mixed> */
    protected function organization(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => $this->seo->absolute('/#organization'),
            'name' => $this->siteName,
            'url' => $this->seo->absolute('/'),
        ];
    }

    /**
     * @param array<int, array{0: string, 1: string}> $breadcrumbs
     *
     * @return array<mixed>
     */
    protected function breadcrumbList(array $breadcrumbs): array
    {
        $items = [];
        foreach ($breadcrumbs as $position => [$label, $url]) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $label,
                'item' => $url,
            ];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /**
     * @param array<int, array{0: string, 1: string}> $questions
     *
     * @return array<mixed>
     */
    protected function faqPage(array $questions): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $qa) => [
                '@type' => 'Question',
                'name' => $qa[0],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
            ], $questions),
        ];
    }

    /**
     * @param array<int, string> $keys translation keys, each with a `.q` and `.a` suffix
     *
     * @return array<int, array{0: string, 1: string}>
     */
    protected function faqEntries(array $keys, string $locale, array $parameters = []): array
    {
        return array_map(
            fn (string $key) => [
                $this->translator->trans($key.'.q', $parameters, locale: $locale),
                $this->translator->trans($key.'.a', $parameters, locale: $locale),
            ],
            $keys,
        );
    }

    protected function seoFor(string $route, string $locale, string $title, string $description, array $params = []): Seo
    {
        return new Seo(
            title: $title,
            description: $description,
            canonical: $this->seo->url($route, $params + ['_locale' => $locale]),
            alternates: $this->seo->alternates($route, $params),
        );
    }
}
