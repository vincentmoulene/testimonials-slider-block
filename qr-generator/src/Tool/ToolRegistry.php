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

    /** @return array<int, Tool> */
    public function featured(): array
    {
        return array_values(array_filter($this->all(), static fn (Tool $t) => $t->featured));
    }

    public function get(string $id): ?Tool
    {
        return $this->all()[$id] ?? null;
    }

    public function getBySlug(string $slug, string $locale): ?Tool
    {
        foreach ($this->all() as $tool) {
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
                id: 'url',
                kind: 'qr',
                slugs: [
                    'en' => 'url-qr-code-generator',
                    'fr' => 'generateur-qr-code-url',
                    'es' => 'generador-codigo-qr-url',
                    'de' => 'url-qr-code-generator',
                    'it' => 'generatore-qr-code-url',
                ],
                fields: [new Field('url', 'url', true, maxLength: 1800, default: 'https://')],
                icon: 'link',
                featured: true,
            ),
            new Tool(
                id: 'text',
                kind: 'qr',
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
                kind: 'qr',
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
                kind: 'qr',
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
                id: 'email',
                kind: 'qr',
                slugs: [
                    'en' => 'email-qr-code-generator',
                    'fr' => 'qr-code-email',
                    'es' => 'codigo-qr-correo',
                    'de' => 'email-qr-code-generator',
                    'it' => 'qr-code-email',
                ],
                fields: [
                    new Field('email', 'email', true, maxLength: 128),
                    new Field('subject', 'text', maxLength: 160),
                    new Field('body', 'textarea', maxLength: 800),
                ],
                icon: 'mail',
                featured: true,
            ),
            new Tool(
                id: 'sms',
                kind: 'qr',
                slugs: [
                    'en' => 'sms-qr-code-generator',
                    'fr' => 'qr-code-sms',
                    'es' => 'codigo-qr-sms',
                    'de' => 'sms-qr-code-generator',
                    'it' => 'qr-code-sms',
                ],
                fields: [
                    new Field('phone', 'tel', true, maxLength: 32, wide: false),
                    new Field('message', 'textarea', maxLength: 500),
                ],
                icon: 'sms',
            ),
            new Tool(
                id: 'phone',
                kind: 'qr',
                slugs: [
                    'en' => 'phone-qr-code-generator',
                    'fr' => 'qr-code-telephone',
                    'es' => 'codigo-qr-telefono',
                    'de' => 'telefon-qr-code-generator',
                    'it' => 'qr-code-telefono',
                ],
                fields: [new Field('phone', 'tel', true, maxLength: 32)],
                icon: 'phone',
            ),
            new Tool(
                id: 'whatsapp',
                kind: 'qr',
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
            new Tool(
                id: 'geo',
                kind: 'qr',
                slugs: [
                    'en' => 'location-qr-code-generator',
                    'fr' => 'qr-code-geolocalisation',
                    'es' => 'codigo-qr-ubicacion',
                    'de' => 'standort-qr-code-generator',
                    'it' => 'qr-code-posizione',
                ],
                fields: [
                    new Field('latitude', 'text', true, maxLength: 24, wide: false),
                    new Field('longitude', 'text', true, maxLength: 24, wide: false),
                ],
                icon: 'pin',
            ),
            new Tool(
                id: 'barcode',
                kind: 'barcode',
                slugs: [
                    'en' => 'barcode-generator',
                    'fr' => 'generateur-code-barres',
                    'es' => 'generador-codigo-barras',
                    'de' => 'strichcode-generator',
                    'it' => 'generatore-codice-a-barre',
                ],
                fields: [
                    new Field('value', 'text', true, maxLength: 64, wide: false),
                    new Field('symbology', 'choice', choices: ['EAN13', 'EAN8', 'UPCA', 'CODE128', 'CODE39', 'ITF14'], default: 'EAN13', wide: false),
                ],
                icon: 'barcode',
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
