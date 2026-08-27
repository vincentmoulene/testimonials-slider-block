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
        self::assertSame('mailto:a@b.com', $this->build('qrcode', ['content' => 'mailto:a@b.com']));
    }

    public function testAnEmptyGenericContentIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('qrcode', ['content' => '   ']);
    }

    public function testEventProducesACalendarEntry(): void
    {
        $payload = $this->build('event', [
            'event_title' => 'Launch party',
            'start' => '2026-09-15T18:30',
            'end' => '2026-09-15T21:00',
            'location' => 'Paris',
        ]);

        self::assertStringStartsWith("BEGIN:VEVENT\nSUMMARY:Launch party", $payload);
        self::assertStringContainsString('DTSTART:20260915T183000', $payload);
        self::assertStringContainsString('DTEND:20260915T210000', $payload);
        self::assertStringContainsString('LOCATION:Paris', $payload);
        self::assertStringEndsWith('END:VEVENT', $payload);
    }

    public function testAnEventWithoutAnEndIsValid(): void
    {
        $payload = $this->build('event', ['event_title' => 'Standup', 'start' => '2026-09-15T09:00']);

        self::assertStringNotContainsString('DTEND', $payload);
    }

    public function testBitcoinProducesABip21Uri(): void
    {
        $payload = $this->build('bitcoin', [
            'address' => 'bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq',
            'amount' => '0,015',
            'label' => 'Invoice 42',
        ]);

        self::assertStringStartsWith('bitcoin:bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq?', $payload);
        self::assertStringContainsString('amount=0.015', $payload);
        self::assertStringContainsString('label=Invoice%2042', $payload);
    }

    public function testAnInvalidBitcoinAddressIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('bitcoin', ['address' => 'not-an-address']);
    }

    public function testANegativeBitcoinAmountIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('bitcoin', ['address' => 'bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq', 'amount' => '-1']);
    }

    public function testTheReviewToolValidatesTheLink(): void
    {
        self::assertSame('https://g.page/r/abc/review', $this->build('review', ['url' => 'g.page/r/abc/review']));
    }

    public function testSepaPayloadFollowsTheEpcLayout(): void
    {
        $payload = $this->build('sepa', [
            'beneficiary_name' => 'Association Exemple',
            'iban' => 'FR76 3000 6000 0112 3456 7890 189',
            'amount_eur' => '25',
            'remittance' => 'Don 2026',
        ]);

        self::assertSame([
            'BCD',
            '002',
            '1',
            'SCT',
            '',
            'Association Exemple',
            'FR7630006000011234567890189',
            'EUR25.00',
            '',
            '',
            'Don 2026',
        ], explode("\n", $payload));
        self::assertLessThanOrEqual(331, \strlen($payload), 'The EPC specification caps the payload at 331 bytes');
    }

    public function testSepaOmitsTheTrailingEmptyFields(): void
    {
        $payload = $this->build('sepa', ['beneficiary_name' => 'Acme', 'iban' => 'FR7630006000011234567890189']);

        self::assertSame("BCD\n002\n1\nSCT\n\nAcme\nFR7630006000011234567890189", $payload);
    }

    public function testAnIbanWithAWrongChecksumIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        // Same IBAN as above with the last digit changed.
        $this->build('sepa', ['beneficiary_name' => 'Acme', 'iban' => 'FR7630006000011234567890188']);
    }

    /** The 331-byte cap is on bytes, so multi-byte characters can blow it. */
    public function testAnOverlongSepaPayloadIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('sepa', [
            'beneficiary_name' => str_repeat('é', 70),
            'iban' => 'FR7630006000011234567890189',
            'remittance' => str_repeat('€', 140),
        ]);
    }

    public function testTotpProducesAnOtpauthUri(): void
    {
        $payload = $this->build('totp', ['issuer' => 'Acme', 'account' => 'ada@example.com', 'secret' => 'jbsw y3dp ehpk 3pxp']);

        self::assertStringStartsWith('otpauth://totp/Acme:ada%40example.com?', $payload);
        self::assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $payload, 'The secret is normalised, not passed through');
        self::assertStringContainsString('issuer=Acme', $payload);
        self::assertStringContainsString('algorithm=SHA1&digits=6&period=30', $payload);
    }

    public function testANonBase32SecretIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('totp', ['issuer' => 'Acme', 'account' => 'ada@example.com', 'secret' => 'not-base-32-because-of-1-and-8']);
    }

    public function testEthereumEncodesTheAmountInWei(): void
    {
        $payload = $this->build('crypto', [
            'chain' => 'ETH',
            'address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F',
            'amount' => '0.05',
        ]);

        self::assertSame('ethereum:0x71C7656EC7ab88b098defB751B7401B5f6d8976F@1?value=50000000000000000', $payload);
    }

    public function testLitecoinUsesTheBip21Convention(): void
    {
        $payload = $this->build('crypto', [
            'chain' => 'LTC',
            'address' => 'LhK2kQwiaAvhjWY799cZvMyYwnQAcxkarr',
            'amount' => '2.5',
            'label' => 'Invoice 7',
        ]);

        self::assertSame('litecoin:LhK2kQwiaAvhjWY799cZvMyYwnQAcxkarr?amount=2.5&label=Invoice%207', $payload);
    }

    public function testMoneroUsesItsOwnParameterNames(): void
    {
        $payload = $this->build('crypto', [
            'chain' => 'XMR',
            'address' => '48jewbtxe4jU3MnzJFjTs3gVFWh2nRrbCPQD1TnCe1G3JTFvKZfSrSCEwFvJ3rAEUb4xZcFSpJmY1Sr4kcNgUyDaGSXEXAr',
            'amount' => '1.5',
        ]);

        self::assertStringContainsString('tx_amount=1.5', $payload);
        self::assertStringNotContainsString('amount=1.5&', $payload);
    }

    public function testAnAddressFromTheWrongChainIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('crypto', ['chain' => 'LTC', 'address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F']);
    }

    public function testAnUnknownChainIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('crypto', ['chain' => 'NOPE', 'address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F']);
    }

    public function testTelegramAcceptsAUsernameWithOrWithoutTheAt(): void
    {
        self::assertSame('https://t.me/durov', $this->build('telegram', ['username' => '@durov']));
        self::assertSame('https://t.me/durov', $this->build('telegram', ['username' => 'durov']));
    }

    public function testTelegramAcceptsAPhoneNumber(): void
    {
        self::assertSame('https://t.me/+33612345678', $this->build('telegram', ['username' => '+33 6 12 34 56 78']));
    }

    public function testAnInvalidTelegramUsernameIsRejected(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('telegram', ['username' => 'no']);
    }

    public function testSignalBuildsAContactLink(): void
    {
        self::assertSame('https://signal.me/#p/+33123456789', $this->build('signal', ['phone' => '0033123456789']));
    }

    public function testSignalRefusesANationalNumber(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('signal', ['phone' => '0123456789']);
    }

    public function testDirectionsBuildAUniversalMapsLink(): void
    {
        $payload = $this->build('directions', ['destination' => 'Tour Eiffel, Paris', 'travel_mode' => 'transit']);

        self::assertSame('https://www.google.com/maps/dir/?api=1&destination=Tour%20Eiffel%2C%20Paris&travelmode=transit', $payload);
    }

    public function testDirectionsRejectAnEmptyDestination(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->build('directions', ['destination' => '  ']);
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
