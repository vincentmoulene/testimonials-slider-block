<?php

declare(strict_types=1);

namespace App\Controller;

use App\Generator\FieldValues;
use App\Generator\InvalidPayloadException;
use App\Generator\PayloadFactory;
use App\Generator\RenderOptions;
use App\Seo\Seo;
use App\Seo\SeoFactory;
use App\Tool\Tool;
use App\Tool\ToolDemo;
use App\Tool\ToolRegistry;
use App\Tool\ToolUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ToolController extends SiteController
{
    /** @param array<int, string> $enabledLocales */
    public function __construct(
        SeoFactory $seo,
        TranslatorInterface $translator,
        string $siteName,
        private readonly ToolRegistry $registry,
        private readonly ToolUrlGenerator $toolUrls,
        private readonly ToolDemo $demo,
        private readonly PayloadFactory $payloadFactory,
        private readonly array $enabledLocales,
    ) {
        parent::__construct($seo, $translator, $siteName);
    }

    #[Route(path: [
        'en' => '/en',
        'fr' => '/fr',
        'es' => '/es',
        'de' => '/de',
        'it' => '/it',
    ], name: 'app_home', methods: ['GET'])]
    public function home(Request $request, string $_locale): Response
    {
        $tool = $this->registry->generic();
        $seo = $this->seoFor(
            'app_home',
            $_locale,
            $this->translator->trans('home.meta_title', ['%site%' => $this->siteName], locale: $_locale),
            $this->translator->trans('home.meta_description', [], locale: $_locale),
        );

        // A pre-filled home page URL is a shareable convenience, not a second
        // version of the home page for the index.
        if ($request->query->count() > 0) {
            $seo->robots = 'noindex, follow';
        }

        $faq = $this->faqEntries(['faq.free', 'faq.expire', 'faq.commercial', 'faq.print'], $_locale);
        $seo->addJsonLd($this->webApplication($_locale, $seo->canonical));
        $seo->addJsonLd($this->faqPage($faq));
        $seo->addJsonLd($this->webSite($_locale));

        return $this->render('tool/home.html.twig', $this->toolContext($request, $tool, $_locale) + [
            'seo' => $seo,
            'faq' => $faq,
            'is_home' => true,
        ])->setPublic()->setMaxAge(600)->setSharedMaxAge(3600);
    }

    #[Route(path: [
        'en' => '/en/tools',
        'fr' => '/fr/outils',
        'es' => '/es/herramientas',
        'de' => '/de/werkzeuge',
        'it' => '/it/strumenti',
    ], name: 'app_tools', methods: ['GET'])]
    public function index(string $_locale): Response
    {
        $seo = $this->seoFor(
            'app_tools',
            $_locale,
            $this->title($this->translator->trans('tools.meta_title', [], locale: $_locale)),
            $this->translator->trans('tools.meta_description', [], locale: $_locale),
        );
        $seo->breadcrumbs = [
            [$this->translator->trans('nav.home', [], locale: $_locale), $this->seo->url('app_home', ['_locale' => $_locale])],
            [$this->translator->trans('nav.tools', [], locale: $_locale), $seo->canonical],
        ];
        $seo->addJsonLd($this->breadcrumbList($seo->breadcrumbs));
        $seo->addJsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => array_values(array_map(
                fn (Tool $tool, int $i) => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $this->translator->trans('tool.'.$tool->id.'.name', [], locale: $_locale),
                    'url' => $this->seo->absolute($this->toolUrls->path($tool, $_locale)),
                ],
                array_values($this->registry->standalone()),
                array_keys(array_values($this->registry->standalone())),
            )),
        ]);

        return $this->render('tool/index.html.twig', [
            'seo' => $seo,
            'tools' => $this->registry->standalone(),
        ])->setPublic()->setMaxAge(600)->setSharedMaxAge(3600);
    }

    #[Route(path: [
        'en' => '/en/tools/{slug}',
        'fr' => '/fr/outils/{slug}',
        'es' => '/es/herramientas/{slug}',
        'de' => '/de/werkzeuge/{slug}',
        'it' => '/it/strumenti/{slug}',
    ], name: 'app_tool', methods: ['GET'])]
    public function tool(Request $request, string $_locale, string $slug): Response
    {
        $tool = $this->registry->getBySlug($slug, $_locale);
        if (null === $tool) {
            throw $this->createNotFoundException(\sprintf('No tool "%s" for locale "%s".', $slug, $_locale));
        }

        $name = $this->translator->trans('tool.'.$tool->id.'.name', [], locale: $_locale);
        $canonical = $this->seo->absolute($this->toolUrls->path($tool, $_locale));

        $seo = new Seo(
            title: $this->title($this->translator->trans('tool.'.$tool->id.'.meta_title', [], locale: $_locale)),
            description: $this->translator->trans('tool.'.$tool->id.'.meta_description', [], locale: $_locale),
            canonical: $canonical,
            alternates: array_map(
                fn (string $path) => $this->seo->absolute($path),
                $this->toolUrls->paths($tool, ...$this->enabledLocales),
            ),
        );

        // Pre-filled URLs are useful to share, but must never compete with the
        // clean tool page in the index.
        if ($request->query->count() > 0) {
            $seo->robots = 'noindex, follow';
        }

        $seo->breadcrumbs = [
            [$this->translator->trans('nav.home', [], locale: $_locale), $this->seo->url('app_home', ['_locale' => $_locale])],
            [$this->translator->trans('nav.tools', [], locale: $_locale), $this->seo->url('app_tools', ['_locale' => $_locale])],
            [$name, $canonical],
        ];

        $faq = $this->faqEntries(['faq.'.$tool->id, 'faq.free', 'faq.expire', 'faq.print'], $_locale);
        $seo->addJsonLd($this->breadcrumbList($seo->breadcrumbs));
        $seo->addJsonLd($this->faqPage($faq));
        $seo->addJsonLd($this->howTo($tool, $this->translator->trans('tool.'.$tool->id.'.object', [], locale: $_locale), $_locale, $canonical));

        return $this->render('tool/show.html.twig', $this->toolContext($request, $tool, $_locale) + [
            'seo' => $seo,
            'faq' => $faq,
            'is_home' => false,
        ])->setPublic()->setMaxAge(600)->setSharedMaxAge(3600);
    }

    /** @return array<string, mixed> */
    private function toolContext(Request $request, Tool $tool, string $locale): array
    {
        $options = RenderOptions::fromRequest($request, (string) $request->query->get('format', 'png'));
        $submitted = $this->hasSubmittedValues($tool, $request);
        $values = $submitted
            ? FieldValues::fromRequest($tool, $request)
            : FieldValues::fromArray($tool, $this->demo->values($tool));

        $error = null;
        $previewUrl = null;
        $previewOptions = new RenderOptions(
            format: 'svg',
            size: 'barcode' === $tool->kind ? 600 : 512,
            margin: $options->margin,
            ecc: $options->ecc,
            foreground: $options->foreground,
            background: $options->background,
            transparent: $options->transparent,
        );

        try {
            // Validate here so the page can show a translated message, but let the
            // cacheable image endpoint do the actual rendering.
            $this->payloadFactory->build($tool, $values);
            $previewUrl = $this->toolUrls->imagePath($tool, $values, $previewOptions);
        } catch (InvalidPayloadException $e) {
            $error = $this->translator->trans($e->translationKey, array_map(
                fn (string $v) => str_starts_with($v, 'field.') ? $this->translator->trans($v, [], locale: $locale) : $v,
                $e->parameters,
            ), locale: $locale);
        }

        return [
            'tool' => $tool,
            'tools' => $this->registry->standalone(),
            'values' => $values,
            'options' => $options,
            'preview_url' => $previewUrl,
            'error' => $error,
            'is_demo' => !$submitted,
            'downloads' => [
                'png' => $this->toolUrls->imagePath($tool, $values, new RenderOptions('png', 1024, $options->margin, $options->ecc, $options->foreground, $options->background, $options->transparent), true),
                'svg' => $this->toolUrls->imagePath($tool, $values, new RenderOptions('svg', 1024, $options->margin, $options->ecc, $options->foreground, $options->background, $options->transparent), true),
                'webp' => $this->toolUrls->imagePath($tool, $values, new RenderOptions('webp', 1024, $options->margin, $options->ecc, $options->foreground, $options->background, $options->transparent), true),
            ],
        ];
    }

    private function hasSubmittedValues(Tool $tool, Request $request): bool
    {
        foreach ($tool->fields as $field) {
            if ($request->query->has($field->name)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<mixed> */
    private function webApplication(string $locale, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => $this->siteName,
            'url' => $url,
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'Any',
            'browserRequirements' => 'Requires JavaScript for live preview',
            'inLanguage' => $locale,
            'description' => $this->translator->trans('home.meta_description', [], locale: $locale),
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
            'featureList' => array_values(array_map(
                fn (Tool $tool) => $this->translator->trans('tool.'.$tool->id.'.name', [], locale: $locale),
                $this->registry->standalone(),
            )),
        ];
    }

    /** @return array<mixed> */
    private function webSite(string $locale): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $this->seo->absolute('/#website'),
            'name' => $this->siteName,
            'url' => $this->seo->url('app_home', ['_locale' => $locale]),
            'inLanguage' => $locale,
            'publisher' => $this->organization(),
        ];
    }

    /** @return array<mixed> */
    private function howTo(Tool $tool, string $name, string $locale, string $url): array
    {
        $steps = [];
        foreach (['howto.step1', 'howto.step2', 'howto.step3'] as $i => $key) {
            $steps[] = [
                '@type' => 'HowToStep',
                'position' => $i + 1,
                'name' => $this->translator->trans($key.'.title', ['%tool%' => $name], locale: $locale),
                'text' => $this->translator->trans($key.'.text', ['%tool%' => $name], locale: $locale),
                'url' => $url.'#how-to',
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $this->translator->trans('howto.title', ['%tool%' => $name], locale: $locale),
            'totalTime' => 'PT1M',
            'estimatedCost' => ['@type' => 'MonetaryAmount', 'currency' => 'EUR', 'value' => '0'],
            'step' => $steps,
        ];
    }
}
