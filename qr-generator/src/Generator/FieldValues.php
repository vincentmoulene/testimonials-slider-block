<?php

declare(strict_types=1);

namespace App\Generator;

use App\Tool\Tool;
use Symfony\Component\HttpFoundation\Request;

/**
 * Normalised, trimmed access to the values submitted for a tool's fields.
 */
final readonly class FieldValues
{
    /** @param array<string, string> $values */
    private function __construct(private array $values)
    {
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(Tool $tool, array $raw): self
    {
        $values = [];
        foreach ($tool->fields as $field) {
            $value = $raw[$field->name] ?? $field->default ?? '';
            if (\is_bool($value)) {
                $value = $value ? '1' : '';
            }
            if (!\is_scalar($value)) {
                $value = '';
            }
            $values[$field->name] = trim((string) $value);
        }

        return new self($values);
    }

    public static function fromRequest(Tool $tool, Request $request): self
    {
        return self::fromArray($tool, $request->query->all() + $request->request->all());
    }

    public function get(string $name): string
    {
        return $this->values[$name] ?? '';
    }

    public function bool(string $name): bool
    {
        return \in_array(strtolower($this->get($name)), ['1', 'true', 'on', 'yes'], true);
    }

    /** @return array<string, string> */
    public function all(): array
    {
        return $this->values;
    }
}
