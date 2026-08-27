<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tool\ToolRegistry;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SiteSmokeTest extends WebTestCase
{
    private const LOCALES = ['en', 'fr', 'es', 'de', 'it'];

    public function testRootRedirectsToALocalisedHomePage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects();
        self::assertMatchesRegularExpression('#/(en|fr|es|de|it)$#', (string) $client->getResponse()->headers->get('Location'));
    }

    /** Every locale must expose a complete, self-consistent set of pages. */
    public function testEveryLocaleHomePageIsIndexableAndTranslated(): void
    {
        $client = static::createClient();

        foreach (self::LOCALES as $locale) {
            $crawler = $client->request('GET', '/'.$locale);

            self::assertResponseIsSuccessful(\sprintf('Home page for "%s"', $locale));
            self::assertSame($locale, $crawler->filter('html')->attr('lang'));
            self::assertCount(1, $crawler->filter('h1'));
            self::assertCount(1, $crawler->filter('link[rel="canonical"]'));
            // 5 locales + x-default
            self::assertCount(6, $crawler->filter('link[rel="alternate"][hreflang]'));
            self::assertNotSame('', (string) $crawler->filter('meta[name="description"]')->attr('content'));
            self::assertStringNotContainsString('home.', $crawler->filter('h1')->text(), 'Untranslated key leaked into the page');
        }
    }

    public function testEveryToolPageAnswersInEveryLocale(): void
    {
        $client = static::createClient();
        $registry = static::getContainer()->get(ToolRegistry::class);
        self::assertInstanceOf(ToolRegistry::class, $registry);

        foreach ($registry->standalone() as $tool) {
            foreach (self::LOCALES as $locale) {
                $crawler = $client->request('GET', $this->toolPath($locale, $tool->slug($locale)));

                self::assertResponseIsSuccessful(\sprintf('Tool "%s" in "%s"', $tool->id, $locale));
                self::assertCount(1, $crawler->filter('h1'));
                self::assertGreaterThan(0, $crawler->filter('script[type="application/ld+json"]')->count());
                self::assertGreaterThan(0, $crawler->filter('.generator-preview img')->count(), 'The page must render a preview without JavaScript');
            }
        }
    }

    /** The generic generator is the home page, so its slug must not resolve. */
    public function testTheGenericGeneratorHasNoLandingPageOfItsOwn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/tools/qr-code-generator');

        self::assertResponseStatusCodeSame(404);
    }

    public function testTheHomePageIsTheGenericGenerator(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-generator-tool-value="qrcode"]'));
        // One free-form field, not a specialised form.
        self::assertCount(1, $crawler->filter('form.generator-form textarea[name="content"]'));
    }

    public function testAPrefilledToolPageIsNotIndexable(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/tools/wifi-qr-code-generator?ssid=MyNet');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('noindex', (string) $crawler->filter('meta[name="robots"]')->attr('content'));
    }

    public function testSitemapListsEveryLocaleWithAlternates(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        $xml = (string) $client->getResponse()->getContent();

        foreach (self::LOCALES as $locale) {
            self::assertStringContainsString('<loc>https://example.com/'.$locale.'</loc>', $xml);
        }
        self::assertStringContainsString('hreflang="x-default"', $xml);
        self::assertGreaterThan(50, substr_count($xml, '<loc>'));
    }

    public function testRobotsTxtPointsAtTheSitemapAndProtectsCrawlBudget(): void
    {
        $client = static::createClient();
        $client->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Sitemap: https://example.com/sitemap.xml', $body);
        self::assertStringContainsString('Disallow: /q/', $body);
    }

    public function testBlogAndArticleRender(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fr/blog');
        self::assertResponseIsSuccessful();

        $crawler = $client->request('GET', '/fr/blog/taille-impression-qr-code');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('BlogPosting', $crawler->filter('script[type="application/ld+json"]')->last()->text());
    }

    /** Every article parses, in every language, with its front matter intact. */
    public function testEveryArticleRendersInEveryLocale(): void
    {
        $client = static::createClient();
        $repository = static::getContainer()->get(\App\Blog\ArticleRepository::class);
        self::assertInstanceOf(\App\Blog\ArticleRepository::class, $repository);

        $rendered = 0;
        foreach (self::LOCALES as $locale) {
            $articles = $repository->findByLocale($locale);
            self::assertGreaterThanOrEqual(9, \count($articles), \sprintf('Locale "%s" should carry the use-case articles', $locale));

            foreach ($articles as $article) {
                $crawler = $client->request('GET', \sprintf('/%s/blog/%s', $locale, $article->slug));

                self::assertResponseIsSuccessful($article->slug);
                self::assertNotSame('', $article->title, $article->slug);
                self::assertNotSame('', $article->description, $article->slug);
                self::assertCount(1, $crawler->filter('h1'), $article->slug);
                ++$rendered;
            }
        }

        self::assertGreaterThanOrEqual(49, $rendered);
    }

    /** Articles sharing a key must cross-link, so the languages cluster. */
    public function testTranslatedArticlesDeclareEachOther(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/blog/qr-code-restaurant');

        self::assertResponseIsSuccessful();
        self::assertCount(6, $crawler->filter('link[rel="alternate"][hreflang]'), '5 locales + x-default');
        self::assertSame(
            'https://example.com/de/blog/qr-code-restaurant',
            $crawler->filter('link[hreflang="de"]')->attr('href'),
        );
    }

    public function testLegalPagesRenderInEveryLocale(): void
    {
        $client = static::createClient();
        $paths = [
            'en' => ['/en/privacy-policy', '/en/terms-of-use', '/en/cookies', '/en/about'],
            'fr' => ['/fr/politique-de-confidentialite', '/fr/conditions-d-utilisation', '/fr/cookies', '/fr/a-propos'],
            'es' => ['/es/politica-de-privacidad', '/es/condiciones-de-uso', '/es/cookies', '/es/sobre-nosotros'],
            'de' => ['/de/datenschutz', '/de/nutzungsbedingungen', '/de/cookies', '/de/ueber-uns'],
            'it' => ['/it/informativa-sulla-privacy', '/it/condizioni-d-uso', '/it/cookie', '/it/chi-siamo'],
        ];

        foreach ($paths as $locale => $localePaths) {
            foreach ($localePaths as $path) {
                $client->request('GET', $path);
                self::assertResponseIsSuccessful($path.' ('.$locale.')');
            }
        }
    }

    public function testImageEndpointReturnsACacheableImage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/q/png?t=qrcode&content=https%3A%2F%2Fexample.com');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/png');
        self::assertStringContainsString('immutable', (string) $client->getResponse()->headers->get('Cache-Control'));
    }

    public function testImageEndpointRejectsAnInvalidPayload(): void
    {
        $client = static::createClient();
        $client->request('GET', '/q/png?t=whatsapp&phone=12');

        self::assertResponseStatusCodeSame(400);
    }

    public function testApiReturnsAnInlineImage(): void
    {
        $client = static::createClient();
        $this->postJson($client, ['tool' => 'wifi', 'locale' => 'fr', 'values' => ['ssid' => 'Cafe', 'password' => 'x', 'encryption' => 'WPA']]);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertTrue($data['ok']);
        self::assertStringStartsWith('data:image/svg+xml;base64,', $data['image']);
        self::assertSame('WIFI:T:WPA;S:Cafe;P:x;;', $data['payload']);
    }

    public function testApiErrorsAreTranslated(): void
    {
        $client = static::createClient();
        $this->postJson($client, ['tool' => 'qrcode', 'locale' => 'fr', 'values' => ['content' => '']]);

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        self::assertFalse($data['ok']);
        self::assertStringContainsString('obligatoire', $data['error']);
    }

    public function testUnknownPageReturnsATranslatedNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/fr/blog/does-not-exist');

        self::assertResponseStatusCodeSame(404);
    }

    private function toolPath(string $locale, string $slug): string
    {
        $prefix = ['en' => 'tools', 'fr' => 'outils', 'es' => 'herramientas', 'de' => 'werkzeuge', 'it' => 'strumenti'][$locale];

        return \sprintf('/%s/%s/%s', $locale, $prefix, $slug);
    }

    /** @param array<string, mixed> $payload */
    private function postJson(KernelBrowser $client, array $payload): void
    {
        $client->request('POST', '/api/generate', server: ['CONTENT_TYPE' => 'application/json'], content: (string) json_encode($payload));
    }
}
