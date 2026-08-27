<?php

declare(strict_types=1);

namespace App\Lead;

enum LeadCaptureMode: string
{
    /** No email is ever asked for. */
    case Off = 'off';

    /** A discreet opt-in form is shown next to the downloads. */
    case Optional = 'optional';

    /** The visitor is asked for an email the first time they download a code. */
    case Download = 'download';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? self::Off;
    }
}
