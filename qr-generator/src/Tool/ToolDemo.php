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
            'text' => ['text' => 'Hello 👋'],
            'wifi' => ['ssid' => 'MyNetwork', 'password' => 'super-secret', 'encryption' => 'WPA'],
            'vcard' => ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'phone' => '+33123456789', 'email' => 'ada@example.com', 'organization' => 'Analytical Engines'],
            'whatsapp' => ['phone' => '+33123456789', 'message' => 'Hello'],
            default => [],
        };
    }
}
