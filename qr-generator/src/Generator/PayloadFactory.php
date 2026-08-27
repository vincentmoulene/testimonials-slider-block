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
            'crypto' => $this->crypto($input),
            'sepa' => $this->sepa($input),
            'totp' => $this->totp($input),
            'telegram' => $this->telegram($input->get('username')),
            'signal' => $this->signal($input->get('phone')),
            'directions' => $this->directions($input),
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
        $amount = $this->decimalAmount($input->get('amount'));
        if (null !== $amount) {
            $query['amount'] = $amount;
        }
        if ('' !== $input->get('label')) {
            $query['label'] = $input->get('label');
        }

        return 'bitcoin:'.$address.($query ? '?'.http_build_query($query, '', '&', \PHP_QUERY_RFC3986) : '');
    }

    /**
     * Chains that follow the BIP-21 "scheme:address?amount=" convention, plus
     * Ethereum (EIP-681) and Solana Pay, which differ enough to be special-cased.
     *
     * @var array<string, array{scheme: string, pattern: string, decimals: int}>
     */
    private const CHAINS = [
        'BTC' => ['scheme' => 'bitcoin', 'pattern' => '/^(?:[13][a-km-zA-HJ-NP-Z1-9]{25,34}|bc1[a-z0-9]{25,87})$/', 'decimals' => 8],
        'ETH' => ['scheme' => 'ethereum', 'pattern' => '/^0x[0-9a-fA-F]{40}$/', 'decimals' => 18],
        'LTC' => ['scheme' => 'litecoin', 'pattern' => '/^(?:[LM3][a-km-zA-HJ-NP-Z1-9]{26,33}|ltc1[a-z0-9]{25,87})$/', 'decimals' => 8],
        'DOGE' => ['scheme' => 'dogecoin', 'pattern' => '/^[DA9][a-km-zA-HJ-NP-Z1-9]{25,34}$/', 'decimals' => 8],
        'BCH' => ['scheme' => 'bitcoincash', 'pattern' => '/^(?:(?:bitcoincash:)?[qp][a-z0-9]{41}|[13][a-km-zA-HJ-NP-Z1-9]{25,34})$/', 'decimals' => 8],
        'XMR' => ['scheme' => 'monero', 'pattern' => '/^[48][0-9A-Za-z]{94,105}$/', 'decimals' => 12],
        'DASH' => ['scheme' => 'dash', 'pattern' => '/^X[1-9A-HJ-NP-Za-km-z]{33}$/', 'decimals' => 8],
        'SOL' => ['scheme' => 'solana', 'pattern' => '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', 'decimals' => 9],
    ];

    private function crypto(FieldValues $input): string
    {
        $chain = strtoupper($input->get('chain'));
        $config = self::CHAINS[$chain] ?? throw new InvalidPayloadException('error.unknown_chain');

        $address = trim($input->get('address'));
        if (!preg_match($config['pattern'], $address)) {
            throw new InvalidPayloadException('error.invalid_crypto_address', ['%chain%' => $chain]);
        }

        $amount = $this->decimalAmount($input->get('amount'));
        $label = $input->get('label');

        // Ethereum carries the value in wei, as an integer, in a "value" key.
        if ('ETH' === $chain) {
            $uri = 'ethereum:'.$address.'@1';

            return null !== $amount ? $uri.'?value='.$this->toBaseUnits($amount, $config['decimals']) : $uri;
        }

        // Monero names its parameters differently from every other BIP-21 chain.
        $amountKey = 'XMR' === $chain ? 'tx_amount' : 'amount';
        $labelKey = 'XMR' === $chain ? 'tx_description' : 'label';

        $query = [];
        if (null !== $amount) {
            $query[$amountKey] = $amount;
        }
        if ('' !== $label) {
            $query[$labelKey] = $label;
        }

        return $config['scheme'].':'.$address.($query ? '?'.http_build_query($query, '', '&', \PHP_QUERY_RFC3986) : '');
    }

    /**
     * EPC QR code (EPC069-12), also known as GiroCode: a SEPA credit transfer
     * pre-filled in the payer's banking app. Strictly positional, UTF-8, and
     * capped at 331 bytes by the specification.
     */
    private function sepa(FieldValues $input): string
    {
        $name = $this->collapseWhitespace($input->get('beneficiary_name'));
        $iban = $this->iban($input->get('iban'));

        $amount = $this->decimalAmount($input->get('amount_eur'));
        if (null !== $amount && ((float) $amount < 0.01 || (float) $amount > 999999999.99)) {
            throw new InvalidPayloadException('error.invalid_amount');
        }

        $lines = [
            'BCD',                                                        // service tag
            '002',                                                        // version (002 makes the BIC optional)
            '1',                                                          // character set: UTF-8
            'SCT',                                                        // SEPA credit transfer
            '',                                                           // BIC, not required inside SEPA
            mb_substr($name, 0, 70),
            $iban,
            null !== $amount ? 'EUR'.number_format((float) $amount, 2, '.', '') : '',
            '',                                                           // purpose code
            '',                                                           // structured remittance reference
            mb_substr($this->collapseWhitespace($input->get('remittance')), 0, 140),
        ];

        // Trailing empty fields may be omitted, and shorter payloads scan better.
        while ([] !== $lines && '' === end($lines)) {
            array_pop($lines);
        }

        $payload = implode("\n", $lines);
        if (\strlen($payload) > 331) {
            throw new InvalidPayloadException('error.sepa_too_long');
        }

        return $payload;
    }

    private function iban(string $iban): string
    {
        $iban = strtoupper((string) preg_replace('/[\s-]+/', '', $iban));
        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
            throw new InvalidPayloadException('error.invalid_iban');
        }

        // ISO 7064 MOD 97-10, computed in 7-digit chunks to stay inside int range.
        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $numeric = '';
        foreach (str_split($rearranged) as $character) {
            $numeric .= ctype_alpha($character) ? (string) (\ord($character) - 55) : $character;
        }

        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) (($remainder.$chunk) % 97);
        }

        if (1 !== $remainder) {
            throw new InvalidPayloadException('error.invalid_iban');
        }

        return $iban;
    }

    /**
     * otpauth:// URI, the format every authenticator app reads (Google
     * Authenticator, Authy, 1Password, Bitwarden…).
     */
    private function totp(FieldValues $input): string
    {
        $issuer = $this->collapseWhitespace($input->get('issuer'));
        $account = $this->collapseWhitespace($input->get('account'));

        $secret = strtoupper((string) preg_replace('/[\s-]+/', '', $input->get('secret')));
        $secret = rtrim($secret, '=');
        if (!preg_match('/^[A-Z2-7]{16,}$/', $secret)) {
            throw new InvalidPayloadException('error.invalid_secret');
        }

        $label = rawurlencode($issuer).':'.rawurlencode($account);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => '6',
            'period' => '30',
        ], '', '&', \PHP_QUERY_RFC3986);

        return 'otpauth://totp/'.$label.'?'.$query;
    }

    private function telegram(string $username): string
    {
        $username = ltrim(trim($username), '@');

        // A phone number works too: t.me/+33612345678 opens the same chat.
        if (preg_match('/^\+?\d[\d\s.-]{5,}$/', $username)) {
            return 'https://t.me/'.$this->phone($username);
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{3,31}$/', $username)) {
            throw new InvalidPayloadException('error.invalid_username');
        }

        return 'https://t.me/'.$username;
    }

    private function signal(string $phone): string
    {
        $phone = $this->phone($phone);
        if (!str_starts_with($phone, '+')) {
            throw new InvalidPayloadException('error.invalid_phone');
        }

        return 'https://signal.me/#p/'.$phone;
    }

    private function directions(FieldValues $input): string
    {
        $destination = $this->collapseWhitespace($input->get('destination'));
        if ('' === $destination) {
            throw new InvalidPayloadException('error.field_required', ['%field%' => 'field.destination']);
        }

        $mode = $input->get('travel_mode');
        $query = ['api' => '1', 'destination' => $destination];
        if (\in_array($mode, ['driving', 'walking', 'bicycling', 'transit'], true)) {
            $query['travelmode'] = $mode;
        }

        return 'https://www.google.com/maps/dir/?'.http_build_query($query, '', '&', \PHP_QUERY_RFC3986);
    }

    /** Normalises a user-typed amount, or null when the field was left empty. */
    private function decimalAmount(string $value): ?string
    {
        $value = str_replace([',', ' '], ['.', ''], trim($value));
        if ('' === $value) {
            return null;
        }
        if (!preg_match('/^\d*\.?\d+$/', $value) || 0.0 >= (float) $value) {
            throw new InvalidPayloadException('error.invalid_amount');
        }

        return $value;
    }

    /** Decimal string to integer base units (1.5 ETH with 18 decimals → 1500000000000000000). */
    private function toBaseUnits(string $amount, int $decimals): string
    {
        [$integer, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $fraction = substr(str_pad($fraction, $decimals, '0'), 0, $decimals);
        $result = ltrim($integer.$fraction, '0');

        return '' === $result ? '0' : $result;
    }

    private function collapseWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
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
