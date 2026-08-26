<?php

declare(strict_types=1);

namespace App\Tool;

use App\Generator\FieldValues;
use App\Generator\RenderOptions;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ToolUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ToolRegistry $registry,
    ) {
    }

    public function path(Tool|string $tool, string $locale): string
    {
        $tool = \is_string($tool) ? ($this->registry->get($tool) ?? throw new \InvalidArgumentException('Unknown tool: '.$tool)) : $tool;

        return $this->urlGenerator->generate('app_tool', ['_locale' => $locale, 'slug' => $tool->slug($locale)]);
    }

    /** @return array<string, string> locale => path */
    public function paths(Tool $tool, string ...$locales): array
    {
        $paths = [];
        foreach ($locales as $locale) {
            $paths[$locale] = $this->path($tool, $locale);
        }

        return $paths;
    }

    /**
     * URL of the image endpoint for a given tool + values. Everything lives in
     * the query string so the response is a plain, cacheable GET.
     */
    public function imagePath(Tool $tool, FieldValues $values, RenderOptions $options, bool $download = false): string
    {
        $query = array_filter($values->all(), static fn (string $v) => '' !== $v);
        $query['t'] = $tool->id;
        $query['size'] = (string) $options->size;
        $query['margin'] = (string) $options->margin;
        $query['ecc'] = $options->ecc;
        $query['fg'] = $options->foreground;
        $query['bg'] = $options->background;
        if ($options->transparent) {
            $query['transparent'] = '1';
        }
        if ($download) {
            $query['download'] = '1';
        }

        return $this->urlGenerator->generate('app_generate', ['format' => $options->format] + $query);
    }
}
