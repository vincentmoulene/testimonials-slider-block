<?php

declare(strict_types=1);

namespace App\Lead;

final class InvalidLeadException extends \RuntimeException
{
    public function __construct(public readonly string $translationKey)
    {
        parent::__construct($translationKey);
    }
}
