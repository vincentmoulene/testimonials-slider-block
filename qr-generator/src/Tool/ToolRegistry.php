<?php

declare(strict_types=1);

namespace App\Tool;

/**
 * Single source of truth for every generator exposed by the site.
 *
 * Each tool owns a *translated* slug so that every locale gets a keyword-rich,
 * indexable landing page (/en/tools/wifi-qr-code, /fr/outils/qr-code-wifi, …).
 */
final class ToolRegistry
{
    /** @var array<string, Tool>|null */
    private ?array $tools = null;

    /** @return array<string, Tool> */
    public function all(): array
    {
        return $this->tools ??= $this->build();
    }

    /**
     * Tools that own a landing page. The generic generator is excluded: it is
     * the home page, and giving it a second URL would duplicate it.
     *
     * @return array<string, Tool>
     */
    public function standalone(): array
    {
        return array_filter($this->all(), static fn (Tool $t) => $t->standalone);
    }

    /** @return array<int, Tool> */
    public function featured(): array
    {
        return array_values(array_filter($this->all(), static fn (Tool $t) => $t->featured && $t->standalone));
    }

    public function generic(): Tool
    {
        return $this->get('qrcode') ?? throw new \LogicException('The generic generator is missing.');
    }

    public function get(string $id): ?Tool
    {
        return $this->all()[$id] ?? null;
    }

    public function getBySlug(string $slug, string $locale): ?Tool
    {
        foreach ($this->standalone() as $tool) {
            if ($tool->slug($locale) === $slug) {
                return $tool;
            }
        }

        return null;
    }

    /** @return array<string, array<string, string>> tool id => (locale => slug) */
    public function slugMap(): array
    {
        return array_map(static fn (Tool $t) => $t->slugs, $this->all());
    }

    /** @return array<string, Tool> */
    private function build(): array
    {
        $tools = [
            new Tool(
                id: 'qrcode',
                slugs: [
                    'en' => 'qr-code-generator',
                    'fr' => 'generateur-qr-code',
                    'es' => 'generador-codigo-qr',
                    'de' => 'qr-code-generator',
                    'it' => 'generatore-qr-code',
                ],
                fields: [new Field('content', 'textarea', true, maxLength: 1800)],
                icon: 'qr',
                standalone: false,
            ),
            new Tool(
                id: 'text',
                slugs: [
                    'en' => 'text-qr-code-generator',
                    'fr' => 'qr-code-texte',
                    'es' => 'codigo-qr-texto',
                    'de' => 'text-qr-code-generator',
                    'it' => 'qr-code-testo',
                ],
                fields: [new Field('text', 'textarea', true, maxLength: 1800)],
                icon: 'text',
                featured: true,
            ),
            new Tool(
                id: 'wifi',
                slugs: [
                    'en' => 'wifi-qr-code-generator',
                    'fr' => 'qr-code-wifi',
                    'es' => 'codigo-qr-wifi',
                    'de' => 'wlan-qr-code-generator',
                    'it' => 'qr-code-wifi',
                ],
                fields: [
                    new Field('ssid', 'text', true, maxLength: 64, wide: false),
                    new Field('password', 'text', maxLength: 64, wide: false),
                    new Field('encryption', 'choice', choices: ['WPA', 'WEP', 'nopass'], default: 'WPA', wide: false),
                    new Field('hidden', 'checkbox', wide: false),
                ],
                icon: 'wifi',
                featured: true,
            ),
            new Tool(
                id: 'vcard',
                slugs: [
                    'en' => 'vcard-qr-code-generator',
                    'fr' => 'qr-code-vcard-contact',
                    'es' => 'codigo-qr-vcard',
                    'de' => 'visitenkarte-qr-code',
                    'it' => 'qr-code-vcard',
                ],
                fields: [
                    new Field('first_name', 'text', true, maxLength: 64, wide: false),
                    new Field('last_name', 'text', maxLength: 64, wide: false),
                    new Field('phone', 'tel', maxLength: 32, wide: false),
                    new Field('email', 'email', maxLength: 128, wide: false),
                    new Field('organization', 'text', maxLength: 128, wide: false),
                    new Field('job_title', 'text', maxLength: 128, wide: false),
                    new Field('website', 'url', maxLength: 256),
                ],
                icon: 'card',
                featured: true,
            ),
            new Tool(
                id: 'whatsapp',
                slugs: [
                    'en' => 'whatsapp-qr-code-generator',
                    'fr' => 'qr-code-whatsapp',
                    'es' => 'codigo-qr-whatsapp',
                    'de' => 'whatsapp-qr-code-generator',
                    'it' => 'qr-code-whatsapp',
                ],
                fields: [
                    new Field('phone', 'tel', true, maxLength: 32, wide: false),
                    new Field('message', 'textarea', maxLength: 500),
                ],
                icon: 'chat',
                featured: true,
            ),
        ];

        $indexed = [];
        foreach ($tools as $tool) {
            $indexed[$tool->id] = $tool;
        }

        return $indexed;
    }
}
