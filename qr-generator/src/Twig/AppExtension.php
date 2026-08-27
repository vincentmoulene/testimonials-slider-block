<?php

declare(strict_types=1);

namespace App\Twig;

use App\Tool\Tool;
use App\Tool\ToolRegistry;
use App\Tool\ToolUrlGenerator;
use Symfony\Component\Intl\Languages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly ToolUrlGenerator $toolUrls,
        private readonly ToolRegistry $tools,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tool_path', $this->toolPath(...)),
            new TwigFunction('tools', $this->allTools(...)),
            new TwigFunction('featured_tools', $this->featuredTools(...)),
            new TwigFunction('locale_name', $this->localeName(...)),
            new TwigFunction('icon', $this->icon(...), ['is_safe' => ['html']]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_ld', $this->jsonLd(...), ['is_safe' => ['html']]),
        ];
    }

    public function toolPath(Tool|string $tool, string $locale): string
    {
        return $this->toolUrls->path($tool, $locale);
    }

    /** @return array<string, Tool> */
    public function allTools(): array
    {
        return $this->tools->all();
    }

    /** @return array<int, Tool> */
    public function featuredTools(): array
    {
        return $this->tools->featured();
    }

    public function localeName(string $locale, ?string $displayLocale = null): string
    {
        return ucfirst(Languages::getName($locale, $displayLocale ?? $locale));
    }

    /** @param array<mixed> $data */
    public function jsonLd(array $data): string
    {
        return json_encode($data, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_HEX_TAG | \JSON_HEX_AMP | \JSON_HEX_APOS | \JSON_HEX_QUOT) ?: '{}';
    }

    public function icon(string $name, int $size = 24): string
    {
        $paths = [
            'bank' => '<path d="M3 10h18"/><path d="M12 3 3 8h18z"/><path d="M6 10v7"/><path d="M10 10v7"/><path d="M14 10v7"/><path d="M18 10v7"/><path d="M3 20h18"/>',
            'key' => '<circle cx="8" cy="14" r="4"/><path d="M11 11 20 2"/><path d="M17 5l2 2"/><path d="M14 8l2 2"/>',
            'send' => '<path d="M22 2 11 13"/><path d="M22 2l-7 20-4-9-9-4z"/>',
            'navigation' => '<polygon points="3 11 22 2 13 21 11 13 3 11"/>',
            'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3z"/><path d="M20 14v3"/><path d="M14 20h3"/><path d="M20 20h1"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4"/><path d="M8 3v4"/><path d="M3 11h18"/>',
            'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
            'coin' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 8.5h4a2 2 0 0 1 0 4h-4h4a2 2 0 0 1 0 4h-4"/><path d="M9.5 8.5V16"/><path d="M11 6.5V8.5"/><path d="M11 16v2"/>',
            'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
            'text' => '<path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/>',
            'wifi' => '<path d="M5 13a10 10 0 0 1 14 0"/><path d="M8.5 16.5a5 5 0 0 1 7 0"/><path d="M2 8.82a15 15 0 0 1 20 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>',
            'card' => '<rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M5 17c.7-1.6 2-2.4 3.5-2.4S11.3 15.4 12 17"/><line x1="15" y1="9" x2="19" y2="9"/><line x1="15" y1="13" x2="19" y2="13"/>',
            'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>',
            'sms' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
            'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.35 1.9.66 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.31 1.85.53 2.81.66A2 2 0 0 1 22 16.92z"/>',
            'chat' => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8z"/>',
            'pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
            'barcode' => '<path d="M3 5v14"/><path d="M7 5v14"/><path d="M11 5v14"/><path d="M14 5v14"/><path d="M18 5v14"/><path d="M21 5v14"/>',
            'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
            'check' => '<polyline points="20 6 9 17 4 12"/>',
            'globe' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>',
            'bolt' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
            'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'arrow' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        ];

        return \sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
            $size,
            $paths[$name] ?? $paths['link'],
        );
    }
}
