<?php

declare(strict_types=1);

namespace App\Controller;

use App\Seo\SeoFactory;
use App\Seo\SitemapBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SeoController extends AbstractController
{
    /** @param array<int, string> $enabledLocales */
    public function __construct(
        private readonly SitemapBuilder $sitemap,
        private readonly SeoFactory $seo,
        private readonly array $enabledLocales,
        private readonly string $siteName,
        private readonly string $adsenseClient,
    ) {
    }

    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        $response = $this->render('seo/sitemap.xml.twig', ['entries' => $this->sitemap->build()]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->setPublic()->setSharedMaxAge(3600);

        return $response;
    }

    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'])]
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            // Query-string image URLs are an infinite crawl space with no
            // stand-alone value: keep crawl budget on the landing pages.
            'Disallow: /q/',
            'Disallow: /api/',
            '',
            'Sitemap: '.$this->seo->absolute('/sitemap.xml'),
            '',
        ];

        return new Response(implode("\n", $lines), Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    #[Route('/ads.txt', name: 'app_ads_txt', methods: ['GET'])]
    public function adsTxt(): Response
    {
        if ('' === $this->adsenseClient) {
            throw $this->createNotFoundException();
        }

        $publisherId = str_starts_with($this->adsenseClient, 'ca-') ? substr($this->adsenseClient, 3) : $this->adsenseClient;

        return new Response(
            \sprintf("google.com, %s, DIRECT, f08c47fec0942fa0\n", $publisherId),
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'public, max-age=86400'],
        );
    }

    #[Route('/site.webmanifest', name: 'app_manifest', methods: ['GET'])]
    public function manifest(): Response
    {
        $response = $this->json([
            'name' => $this->siteName,
            'short_name' => $this->siteName,
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#0b0f19',
            'theme_color' => '#0b0f19',
            'icons' => [
                ['src' => '/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any'],
            ],
            'lang' => $this->enabledLocales[0] ?? 'en',
        ]);
        $response->headers->set('Content-Type', 'application/manifest+json');
        $response->setPublic()->setSharedMaxAge(86400);

        return $response;
    }
}
