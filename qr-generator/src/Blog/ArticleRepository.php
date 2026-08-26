<?php

declare(strict_types=1);

namespace App\Blog;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * File-based blog: articles are Markdown files with a YAML front matter, one
 * directory per locale. No database to provision, no admin to secure, and the
 * whole content set is versioned with the code.
 */
final class ArticleRepository
{
    /** @var array<string, array<string, Article>> locale => (slug => article) */
    private array $loaded = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly string $contentDir,
    ) {
    }

    /** @return array<int, Article> newest first */
    public function findByLocale(string $locale): array
    {
        $articles = array_values($this->load($locale));
        usort($articles, static fn (Article $a, Article $b) => $b->publishedAt <=> $a->publishedAt);

        return $articles;
    }

    public function findOne(string $locale, string $slug): ?Article
    {
        return $this->load($locale)[$slug] ?? null;
    }

    public function findByKey(string $locale, string $key): ?Article
    {
        foreach ($this->load($locale) as $article) {
            if ($article->key === $key) {
                return $article;
            }
        }

        return null;
    }

    /**
     * @return array<string, string> locale => slug, for every locale the article exists in
     */
    public function translations(string $key, string ...$locales): array
    {
        $map = [];
        foreach ($locales as $locale) {
            $article = $this->findByKey($locale, $key);
            if (null !== $article) {
                $map[$locale] = $article->slug;
            }
        }

        return $map;
    }

    /** @return array<int, Article> */
    public function related(Article $article, int $limit = 3): array
    {
        $others = array_filter(
            $this->findByLocale($article->locale),
            static fn (Article $a) => $a->key !== $article->key,
        );

        usort($others, static function (Article $a, Article $b) use ($article) {
            $sharedA = \count(array_intersect($a->tags, $article->tags));
            $sharedB = \count(array_intersect($b->tags, $article->tags));

            return [$sharedB, $b->publishedAt] <=> [$sharedA, $a->publishedAt];
        });

        return \array_slice($others, 0, $limit);
    }

    /** @return array<string, Article> slug => article */
    private function load(string $locale): array
    {
        if (isset($this->loaded[$locale])) {
            return $this->loaded[$locale];
        }

        $dir = $this->contentDir.'/blog/'.$locale;
        if (!is_dir($dir)) {
            return $this->loaded[$locale] = [];
        }

        $articles = [];
        foreach (Finder::create()->files()->in($dir)->name('*.md')->sortByName() as $file) {
            $item = $this->cache->getItem('article.'.$locale.'.'.md5($file->getPathname()).'.'.$file->getMTime());
            if (!$item->isHit()) {
                $item->set($this->parse($locale, $file->getFilename(), (string) file_get_contents($file->getPathname())));
                $this->cache->save($item);
            }
            /** @var Article $article */
            $article = $item->get();
            $articles[$article->slug] = $article;
        }

        return $this->loaded[$locale] = $articles;
    }

    private function parse(string $locale, string $filename, string $raw): Article
    {
        $raw = str_replace("\r\n", "\n", $raw);
        if (!preg_match('/^---\n(.*?)\n---\n(.*)$/s', $raw, $matches)) {
            throw new \RuntimeException(\sprintf('Article "%s/%s" is missing its YAML front matter.', $locale, $filename));
        }

        /** @var array<string, mixed> $meta */
        $meta = Yaml::parse($matches[1], Yaml::PARSE_DATETIME) ?? [];
        $body = trim($matches[2]);

        $published = $this->toDate($meta['date'] ?? null) ?? new \DateTimeImmutable('now');

        return new Article(
            key: (string) ($meta['key'] ?? pathinfo($filename, \PATHINFO_FILENAME)),
            locale: $locale,
            slug: (string) ($meta['slug'] ?? pathinfo($filename, \PATHINFO_FILENAME)),
            title: (string) ($meta['title'] ?? ''),
            description: (string) ($meta['description'] ?? ''),
            publishedAt: $published,
            updatedAt: $this->toDate($meta['updated'] ?? null) ?? $published,
            tags: array_map(strval(...), (array) ($meta['tags'] ?? [])),
            html: $this->converter()->convert($body)->getContent(),
            readingTime: max(1, (int) ceil(str_word_count(strip_tags($body)) / 200)),
            relatedTool: isset($meta['tool']) ? (string) $meta['tool'] : null,
        );
    }

    private function toDate(mixed $value): ?\DateTimeImmutable
    {
        return match (true) {
            $value instanceof \DateTimeImmutable => $value,
            $value instanceof \DateTimeInterface => \DateTimeImmutable::createFromInterface($value),
            \is_int($value) => (new \DateTimeImmutable())->setTimestamp($value),
            \is_string($value) && '' !== $value => new \DateTimeImmutable($value),
            default => null,
        };
    }

    private function converter(): MarkdownConverter
    {
        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'heading_permalink' => ['symbol' => '#', 'insert' => 'after', 'aria_hidden' => true],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new HeadingPermalinkExtension());

        return new MarkdownConverter($environment);
    }
}
