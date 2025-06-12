<?php

namespace App\Twig;

use NumberFormatter;
use Twig\Extension\RuntimeExtensionInterface;

class AppFilter implements RuntimeExtensionInterface
{
    public function jsonDecode(string $json, bool $assoc = true)
    {
        return json_decode($json, $assoc);
    }

    public function toInt($value): int
    {
        return (int) $value;
    }

    public function toFloat($value): float
    {
        return (float) $value;
    }

    public function toString($value): string
    {
        return (string) $value;
    }

    public function toBool($value): bool
    {
        return (bool) $value;
    }

    public function toArray($value): array
    {
        return (array) $value;
    }

    public function toObject($value)
    {
        return (object) $value;
    }

    public function numberFormat($value, $decimal = 1, $decimalPoint = ',', $thousandSeparator = '.')
    {
        $number = number_format($value, $decimal, $decimalPoint, $thousandSeparator);

        return rtrim(rtrim($number, 0),',');
    }

    public function arrayUnset($array, $key)
    {
        if (isset($array[$key])) {
            unset($array[$key]);
        }

        return $array;
    }

    public function base64Encode(string $id, string $prefix = 'bm-order')
    {
        return base64_encode(sprintf('%s:%s', $prefix, $id));
    }

    public function base64Decode(string $string)
    {
        return explode(':', base64_decode($string));
    }

    public function normalizePhoneNumber(string $phoneNumber = ''): string
    {
        if (strpos($phoneNumber, '+') === 0) {
            $phoneNumber = ltrim($phoneNumber, '+');
        }

        if (strpos($phoneNumber, '62') === 0) {
            $phoneNumber = '0'.ltrim($phoneNumber, '62');
        }

        if (isset($phoneNumber[0]) && $phoneNumber[0] !== '0') {
            $phoneNumber = '0'.$phoneNumber;
        }

        return $phoneNumber;
    }

    public  function formatNumberToWords($number = 0, string $locale = 'id')
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::SPELLOUT);

        return str_replace('titik', 'koma', $formatter->format($number));
    }
}
