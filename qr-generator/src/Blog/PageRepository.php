<?php

declare(strict_types=1);

namespace App\Blog;

use League\CommonMark\CommonMarkConverter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Static pages (about, legal, cookies…) written in Markdown, one file per
 * locale, falling back to the default locale when a translation is missing.
 */
final class PageRepository
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly string $contentDir,
        private readonly string $defaultLocale,
    ) {
    }

    public function find(string $name, string $locale): ?Page
    {
        foreach ([$locale, $this->defaultLocale] as $candidate) {
            $file = \sprintf('%s/pages/%s/%s.md', $this->contentDir, $candidate, $name);
            if (is_file($file)) {
                $item = $this->cache->getItem('page.'.$candidate.'.'.$name.'.'.filemtime($file));
                if (!$item->isHit()) {
                    $item->set($this->parse($name, $candidate, (string) file_get_contents($file), (int) filemtime($file)));
                    $this->cache->save($item);
                }

                return $item->get();
            }
        }

        return null;
    }

    private function parse(string $name, string $locale, string $raw, int $mtime): Page
    {
        $raw = str_replace("\r\n", "\n", $raw);
        if (!preg_match('/^---\n(.*?)\n---\n(.*)$/s', $raw, $matches)) {
            throw new \RuntimeException(\sprintf('Page "%s/%s" is missing its YAML front matter.', $locale, $name));
        }

        /** @var array<string, mixed> $meta */
        $meta = Yaml::parse($matches[1]) ?? [];

        return new Page(
            name: $name,
            locale: $locale,
            title: (string) ($meta['title'] ?? ''),
            description: (string) ($meta['description'] ?? ''),
            html: (new CommonMarkConverter(['html_input' => 'escape', 'allow_unsafe_links' => false]))->convert(trim($matches[2]))->getContent(),
            updatedAt: (new \DateTimeImmutable())->setTimestamp($mtime),
        );
    }
}
