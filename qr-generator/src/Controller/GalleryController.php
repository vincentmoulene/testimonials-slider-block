<?php

declare(strict_types=1);

namespace App\Controller;

use App\Link\LinkPublisher;
use App\Repository\PublicLinkRepository;
use App\Seo\SeoFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GalleryController extends SiteController
{
    private const PER_PAGE = 60;

    public function __construct(
        SeoFactory $seo,
        TranslatorInterface $translator,
        string $siteName,
        private readonly PublicLinkRepository $links,
        private readonly LinkPublisher $publisher,
    ) {
        parent::__construct($seo, $translator, $siteName);
    }

    #[Route(path: [
        'en' => '/en/qr-codes',
        'fr' => '/fr/qr-codes-crees',
        'es' => '/es/codigos-qr-creados',
        'de' => '/de/erstellte-qr-codes',
        'it' => '/it/qr-code-creati',
    ], name: 'app_gallery', methods: ['GET'])]
    public function index(Request $request, string $_locale): Response
    {
        if (!$this->publisher->isEnabled()) {
            throw $this->createNotFoundException();
        }

        $search = trim((string) $request->query->get('q', ''));
        $result = $this->links->paginate($request->query->getInt('page', 1), self::PER_PAGE, $search);

        $seo = $this->seoFor(
            'app_gallery',
            $_locale,
            $this->title($this->translator->trans('links.meta_title', [], locale: $_locale)),
            $this->translator->trans('links.meta_description', [], locale: $_locale),
        );

        // Visitor-submitted links: useful to browse, not something to push into
        // the index, and never a source of link equity for the destinations.
        $seo->robots = 'noindex, follow';

        $seo->breadcrumbs = [
            [$this->translator->trans('nav.home', [], locale: $_locale), $this->seo->url('app_home', ['_locale' => $_locale])],
            [$this->translator->trans('nav.gallery', [], locale: $_locale), $seo->canonical],
        ];

        return $this->render('gallery/index.html.twig', [
            'seo' => $seo,
            'links' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'search' => $search,
        ])->setPublic()->setMaxAge(60)->setSharedMaxAge(300);
    }
}
