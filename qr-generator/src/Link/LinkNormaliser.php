<?php

declare(strict_types=1);

namespace App\Link;

/**
 * Decides what may appear in the public gallery.
 *
 * The gallery only ever shows plain http(s) links. Everything else a visitor can
 * encode — a Wi-Fi password, a contact card, a private note, a phone number — is
 * rejected here, and links that look like they carry a credential are dropped
 * even though they are technically links.
 */
final readonly class LinkNormaliser
{
    /** Query parameters whose presence usually means the URL is a private one-time link. */
    private const SECRET_PARAMETERS = '/^(?:token|secret|password|passwd|pwd|auth|authorization|signature|sig|key|apikey|api_key|session|sessionid|otp|code|access_token|id_token|reset|invite|share)$/i';

    /** @param array<int, string> $blockedHosts */
    public function __construct(private array $blockedHosts)
    {
    }

    /** Returns the URL to publish, or null if it must not be published at all. */
    public function normalise(string $payload): ?string
    {
        $payload = trim($payload);
        if ('' === $payload || mb_strlen($payload) > 1024) {
            return null;
        }

        $parts = parse_url($payload);
        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (!\in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        // Credentials in a URL are a secret by definition: never republish them.
        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $host = strtolower($parts['host']);
        if (!$this->isPublicHost($host) || $this->isBlocked($host)) {
            return null;
        }

        if (isset($parts['query']) && $this->carriesASecret($parts['query'])) {
            return null;
        }

        // Rebuild from the parsed parts so nothing unexpected survives.
        $url = strtolower($parts['scheme']).'://'.$host;
        if (isset($parts['port'])) {
            $url .= ':'.$parts['port'];
        }
        $url .= $parts['path'] ?? '';
        if (isset($parts['query'])) {
            $url .= '?'.$parts['query'];
        }
        if (isset($parts['fragment'])) {
            $url .= '#'.$parts['fragment'];
        }

        return mb_strlen($url) > 1024 ? null : $url;
    }

    private function isPublicHost(string $host): bool
    {
        // A hostname with no dot is a local name; a bare IP is never a site to list.
        if (!str_contains($host, '.') || filter_var($host, \FILTER_VALIDATE_IP)) {
            return false;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.internal') || str_ends_with($host, '.test')) {
            return false;
        }

        return false !== filter_var($host, \FILTER_VALIDATE_DOMAIN, \FILTER_FLAG_HOSTNAME);
    }

    private function isBlocked(string $host): bool
    {
        foreach ($this->blockedHosts as $blocked) {
            $blocked = strtolower(trim($blocked));
            if ('' === $blocked) {
                continue;
            }
            if ($host === $blocked || str_ends_with($host, '.'.$blocked)) {
                return true;
            }
        }

        return false;
    }

    private function carriesASecret(string $query): bool
    {
        parse_str($query, $parameters);
        foreach (array_keys($parameters) as $name) {
            if (preg_match(self::SECRET_PARAMETERS, (string) $name)) {
                return true;
            }
        }

        return false;
    }
}
