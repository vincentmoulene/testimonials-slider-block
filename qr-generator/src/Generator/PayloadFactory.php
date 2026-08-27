<?php

declare(strict_types=1);

namespace App\Generator;

use App\Tool\Tool;

/**
 * Turns the fields of a tool into the raw string that gets encoded in the code.
 *
 * Every payload follows the de-facto standards understood by native iOS/Android
 * camera apps (ZXing "barcode contents" conventions), so no redirect and no
 * tracking domain is ever inserted: the code works forever, even offline.
 */
final class PayloadFactory
{
    public function build(Tool $tool, FieldValues $input): string
    {
        foreach ($tool->fields as $field) {
            $value = $input->get($field->name);
            if ($field->required && '' === trim($value)) {
                throw new InvalidPayloadException('error.field_required', ['%field%' => 'field.'.$field->name]);
            }
            if (mb_strlen($value) > $field->maxLength) {
                throw new InvalidPayloadException('error.field_too_long', ['%field%' => 'field.'.$field->name, '%max%' => (string) $field->maxLength]);
            }
        }

        return match ($tool->id) {
            'qrcode' => $this->generic($input->get('content')),
            'url', 'review' => $this->url($input->get('url')),
            'event' => $this->event($input),
            'bitcoin' => $this->bitcoin($input),
            'text' => $input->get('text'),
            'wifi' => $this->wifi($input),
            'vcard' => $this->vcard($input),
            'email' => $this->email($input),
            'sms' => 'SMSTO:'.$this->phone($input->get('phone')).':'.$input->get('message'),
            'phone' => 'tel:'.$this->phone($input->get('phone')),
            'whatsapp' => $this->whatsapp($input),
            'geo' => $this->geo($input),
            'barcode' => $this->barcode($input),
            default => throw new InvalidPayloadException('error.unknown_tool'),
        };
    }

    /**
     * The generic generator encodes exactly what was typed, with one courtesy:
     * a bare domain becomes a link, because that is always what people mean.
     */
    private function generic(string $content): string
    {
        $content = trim($content);
        if ('' === $content) {
            throw new InvalidPayloadException('error.field_required', ['%field%' => 'field.content']);
        }

        $looksLikeADomain = !str_contains($content, ' ')
            && !preg_match('#^[a-z][a-z0-9+.-]*:#i', $content)
            && preg_match('/^[\w-]+(?:\.[\w-]+)+(?:[\/?#].*)?$/u', $content);

        return $looksLikeADomain ? 'https://'.$content : $content;
    }

    private function event(FieldValues $input): string
    {
        $lines = [
            'BEGIN:VEVENT',
            'SUMMARY:'.$this->escapeVcard($input->get('event_title')),
            'DTSTART:'.$this->icalDate($input->get('start')),
        ];
        if ('' !== $input->get('end')) {
            $lines[] = 'DTEND:'.$this->icalDate($input->get('end'));
        }
        if ('' !== $input->get('location')) {
            $lines[] = 'LOCATION:'.$this->escapeVcard($input->get('location'));
        }
        $lines[] = 'END:VEVENT';

        return implode("\n", $lines);
    }

    private function icalDate(string $value): string
    {
        try {
            // Local time, as produced by <input type="datetime-local">.
            return (new \DateTimeImmutable($value))->format('Ymd\THis');
        } catch (\Exception) {
            throw new InvalidPayloadException('error.invalid_date');
        }
    }

    private function bitcoin(FieldValues $input): string
    {
        $address = trim($input->get('address'));
        if (!preg_match('/^(?:[13][a-km-zA-HJ-NP-Z1-9]{25,34}|bc1[a-z0-9]{25,87})$/', $address)) {
            throw new InvalidPayloadException('error.invalid_bitcoin_address');
        }

        $query = [];
        $amount = str_replace(',', '.', trim($input->get('amount')));
        if ('' !== $amount) {
            if (!is_numeric($amount) || (float) $amount <= 0) {
                throw new InvalidPayloadException('error.invalid_amount');
            }
            $query['amount'] = rtrim(rtrim(number_format((float) $amount, 8, '.', ''), '0'), '.');
        }
        if ('' !== $input->get('label')) {
            $query['label'] = $input->get('label');
        }

        return 'bitcoin:'.$address.($query ? '?'.http_build_query($query, '', '&', \PHP_QUERY_RFC3986) : '');
    }

    private function url(string $url): string
    {
        $url = trim($url);
        if ('' === $url || 'https://' === $url) {
            throw new InvalidPayloadException('error.field_required', ['%field%' => 'field.url']);
        }
        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }
        if (!filter_var($url, \FILTER_VALIDATE_URL)) {
            throw new InvalidPayloadException('error.invalid_url');
        }

