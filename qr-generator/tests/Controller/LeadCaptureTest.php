<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Lead;
use App\Repository\LeadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LeadCaptureTest extends WebTestCase
{
    private KernelBrowser $client;
    private LeadRepository $leads;
    private string $clientIp;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // The endpoint is rate limited per IP, and the limiter state outlives a
        // single test: give every test its own address so they stay independent.
        $this->clientIp = '203.0.113.'.(hexdec(substr(md5($this->name()), 0, 2)) % 254 + 1);

        $leads = static::getContainer()->get(LeadRepository::class);
        self::assertInstanceOf(LeadRepository::class, $leads);
        $this->leads = $leads;

        $manager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        $manager->createQuery('DELETE FROM '.Lead::class)->execute();
    }

    public function testAnEmailIsStoredAndNormalised(): void
    {
        $this->post(['email' => '  Ada@Example.COM ', 'locale' => 'fr', 'tool' => 'wifi', 'consent' => true, 'source' => 'https://example.com/fr']);

        self::assertResponseIsSuccessful();
        self::assertSame(['ok' => true], $this->json());

        $lead = $this->leads->findOneBy(['email' => 'ada@example.com']);
        self::assertNotNull($lead);
        self::assertSame('fr', $lead->getLocale());
        self::assertSame('wifi', $lead->getTool());
        self::assertSame('https://example.com/fr', $lead->getSource());
        self::assertTrue($lead->hasConsentedToMarketing());
        self::assertSame(1, $lead->getGenerationCount());
        self::assertNotNull($lead->getIpHash(), 'The IP is kept as a keyed hash, never in clear text');
        self::assertStringNotContainsString('127.0.0.1', (string) $lead->getIpHash());
    }

    public function testASecondDownloadCountsInsteadOfDuplicating(): void
    {
        $this->post(['email' => 'ada@example.com', 'locale' => 'fr', 'tool' => 'wifi', 'consent' => true]);
        $this->post(['email' => 'ada@example.com', 'locale' => 'en', 'tool' => 'vcard']);

        self::assertCount(1, $this->leads->findAll());

        $lead = $this->leads->findOneBy(['email' => 'ada@example.com']);
        self::assertNotNull($lead);
        self::assertSame(2, $lead->getGenerationCount());
        self::assertSame('vcard', $lead->getTool(), 'The most recent generator is kept');
        self::assertTrue($lead->hasConsentedToMarketing(), 'A later download must not silently revoke consent');
    }

    public function testMarketingConsentIsSeparateFromTheDownload(): void
    {
        $this->post(['email' => 'no-news@example.com', 'locale' => 'en', 'tool' => 'url']);

        $lead = $this->leads->findOneBy(['email' => 'no-news@example.com']);
        self::assertNotNull($lead);
        self::assertFalse($lead->hasConsentedToMarketing());
    }

    public function testAnInvalidEmailIsRejectedWithATranslatedMessage(): void
    {
        $this->post(['email' => 'not-an-email', 'locale' => 'fr']);

        self::assertResponseStatusCodeSame(422);
        $body = $this->json();
        self::assertFalse($body['ok']);
        self::assertStringContainsString('valide', $body['error']);
        self::assertCount(0, $this->leads->findAll());
    }

    public function testTheHoneypotSilentlyDiscardsBots(): void
    {
        $this->post(['email' => 'bot@example.com', 'locale' => 'en', 'company' => 'ACME SEO Ltd']);

        self::assertResponseIsSuccessful();
        self::assertSame(['ok' => true], $this->json(), 'A bot must not be able to tell it was caught');
        self::assertCount(0, $this->leads->findAll());
    }

    public function testAnUnknownToolFallsBackToTheGenericGenerator(): void
    {
        $this->post(['email' => 'ada@example.com', 'locale' => 'en', 'tool' => 'not-a-tool']);

        $lead = $this->leads->findOneBy(['email' => 'ada@example.com']);
        self::assertNotNull($lead);
        self::assertSame('qrcode', $lead->getTool());
    }

    public function testTheDownloadGateIsRenderedOnEveryGeneratorPage(): void
    {
        foreach (['/fr', '/en/tools/wifi-qr-code-generator'] as $path) {
            $crawler = $this->client->request('GET', $path);

            self::assertResponseIsSuccessful();
            self::assertCount(1, $crawler->filter('dialog.lead-dialog'), $path);
            self::assertCount(1, $crawler->filter('dialog.lead-dialog input[name="email"]'), $path);
            // The honeypot must exist but stay out of the accessibility tree.
            self::assertCount(1, $crawler->filter('dialog.lead-dialog .hp input[name="company"]'), $path);
            self::assertCount(1, $crawler->filter('dialog.lead-dialog input[name="consent"]'), $path);
        }
    }

    public function testForgettingALeadRemovesItCompletely(): void
    {
        $this->post(['email' => 'ada@example.com', 'locale' => 'en', 'tool' => 'url']);

        self::assertTrue($this->leads->deleteByEmail('Ada@Example.com'));
        self::assertCount(0, $this->leads->findAll());
        self::assertFalse($this->leads->deleteByEmail('ada@example.com'));
    }

    public function testTheEndpointIsRateLimitedPerIp(): void
    {
        $ip = '198.51.100.7';

        for ($i = 0; $i < 10; ++$i) {
            $this->post(['email' => \sprintf('flood%d@example.com', $i), 'locale' => 'en'], $ip);
            self::assertResponseIsSuccessful(\sprintf('Request %d should still be allowed', $i + 1));
        }

        $this->post(['email' => 'flood-too-far@example.com', 'locale' => 'en'], $ip);
        self::assertResponseStatusCodeSame(429);
        self::assertNull($this->leads->findOneBy(['email' => 'flood-too-far@example.com']));
    }

    /** @param array<string, mixed> $payload */
    private function post(array $payload, ?string $ip = null): void
    {
        $this->client->request(
            'POST',
            '/api/lead',
            server: ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $ip ?? $this->clientIp],
            content: (string) json_encode($payload),
        );
    }

    /** @return array<string, mixed> */
    private function json(): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return $data;
    }
}
