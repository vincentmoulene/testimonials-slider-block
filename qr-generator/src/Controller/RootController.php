<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RootController extends AbstractController
{
    /** @param array<int, string> $enabledLocales */
    public function __construct(
        private readonly array $enabledLocales,
        private readonly string $defaultLocale,
    ) {
    }

    /**
     * The bare domain never serves content itself: it redirects to the best
     * matching localised home page, which is the only indexable version.
     */
    #[Route('/', name: 'app_root', methods: ['GET'])]
    public function index(Request $request): RedirectResponse
    {
        $preferred = $request->getPreferredLanguage($this->enabledLocales) ?? $this->defaultLocale;

        return $this->redirectToRoute('app_home', ['_locale' => $preferred], Response::HTTP_FOUND);
    }
}