        return $url;
    }

    private function wifi(FieldValues $input): string
    {
        $encryption = \in_array($input->get('encryption'), ['WPA', 'WEP', 'nopass'], true) ? $input->get('encryption') : 'WPA';
        $parts = [
            'T:'.$encryption,
            'S:'.$this->escapeWifi($input->get('ssid')),
        ];
        if ('nopass' !== $encryption) {
            $parts[] = 'P:'.$this->escapeWifi($input->get('password'));
        }
        if ($input->bool('hidden')) {
            $parts[] = 'H:true';
        }

        return 'WIFI:'.implode(';', $parts).';;';
    }

    private function escapeWifi(string $value): string
    {
        return preg_replace('/([\\\;,:"])/', '\\\\$1', $value) ?? $value;
    }

    private function vcard(FieldValues $input): string
    {
        $last = $input->get('last_name');
        $first = $input->get('first_name');
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'N:'.$this->escapeVcard($last).';'.$this->escapeVcard($first).';;;',
            'FN:'.$this->escapeVcard(trim($first.' '.$last)),
        ];
        if ('' !== $input->get('organization')) {
            $lines[] = 'ORG:'.$this->escapeVcard($input->get('organization'));
        }
        if ('' !== $input->get('job_title')) {
            $lines[] = 'TITLE:'.$this->escapeVcard($input->get('job_title'));
        }
        if ('' !== $input->get('phone')) {
            $lines[] = 'TEL;TYPE=CELL:'.$this->phone($input->get('phone'));
        }
        if ('' !== $input->get('email')) {
            $lines[] = 'EMAIL;TYPE=INTERNET:'.$this->email_($input->get('email'));
        }
        if ('' !== $input->get('website')) {
            $lines[] = 'URL:'.$this->url($input->get('website'));
        }
        $lines[] = 'END:VCARD';

        return implode("\n", $lines);
    }

    private function escapeVcard(string $value): string
    {
        return str_replace(["\\", ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], $value);
    }

    private function email(FieldValues $input): string
    {
        $query = array_filter([
            'subject' => $input->get('subject'),
            'body' => $input->get('body'),
        ], static fn (string $v) => '' !== $v);

        return 'mailto:'.$this->email_($input->get('email')).($query ? '?'.http_build_query($query, '', '&', \PHP_QUERY_RFC3986) : '');
    }

    private function email_(string $email): string
    {
        $email = trim($email);
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new InvalidPayloadException('error.invalid_email');
        }

        return $email;
    }

    /**
     * Keeps a national number as typed and normalises anything written in an
     * international form (leading "+" or "00") to the canonical "+" notation.
     */
    private function phone(string $phone): string
    {
        $raw = trim($phone);
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        if (\strlen($digits) < 5) {
            throw new InvalidPayloadException('error.invalid_phone');
        }

        if (str_starts_with($raw, '+')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        return $digits;
    }

    private function whatsapp(FieldValues $input): string
    {
        // wa.me links only work with a full international number.
        $phone = $this->phone($input->get('phone'));
        if (!str_starts_with($phone, '+')) {
            throw new InvalidPayloadException('error.invalid_phone');
        }
        $message = $input->get('message');

        return 'https://wa.me/'.substr($phone, 1).('' !== $message ? '?text='.rawurlencode($message) : '');
    }

    private function geo(FieldValues $input): string
    {
        $lat = (float) str_replace(',', '.', $input->get('latitude'));
        $lng = (float) str_replace(',', '.', $input->get('longitude'));
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            throw new InvalidPayloadException('error.invalid_coordinates');
        }

        return \sprintf('geo:%s,%s', rtrim(rtrim(number_format($lat, 6, '.', ''), '0'), '.'), rtrim(rtrim(number_format($lng, 6, '.', ''), '0'), '.'));
    }

    private function barcode(FieldValues $input): string
    {
        $value = trim($input->get('value'));
        $symbology = strtoupper($input->get('symbology'));

        // Fixed-length numeric symbologies: reject anything the library would
        // silently zero-pad, because a padded EAN is a *different* product.
        $lengths = ['EAN13' => [12, 13], 'EAN8' => [7, 8], 'UPCA' => [11, 12], 'ITF14' => [13, 14]];
        if (isset($lengths[$symbology])) {
            if (!ctype_digit($value) || !\in_array(\strlen($value), $lengths[$symbology], true)) {
                throw new InvalidPayloadException('error.invalid_barcode_value', ['%symbology%' => $symbology]);
            }
        }

        return $value;
    }
}
