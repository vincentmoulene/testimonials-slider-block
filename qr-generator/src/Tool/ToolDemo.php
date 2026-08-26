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
            'url' => ['url' => rtrim($this->siteUrl, '/')],
            'text' => ['text' => 'Hello 👋'],
            'wifi' => ['ssid' => 'MyNetwork', 'password' => 'super-secret', 'encryption' => 'WPA'],
            'vcard' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'phone' => '+33123456789', 'email' => 'ada@example.com', 'organization' => 'Analytical Engines'],
            'email' => ['email' => 'hello@example.com', 'subject' => 'Hello'],
            'sms' => ['phone' => '+33123456789', 'message' => 'Hello'],
            'phone' => ['phone' => '+33123456789'],
            'whatsapp' => ['phone' => '+33123456789', 'message' => 'Hello'],
            'geo' => ['latitude' => '48.8584', 'longitude' => '2.2945'],
            'barcode' => ['value' => '5901234123457', 'symbology' => 'EAN13'],
            default => [],
        };
    }
}
