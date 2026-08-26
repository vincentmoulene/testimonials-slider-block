<?php

declare(strict_types=1);

namespace App\Generator;

final readonly class RenderedCode
{
    public function __construct(
        public string $data,
        public string $mimeType,
        public string $extension,
    ) {
    }

    public function dataUri(): string
    {
        return 'data:'.$this->mimeType.';base64,'.base64_encode($this->data);
    }
}
