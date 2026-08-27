<?php

declare(strict_types=1);

namespace App\Tool;

/**
 * Sensible sample values so every tool page renders a real, scannable preview
 * for first-time visitors, crawlers and no-JavaScript browsers.
 */
final readonly class ToolDemo
{
    public function __construct(private string $siteUrl)
    {
    }

    /** @return array<string, string> */
    public function values(Tool $tool): array
    {
        return match ($tool->id) {
            'qrcode' => ['content' => rtrim($this->siteUrl, '/')],
            'url', 'review' => ['url' => rtrim($this->siteUrl, '/')],
            'event' => ['event_title' => 'Product launch', 'start' => '2026-09-15T18:30', 'end' => '2026-09-15T21:00', 'location' => 'Paris'],
            'bitcoin' => ['address' => 'bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq', 'amount' => '0.01'],
            'text' => ['text' => 'Hello 👋'],
            'wifi' => ['ssid' => 'MyNetwork', 'password' => 'super-secret', 'encryption' => 'WPA'],
            'vcard' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'phone' => '+33123456789', 'email' => 'ada@example.com', 'organization' => 'Analytical Engines'],
            'email' => ['email' => 'hello@example.com', 'subject' => 'Hello'],
            'sms' => ['phone' => '+33123456789', 'message' => 'Hello'],
            'phone' => ['phone' => '+33123456789'],
            'whatsapp' => ['phone' => '+33123456789', 'message' => 'Hello'],
            'geo' => ['latitude' => '48.8584', 'longitude' => '2.2945'],
            'sepa' => ['beneficiary_name' => 'Association Exemple', 'iban' => 'FR7630006000011234567890189', 'amount_eur' => '25.00', 'remittance' => 'Don 2026'],
            'totp' => ['issuer' => 'Acme', 'account' => 'ada@example.com', 'secret' => 'JBSWY3DPEHPK3PXP'],
            'crypto' => ['chain' => 'ETH', 'address' => '0x71C7656EC7ab88b098defB751B7401B5f6d8976F', 'amount' => '0.05'],
            'telegram' => ['username' => 'telegram'],
            'signal' => ['phone' => '+33123456789'],
            'directions' => ['destination' => 'Tour Eiffel, Paris', 'travel_mode' => 'driving'],
            'barcode' => ['value' => '5901234123457', 'symbology' => 'EAN13'],
            default => [],
        };
    }
}
