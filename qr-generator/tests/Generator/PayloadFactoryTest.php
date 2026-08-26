<?php

declare(strict_types=1);

namespace App\Tests\Generator;

use App\Generator\FieldValues;
use App\Generator\InvalidPayloadException;
use App\Generator\PayloadFactory;
use App\Tool\Tool;
use App\Tool\ToolRegistry;
use PHPUnit\Framework\TestCase;

final class PayloadFactoryTest extends TestCase
{
    private PayloadFactory $factory;
    private ToolRegistry $registry;

    protected function setUp(): void
    {
        $this->factory = new PayloadFactory();
        $this->registry = new ToolRegistry();
    }

    /** @param array<string, string> $values */
    private function build(string $toolId, array $values): string
    {
        $tool = $this->tool($toolId);

        return $this->factory->build($tool, FieldValues::fromArray($tool, $values));
    }

    private function tool(string $id): Tool
    {
        return $this->registry->get($id) ?? throw new \LogicException('Unknown tool '.$id);
    }

    public function testUrlGetsHttpsWhenSchemeIsMissing(): void
    {
        self::assertSame('https://example.com/page', $this->build('url', ['url' => 'example.com/page']));
    }

    public function testUrlKeepsExplicitScheme(): void
    {
        self::assertSame('http://example.com', $this->build('url', ['url' => 'http://example.com']));
    }

    public function testEmptyUrlIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('url', ['url' => '']);
    }

    public function testWifiPayloadFollowsTheStandard(): void
    {
        self::assertSame(
            'WIFI:T:WPA;S:MyNet;P:secret;;',
            $this->build('wifi', ['ssid' => 'MyNet', 'password' => 'secret', 'encryption' => 'WPA']),
        );
    }

    public function testWifiEscapesReservedCharacters(): void
    {
        $payload = $this->build('wifi', ['ssid' => 'Cafe;Bar', 'password' => 'a:b,c', 'encryption' => 'WPA']);

        self::assertSame('WIFI:T:WPA;S:Cafe\;Bar;P:a\:b\,c;;', $payload);
    }

    public function testWifiOpenNetworkOmitsThePassword(): void
    {
        self::assertSame('WIFI:T:nopass;S:Guest;;', $this->build('wifi', ['ssid' => 'Guest', 'encryption' => 'nopass']));
    }

    public function testWifiHiddenFlag(): void
    {
        self::assertStringContainsString('H:true', $this->build('wifi', ['ssid' => 'Guest', 'password' => 'x', 'hidden' => '1']));
    }

    public function testVcardIsWellFormed(): void
    {
        $payload = $this->build('vcard', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'phone' => '+33 1 23 45 67 89',
            'email' => 'ada@example.com',
        ]);

        self::assertStringStartsWith("BEGIN:VCARD\nVERSION:3.0", $payload);
        self::assertStringContainsString('N:Lovelace;Ada;;;', $payload);
        self::assertStringContainsString('FN:Ada Lovelace', $payload);
        self::assertStringContainsString('TEL;TYPE=CELL:+33123456789', $payload);
        self::assertStringEndsWith('END:VCARD', $payload);
    }

    public function testEmailPayloadEncodesSubjectAndBody(): void
    {
        $payload = $this->build('email', ['email' => 'a@b.com', 'subject' => 'Hé ho', 'body' => 'Bonjour']);

        self::assertStringStartsWith('mailto:a@b.com?', $payload);
        self::assertStringContainsString('subject=H%C3%A9%20ho', $payload);
        self::assertStringContainsString('body=Bonjour', $payload);
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('email', ['email' => 'not-an-email']);
    }

    public function testWhatsAppBuildsAWaMeLink(): void
    {
        self::assertSame(
            'https://wa.me/33123456789?text=Bonjour',
            $this->build('whatsapp', ['phone' => '+33 1 23 45 67 89', 'message' => 'Bonjour']),
        );
    }

    public function testGeoRejectsOutOfRangeCoordinates(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('geo', ['latitude' => '120', 'longitude' => '0']);
    }

    public function testGeoAcceptsCommaDecimalSeparator(): void
    {
        self::assertSame('geo:48.8584,2.2945', $this->build('geo', ['latitude' => '48,8584', 'longitude' => '2,2945']));
    }

    public function testSmsPayload(): void
    {
        self::assertSame('SMSTO:+33123456789:Hello', $this->build('sms', ['phone' => '0033123456789', 'message' => 'Hello']));
    }

    public function testBarcodeRejectsAWrongLengthEan(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('barcode', ['value' => '123', 'symbology' => 'EAN13']);
    }

    public function testBarcodeAcceptsAValidEan(): void
    {
        self::assertSame('5901234123457', $this->build('barcode', ['value' => '5901234123457', 'symbology' => 'EAN13']));
    }

    public function testTooLongTextIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('text', ['text' => str_repeat('a', 2000)]);
    }
}
