<?php

declare(strict_types=1);

namespace App\Tests\Link;

use App\Link\LinkNormaliser;
use PHPUnit\Framework\TestCase;

final class LinkNormaliserTest extends TestCase
{
    /** @param array<int, string> $blockedHosts */
    private function publisher(array $blockedHosts = []): LinkNormaliser
    {
        return new LinkNormaliser($blockedHosts);
    }

    public function testAnOrdinaryLinkIsPublishable(): void
    {
        self::assertSame('https://example.com/promo', $this->publisher()->normalise('https://example.com/promo'));
    }

    public function testTheHostIsLowercasedAndTheRestKeptIntact(): void
    {
        self::assertSame(
            'https://example.com/Promo?a=B#Frag',
            $this->publisher()->normalise('https://EXAMPLE.com/Promo?a=B#Frag'),
        );
    }

    /** Wi-Fi, vCard, plain text, tel: — none of it is a link, none of it is listed. */
    public function testANonHttpPayloadIsNeverPublished(): void
    {
        foreach (['WIFI:T:WPA;S:MyNet;P:secret;;', "BEGIN:VCARD\nVERSION:3.0\nEND:VCARD", 'Just a note', 'tel:+33123456789', 'mailto:a@b.com'] as $payload) {
            self::assertNull($this->publisher()->normalise($payload), $payload);
        }
    }

    public function testCredentialsInTheUrlAreNeverPublished(): void
    {
        self::assertNull($this->publisher()->normalise('https://user:pass@example.com/private'));
    }

    public function testAUrlCarryingASecretParameterIsNotPublished(): void
    {
        foreach (['token', 'access_token', 'key', 'signature', 'invite', 'password'] as $parameter) {
            self::assertNull(
                $this->publisher()->normalise(\sprintf('https://example.com/doc?%s=abc123', $parameter)),
                $parameter,
            );
        }
    }

    public function testAnOrdinaryQueryStringStaysPublishable(): void
    {
        self::assertSame(
            'https://example.com/p?utm_source=poster&page=2',
            $this->publisher()->normalise('https://example.com/p?utm_source=poster&page=2'),
        );
    }

    public function testLocalAndPrivateAddressesAreNotPublished(): void
    {
        foreach ([
            'http://192.168.1.1/router',
            'http://localhost/admin',
            'http://intranet/wiki',
            'https://printer.local/setup',
            'https://staging.test/page',
        ] as $url) {
            self::assertNull($this->publisher()->normalise($url), $url);
        }
    }

    public function testABlockedHostCoversItsSubdomains(): void
    {
        $publisher = $this->publisher(['spam.example']);

        self::assertNull($publisher->normalise('https://spam.example/x'));
        self::assertNull($publisher->normalise('https://sub.spam.example/x'));
        self::assertSame('https://notspam.example/x', $publisher->normalise('https://notspam.example/x'));
    }

    public function testAnOverlongUrlIsNotPublished(): void
    {
        self::assertNull($this->publisher()->normalise('https://example.com/'.str_repeat('a', 1100)));
    }
}
