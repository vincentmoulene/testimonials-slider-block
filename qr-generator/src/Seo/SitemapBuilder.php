<?php

declare(strict_types=1);

namespace App\Seo;

use App\Blog\ArticleRepository;
use App\Tool\ToolRegistry;
use App\Tool\ToolUrlGenerator;

/**
 * One sitemap for the whole site, with xhtml:link alternates on every URL so
 * search engines cluster the five language versions instead of treating them
 * as duplicates.
 */
final readonly class SitemapBuilder
{
    /** @param array<int, string> $enabledLocales */
    public function __construct(
        private SeoFactory $seo,
        private ToolRegistry $tools,
        private ToolUrlGenerator $toolUrls,
        private ArticleRepository $articles,
        private array $enabledLocales,
    ) {
    }

    /** @return array<int, SitemapEntry> */
    public function build(): array
    {
        $entries = [];

        $simpleRoutes = [
            'app_home' => ['daily', 1.0],
            'app_tools' => ['weekly', 0.9],
            'app_blog' => ['daily', 0.8],
            'app_about' => ['monthly', 0.3],
            'app_privacy' => ['yearly', 0.2],
            'app_terms' => ['yearly', 0.2],
            'app_cookies' => ['yearly', 0.2],
        ];

        foreach ($simpleRoutes as $route => [$changeFrequency, $priority]) {
            $alternates = $this->seo->alternates($route);
            foreach ($this->enabledLocales as $locale) {
                $entries[] = new SitemapEntry($alternates[$locale], $alternates, changeFrequency: $changeFrequency, priority: $priority);
            }
        }

        foreach ($this->tools->all() as $tool) {
            $alternates = array_map(
                fn (string $path) => $this->seo->absolute($path),
                $this->toolUrls->paths($tool, ...$this->enabledLocales),
            );
            foreach ($this->enabledLocales as $locale) {
                $entries[] = new SitemapEntry($alternates[$locale], $alternates, changeFrequency: 'weekly', priority: 0.9);
            }
        }

        $seen = [];
        foreach ($this->enabledLocales as $locale) {
            foreach ($this->articles->findByLocale($locale) as $article) {
                if (isset($seen[$article->key][$locale])) {
                    continue;
                }
                $alternates = [];
                foreach ($this->articles->translations($article->key, ...$this->enabledLocales) as $altLocale => $slug) {
                    $alternates[$altLocale] = $this->seo->url('app_article', ['_locale' => $altLocale, 'slug' => $slug]);
                    $seen[$article->key][$altLocale] = true;
                }
                foreach ($alternates as $altLocale => $url) {
                    $entries[] = new SitemapEntry(
                        $url,
                        $alternates,
                        lastModified: $this->articles->findByKey($altLocale, $article->key)?->updatedAt,
                        changeFrequency: 'monthly',
                        priority: 0.7,
                    );
                }
            }
        }

        return $entries;
    }
}
