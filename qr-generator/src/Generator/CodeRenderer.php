<?php

declare(strict_types=1);

namespace App\Generator;

use App\Tool\Tool;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WebPWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Picqer\Barcode\Renderers\PngRenderer as BarcodePngRenderer;
use Picqer\Barcode\Renderers\SvgRenderer as BarcodeSvgRenderer;
use Picqer\Barcode\Types\TypeCode128;
use Picqer\Barcode\Types\TypeCode39;
use Picqer\Barcode\Types\TypeEan13;
use Picqer\Barcode\Types\TypeEan8;
use Picqer\Barcode\Types\TypeInterface;
use Picqer\Barcode\Types\TypeITF14;
use Picqer\Barcode\Types\TypeUpcA;

/**
 * Renders a payload as a QR code or a 1D barcode. Pure computation: nothing is
 * stored, nothing is sent anywhere, which is what makes the free tier viable.
 */
final class CodeRenderer
{
    private const SYMBOLOGIES = [
        'EAN13' => TypeEan13::class,
        'EAN8' => TypeEan8::class,
        'UPCA' => TypeUpcA::class,
        'CODE128' => TypeCode128::class,
        'CODE39' => TypeCode39::class,
        'ITF14' => TypeITF14::class,
    ];

    public function render(Tool $tool, string $payload, RenderOptions $options, FieldValues $values): RenderedCode
    {
        return 'barcode' === $tool->kind
            ? $this->renderBarcode($payload, $options, $values->get('symbology'))
            : $this->renderQrCode($payload, $options);
    }

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

    public function renderBarcode(string $payload, RenderOptions $options, string $symbology): RenderedCode
    {
        $class = self::SYMBOLOGIES[strtoupper($symbology)] ?? null;
        if (null === $class) {
            throw new InvalidPayloadException('error.unknown_symbology');
        }

        /** @var TypeInterface $type */
        $type = new $class();

        try {
            $barcode = $type->getBarcode($payload);
        } catch (\Throwable) {
            throw new InvalidPayloadException('error.invalid_barcode_value', ['%symbology%' => strtoupper($symbology)]);
        }

        $width = max(200, min(2000, $options->size));
        $height = (int) round($width / 3);

        if ('svg' === $options->format) {
            $renderer = (new BarcodeSvgRenderer())
                ->setForegroundColor($options->foregroundRgb())
                ->setBackgroundColor($options->transparent ? null : $options->backgroundRgb());

            return new RenderedCode($renderer->render($barcode, $width, $height), 'image/svg+xml', 'svg');
        }

        $renderer = (new BarcodePngRenderer())
            ->setForegroundColor($options->foregroundRgb())
            ->setBackgroundColor($options->transparent ? null : $options->backgroundRgb());

        return new RenderedCode($renderer->render($barcode, $width, $height), 'image/png', 'png');
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
