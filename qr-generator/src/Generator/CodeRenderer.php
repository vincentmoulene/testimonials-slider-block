<?php

declare(strict_types=1);

namespace App\Generator;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WebPWriter;
use Endroid\QrCode\Writer\WriterInterface;

/**
 * Renders a payload as a QR code. Pure computation: nothing is stored, nothing
 * is sent anywhere, which is what makes the free tier viable.
 */
final class CodeRenderer
{
    public function renderQrCode(string $payload, RenderOptions $options): RenderedCode
    {
        [$fr, $fg, $fb] = $options->foregroundRgb();
        [$br, $bg, $bb] = $options->backgroundRgb();

        $writer = $this->writer($options->format);
        $result = (new Builder(
            writer: $writer,
            writerOptions: $writer instanceof SvgWriter ? [SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => false] : [],
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: match ($options->ecc) {
                'L' => ErrorCorrectionLevel::Low,
                'Q' => ErrorCorrectionLevel::Quartile,
                'H' => ErrorCorrectionLevel::High,
                default => ErrorCorrectionLevel::Medium,
            },
            size: $options->size,
            margin: $options->margin,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color($fr, $fg, $fb),
            backgroundColor: new Color($br, $bg, $bb, $options->transparent && 'svg' !== $options->format ? 127 : 0),
        ))->build();

        return new RenderedCode($result->getString(), $result->getMimeType(), $options->format);
    }

    private function writer(string $format): WriterInterface
    {
        return match ($format) {
            'svg' => new SvgWriter(),
            'webp' => new WebPWriter(),
            default => new PngWriter(),
        };
    }
}
