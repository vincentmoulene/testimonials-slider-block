<?php

declare(strict_types=1);

namespace App\Controller;

use App\Blog\Article;
use App\Blog\ArticleRepository;
use App\Seo\Seo;
use App\Seo\SeoFactory;
use App\Tool\ToolRegistry;
use App\Tool\ToolUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class BlogController extends SiteController
{
    /** @param array<int, string> $enabledLocales */
    public function __construct(
        SeoFactory $seo,
        TranslatorInterface $translator,
        string $siteName,
        private readonly ArticleRepository $articles,
        private readonly ToolRegistry $tools,
        private readonly ToolUrlGenerator $toolUrls,
        private readonly array $enabledLocales,
    ) {
        parent::__construct($seo, $translator, $siteName);
    }

    #[Route(path: [
        'en' => '/en/blog',
        'fr' => '/fr/blog',
        'es' => '/es/blog',
        'de' => '/de/blog',
        'it' => '/it/blog',
    ], name: 'app_blog', methods: ['GET'])]
    public function index(string $_locale): Response
    {
        $seo = $this->seoFor(
            'app_blog',
            $_locale,
            $this->title($this->translator->trans('blog.meta_title', [], locale: $_locale)),
            $this->translator->trans('blog.meta_description', [], locale: $_locale),
        );
        $seo->breadcrumbs = [
            [$this->translator->trans('nav.home', [], locale: $_locale), $this->seo->url('app_home', ['_locale' => $_locale])],
            [$this->translator->trans('nav.blog', [], locale: $_locale), $seo->canonical],
        ];
        $seo->addJsonLd($this->breadcrumbList($seo->breadcrumbs));

        return $this->render('blog/index.html.twig', [
            'seo' => $seo,
            'articles' => $this->articles->findByLocale($_locale),
        ])->setPublic()->setMaxAge(600)->setSharedMaxAge(3600);
    }

    #[Route(path: [
        'en' => '/en/blog/{slug}',
        'fr' => '/fr/blog/{slug}',
        'es' => '/es/blog/{slug}',
        'de' => '/de/blog/{slug}',
        'it' => '/it/blog/{slug}',
    ], name: 'app_article', methods: ['GET'])]
    public function show(string $_locale, string $slug): Response
    {
        $article = $this->articles->findOne($_locale, $slug);
        if (null === $article) {
            throw $this->createNotFoundException();
        }

        $canonical = $this->seo->url('app_article', ['_locale' => $_locale, 'slug' => $article->slug]);
        $alternates = [];
        foreach ($this->articles->translations($article->key, ...$this->enabledLocales) as $locale => $translatedSlug) {
            $alternates[$locale] = $this->seo->url('app_article', ['_locale' => $locale, 'slug' => $translatedSlug]);
        }

        $seo = new Seo(
            title: $this->title($article->title),
            description: $article->description,
            canonical: $canonical,
            alternates: $alternates,
            type: 'article',
            publishedAt: $article->publishedAt->format(\DATE_ATOM),
            updatedAt: $article->updatedAt->format(\DATE_ATOM),
        );
        $seo->breadcrumbs = [
            [$this->translator->trans('nav.home', [], locale: $_locale), $this->seo->url('app_home', ['_locale' => $_locale])],
            [$this->translator->trans('nav.blog', [], locale: $_locale), $this->seo->url('app_blog', ['_locale' => $_locale])],
            [$article->title, $canonical],
        ];
        $seo->addJsonLd($this->breadcrumbList($seo->breadcrumbs));
        $seo->addJsonLd($this->articleSchema($article, $canonical));

        return $this->render('blog/show.html.twig', [
            'seo' => $seo,
            'article' => $article,
            'related' => $this->articles->related($article),
            'related_tool' => $article->relatedTool ? $this->tools->get($article->relatedTool) : null,
        ])->setPublic()->setMaxAge(600)->setSharedMaxAge(3600);
    }

    #[Route(path: [
        'en' => '/en/feed.xml',
        'fr' => '/fr/feed.xml',
        'es' => '/es/feed.xml',
        'de' => '/de/feed.xml',
        'it' => '/it/feed.xml',
    ], name: 'app_feed', methods: ['GET'])]
    public function feed(string $_locale): Response
    {
        $response = $this->render('blog/feed.xml.twig', [
            'articles' => $this->articles->findByLocale($_locale),
            'locale' => $_locale,
            'self_url' => $this->seo->url('app_feed', ['_locale' => $_locale]),
            'home_url' => $this->seo->url('app_home', ['_locale' => $_locale]),
        ]);
        $response->headers->set('Content-Type', 'application/atom+xml; charset=UTF-8');
        $response->setPublic()->setSharedMaxAge(3600);

        return $response;
    }

    /** @return array<mixed> */
    private function articleSchema(Article $article, string $canonical): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $article->title,
            'description' => $article->description,
            'inLanguage' => $article->locale,
            'datePublished' => $article->publishedAt->format(\DATE_ATOM),
            'dateModified' => $article->updatedAt->format(\DATE_ATOM),
            'keywords' => implode(', ', $article->tags),
            'wordCount' => str_word_count(strip_tags($article->html)),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
            'author' => $this->organization(),
            'publisher' => $this->organization(),
        ];
    }
}
