<?php

declare(strict_types=1);

namespace App\Tests\Generator;

use App\Generator\CodeRenderer;
use App\Generator\FieldValues;
use App\Generator\RenderOptions;
use App\Tool\ToolRegistry;
use PHPUnit\Framework\TestCase;

final class CodeRendererTest extends TestCase
{
    public function testPngQrCodeIsProduced(): void
    {
        $renderer = new CodeRenderer();
        $result = $renderer->renderQrCode('https://example.com', new RenderOptions('png', 256));

        self::assertSame('image/png', $result->mimeType);
        self::assertStringStartsWith("\x89PNG", $result->data);
    }

    public function testSvgQrCodeIsProduced(): void
    {
        $result = (new CodeRenderer())->renderQrCode('https://example.com', new RenderOptions('svg', 256));

        self::assertSame('image/svg+xml', $result->mimeType);
        self::assertStringContainsString('<svg', $result->data);
    }

    public function testBarcodeIsProduced(): void
    {
        $registry = new ToolRegistry();
        $tool = $registry->get('barcode');
        self::assertNotNull($tool);

        $values = FieldValues::fromArray($tool, ['value' => '5901234123457', 'symbology' => 'EAN13']);
        $result = (new CodeRenderer())->render($tool, '5901234123457', new RenderOptions('svg', 400), $values);

        self::assertSame('image/svg+xml', $result->mimeType);
        self::assertStringContainsString('<svg', $result->data);
    }

    public function testColoursAreParsedFromHex(): void
    {
        $options = new RenderOptions(foreground: '#ff0000', background: '#00ff00');

        self::assertSame([255, 0, 0], $options->foregroundRgb());
        self::assertSame([0, 255, 0], $options->backgroundRgb());
    }
}
