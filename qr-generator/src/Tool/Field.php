<?php

declare(strict_types=1);

namespace App\Tool;

final readonly class Field
{
    /** @param array<int, string> $choices */
    public function __construct(
        public string $name,
        public string $type = 'text',
        public bool $required = false,
        public array $choices = [],
        public ?string $default = null,
        public int $maxLength = 512,
        public bool $wide = true,
    ) {
    }
}
