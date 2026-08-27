<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\PublicLink;
use App\Repository\PublicLinkRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GalleryTest extends WebTestCase
{
    private KernelBrowser $client;
    private PublicLinkRepository $links;
    private EntityManagerInterface $manager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $links = static::getContainer()->get(PublicLinkRepository::class);
        self::assertInstanceOf(PublicLinkRepository::class, $links);
        $this->links = $links;

        $manager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $this->manager = $manager;
        $this->manager->createQuery('DELETE FROM '.PublicLink::class)->execute();
    }

    public function testDownloadingALinkAddsItToTheGallery(): void
    {
        $this->download('qrcode', ['content' => 'https://example.com/promo']);

        self::assertResponseIsSuccessful();
        $link = $this->links->findOneBy(['url' => 'https://example.com/promo']);
        self::assertNotNull($link);
        self::assertSame('example.com', $link->getHost());
        self::assertSame(1, $link->getHits());
    }

    public function testTheSameLinkIsCountedRatherThanDuplicated(): void
    {
        $this->download('qrcode', ['content' => 'https://example.com/promo']);
        $this->download('qrcode', ['content' => 'https://example.com/promo']);

        self::assertCount(1, $this->links->findAll());
        self::assertSame(2, $this->links->findAll()[0]->getHits());
    }

    /** The whole point of the filter: private payloads never become public. */
    public function testAWifiOrVcardDownloadIsNeverListed(): void
    {
        $this->download('wifi', ['ssid' => 'MyNet', 'password' => 'super-secret', 'encryption' => 'WPA']);
        $this->download('vcard', ['first_name' => 'Ada', 'phone' => '+33123456789']);
        $this->download('text', ['text' => 'a private note']);

        self::assertCount(0, $this->links->findAll());
    }

    public function testAPreviewDoesNotPublishAnything(): void
    {
        // No download=1: the visitor is still typing.
        $this->client->request('GET', '/q/svg?t=qrcode&content=https%3A%2F%2Fexample.com%2Fdraft');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $this->links->findAll());
    }

    public function testTheHomePageShowsTheLatestLinks(): void
    {
        foreach (range(1, 30) as $i) {
            $this->download('qrcode', ['content' => \sprintf('https://example.com/page-%d', $i)]);
        }

        $crawler = $this->client->request('GET', '/fr');

        self::assertResponseIsSuccessful();
        self::assertCount(25, $crawler->filter('.link-grid li'), 'The home page shows the 25 most recent');
        self::assertStringContainsString('example.com/page-30', $crawler->filter('.link-grid')->text());
    }

    public function testTheGalleryListsSearchesAndPaginates(): void
    {
        $this->download('qrcode', ['content' => 'https://example.com/menu']);
        $this->download('qrcode', ['content' => 'https://autre.example/contact']);

        $crawler = $this->client->request('GET', '/fr/qr-codes-crees');
        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('.link-grid li'));
        self::assertStringContainsString('noindex', (string) $crawler->filter('meta[name="robots"]')->attr('content'));

        $crawler = $this->client->request('GET', '/fr/qr-codes-crees?q=menu');
        self::assertCount(1, $crawler->filter('.link-grid li'));

        $crawler = $this->client->request('GET', '/fr/qr-codes-crees?q=rien-du-tout');
        self::assertCount(0, $crawler->filter('.link-grid li'));
    }

    public function testABlockedLinkDisappearsFromEveryListing(): void
    {
        $this->download('qrcode', ['content' => 'https://example.com/bad']);

        $link = $this->links->findOneBy(['url' => 'https://example.com/bad']);
        self::assertNotNull($link);
        $link->block();
        $this->manager->flush();

        self::assertCount(0, $this->links->latest());
        self::assertSame(0, $this->links->countAll());
    }

    public function testTheGalleryIsReachableInEveryLocale(): void
    {
        foreach (['/en/qr-codes', '/fr/qr-codes-crees', '/es/codigos-qr-creados', '/de/erstellte-qr-codes', '/it/qr-code-creati'] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
        }
    }

    /** @param array<string, string> $values */
    private function download(string $tool, array $values): void
    {
        $this->client->request('GET', '/q/png?'.http_build_query(['t' => $tool, 'download' => '1'] + $values));
    }
}
