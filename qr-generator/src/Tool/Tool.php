<?php

declare(strict_types=1);

namespace App\Tool;

final readonly class Tool
{
    /**
     * @param array<string, string> $slugs  locale => url slug
     * @param array<int, Field>     $fields
     * @param array<int, string>    $faq    translation key suffixes (…question / …answer)
     */
    public function __construct(
        public string $id,
        public string $kind,
        public array $slugs,
        public array $fields,
        public string $icon,
        public array $faq = [],
        public bool $featured = false,
        /** A non-standalone tool has no landing page of its own: it lives on the home page. */
        public bool $standalone = true,
    ) {
    }

    public function slug(string $locale): string
    {
        return $this->slugs[$locale] ?? $this->slugs['en'] ?? $this->id;
    }

    public function field(string $name): ?Field
    {
        foreach ($this->fields as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
    }
}
