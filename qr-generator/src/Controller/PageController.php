<?php

declare(strict_types=1);

namespace App\Controller;

use App\Blog\PageRepository;
use App\Seo\SeoFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PageController extends SiteController
{
    public function __construct(
        SeoFactory $seo,
        TranslatorInterface $translator,
        string $siteName,
        private readonly PageRepository $pages,
    ) {
        parent::__construct($seo, $translator, $siteName);
    }

    #[Route(path: [
        'en' => '/en/about',
        'fr' => '/fr/a-propos',
        'es' => '/es/sobre-nosotros',
        'de' => '/de/ueber-uns',
        'it' => '/it/chi-siamo',
    ], name: 'app_about', methods: ['GET'])]
    public function about(string $_locale): Response
    {
        return $this->page('about', 'app_about', $_locale);
    }

    #[Route(path: [
        'en' => '/en/privacy-policy',
        'fr' => '/fr/politique-de-confidentialite',
        'es' => '/es/politica-de-privacidad',
        'de' => '/de/datenschutz',
        'it' => '/it/informativa-sulla-privacy',
    ], name: 'app_privacy', methods: ['GET'])]
    public function privacy(string $_locale): Response
    {
        return $this->page('privacy', 'app_privacy', $_locale);
    }

    #[Route(path: [
        'en' => '/en/terms-of-use',
        'fr' => '/fr/conditions-d-utilisation',
        'es' => '/es/condiciones-de-uso',
        'de' => '/de/nutzungsbedingungen',
        'it' => '/it/condizioni-d-uso',
    ], name: 'app_terms', methods: ['GET'])]
    public function terms(string $_locale): Response
    {
        return $this->page('terms', 'app_terms', $_locale);
    }

    #[Route(path: [
        'en' => '/en/cookies',
        'fr' => '/fr/cookies',
        'es' => '/es/cookies',
        'de' => '/de/cookies',
        'it' => '/it/cookie',
    ], name: 'app_cookies', methods: ['GET'])]
    public function cookies(string $_locale): Response
    {
        return $this->page('cookies', 'app_cookies', $_locale);
    }

    private function page(string $name, string $route, string $locale): Response
    {
        $page = $this->pages->find($name, $locale);
        if (null === $page) {
            throw $this->createNotFoundException();
        }

        $seo = $this->seoFor($route, $locale, $this->title($page->title), $page->description);
        $seo->breadcrumbs = [
            [$this->translator->trans('nav.home', [], locale: $locale), $this->seo->url('app_home', ['_locale' => $locale])],
            [$page->title, $seo->canonical],
        ];
        $seo->addJsonLd($this->breadcrumbList($seo->breadcrumbs));

        return $this->render('page/show.html.twig', [
            'seo' => $seo,
            'page' => $page,
        ])->setPublic()->setMaxAge(3600)->setSharedMaxAge(86400);
    }
}
