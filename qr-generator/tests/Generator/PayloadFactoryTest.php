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

    // -- The generic generator: one field, anything inside ------------------

    public function testTheGenericGeneratorKeepsPlainTextAsTyped(): void
    {
        self::assertSame('Hello world', $this->build('qrcode', ['content' => 'Hello world']));
    }

    public function testTheGenericGeneratorTurnsABareDomainIntoALink(): void
    {
        self::assertSame('https://example.com/page', $this->build('qrcode', ['content' => 'example.com/page']));
    }

    public function testTheGenericGeneratorLeavesAnExplicitSchemeAlone(): void
    {
        self::assertSame('https://example.com', $this->build('qrcode', ['content' => 'https://example.com']));
        self::assertSame('mailto:a@b.com', $this->build('qrcode', ['content' => 'mailto:a@b.com']));
    }

    public function testAnEmptyGenericContentIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('qrcode', ['content' => '   ']);
    }

    public function testTooLongContentIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('qrcode', ['content' => str_repeat('a', 2000)]);
    }

    // -- Text ---------------------------------------------------------------

    public function testTextIsEncodedVerbatim(): void
    {
        self::assertSame("Ligne 1\nLigne 2", $this->build('text', ['text' => "Ligne 1\nLigne 2"]));
    }

    // -- Wi-Fi --------------------------------------------------------------

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

    public function testWifiWithoutASsidIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('wifi', ['ssid' => '', 'password' => 'x']);
    }

    // -- vCard --------------------------------------------------------------

    public function testVcardIsWellFormed(): void
    {
        $payload = $this->build('vcard', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'phone' => '+33 1 23 45 67 89',
            'email' => 'ada@example.com',
            'website' => 'example.com',
        ]);

        self::assertStringStartsWith("BEGIN:VCARD\nVERSION:3.0", $payload);
        self::assertStringContainsString('N:Lovelace;Ada;;;', $payload);
        self::assertStringContainsString('FN:Ada Lovelace', $payload);
        self::assertStringContainsString('TEL;TYPE=CELL:+33123456789', $payload);
        self::assertStringContainsString('EMAIL;TYPE=INTERNET:ada@example.com', $payload);
        self::assertStringContainsString('URL:https://example.com', $payload);
        self::assertStringEndsWith('END:VCARD', $payload);
    }

    public function testAVcardWithAnInvalidEmailIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('vcard', ['first_name' => 'Ada', 'email' => 'not-an-email']);
    }

    public function testAVcardKeepsANationalPhoneNumberAsTyped(): void
    {
        self::assertStringContainsString('TEL;TYPE=CELL:0123456789', $this->build('vcard', ['first_name' => 'Ada', 'phone' => '01 23 45 67 89']));
    }

    // -- WhatsApp -----------------------------------------------------------

    public function testWhatsAppBuildsAWaMeLink(): void
    {
        self::assertSame(
            'https://wa.me/33123456789?text=Bonjour',
            $this->build('whatsapp', ['phone' => '+33 1 23 45 67 89', 'message' => 'Bonjour']),
        );
    }

    public function testWhatsAppRefusesANationalNumber(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('whatsapp', ['phone' => '0123456789']);
    }

    // -- Registry -----------------------------------------------------------

    public function testTheRegistryOnlyExposesTheFiveGenerators(): void
    {
        self::assertSame(['qrcode', 'text', 'wifi', 'vcard', 'whatsapp'], array_keys($this->registry->all()));
        self::assertSame(['text', 'wifi', 'vcard', 'whatsapp'], array_keys($this->registry->standalone()));
    }
}
