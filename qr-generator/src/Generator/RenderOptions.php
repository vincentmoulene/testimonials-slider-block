<?php

declare(strict_types=1);

namespace App\Generator;

use Symfony\Component\HttpFoundation\Request;

final readonly class RenderOptions
{
    public const FORMATS = ['png', 'svg', 'webp'];
    public const ECC_LEVELS = ['L', 'M', 'Q', 'H'];

    public function __construct(
        public string $format = 'png',
        public int $size = 512,
        public int $margin = 16,
        public string $ecc = 'M',
        public string $foreground = '#111111',
        public string $background = '#ffffff',
        public bool $transparent = false,
    ) {
    }

    public static function fromRequest(Request $request, string $format): self
    {
        return new self(
            format: \in_array($format, self::FORMATS, true) ? $format : 'png',
            size: max(96, min(2000, $request->query->getInt('size', 512))),
            margin: max(0, min(80, $request->query->getInt('margin', 16))),
            ecc: \in_array(strtoupper((string) $request->query->get('ecc', 'M')), self::ECC_LEVELS, true) ? strtoupper((string) $request->query->get('ecc', 'M')) : 'M',
            foreground: self::color($request->query->get('fg'), '#111111'),
            background: self::color($request->query->get('bg'), '#ffffff'),
            transparent: $request->query->getBoolean('transparent'),
        );
    }

    /** @return array{0: int, 1: int, 2: int} */
    public function foregroundRgb(): array
    {
        return self::toRgb($this->foreground);
    }

    /** @return array{0: int, 1: int, 2: int} */
    public function backgroundRgb(): array
    {
        return self::toRgb($this->background);
    }

    private static function color(mixed $value, string $fallback): string
    {
        if (!\is_string($value)) {
            return $fallback;
        }
        $value = '#'.ltrim(trim($value), '#');

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value) ? strtolower($value) : $fallback;
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function toRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (3 === \strlen($hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
