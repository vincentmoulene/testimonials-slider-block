<?php

declare(strict_types=1);

namespace App\Generator;

final class InvalidPayloadException extends \InvalidArgumentException
{
    /** @param array<string, string> $parameters */
    public function __construct(
        public readonly string $translationKey,
        public readonly array $parameters = [],
    ) {
        parent::__construct($translationKey);
    }
}
