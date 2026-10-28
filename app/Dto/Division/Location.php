<?php

declare(strict_types=1);

namespace App\Dto\Division;

use App\Livewire\Division\Forms\DivisionForm;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(source: DivisionForm::class)]
class Location
{
    protected const int DECIMAL_PLACES = 14;

    #[Map(source: 'division[location?][latitude?]', transform: [self::class, 'normalizeCoordinate'])]
    public mixed $latitude = null;

    #[Map(source: 'division[location?][longitude?]', transform: [self::class, 'normalizeCoordinate'])]
    public mixed $longitude = null;

    public static function normalizeCoordinate(mixed $value): mixed
    {
        $normalized = \is_string($value) ? str_replace(',', '.', $value) : $value;

        if (\is_float($normalized) && is_finite($normalized)) {
            $normalized = json_encode($normalized, JSON_THROW_ON_ERROR);
        }

        if (!\is_string($normalized) || !\is_numeric($normalized)) {
            return $value;
        }

        // Truncate text before any float conversion; account for scientific notation.
        preg_match('/^([+-]?)(\d*)(?:\.(\d*))?(?:[eE]([+-]?\d+))?$/D', trim($normalized), $parts);

        $digits = $parts[2] . ($parts[3] ?? '');
        $decimalPosition = strlen($parts[2]) + (float) ($parts[4] ?? 0);

        if ($decimalPosition <= -self::DECIMAL_PLACES) {
            return $parts[1] . '0';
        }

        if ($decimalPosition >= strlen($digits)) {
            return $normalized;
        }

        $decimalPosition = (int) $decimalPosition;

        if ($decimalPosition < 0) {
            $integer = '0';
            $fraction = str_repeat('0', -$decimalPosition) . $digits;
        } else {
            $integer = substr($digits, 0, $decimalPosition);
            $fraction = substr($digits, $decimalPosition);
        }

        return $parts[1] . ($integer === '' ? '0' : $integer) . '.' . substr($fraction, 0, self::DECIMAL_PLACES);
    }

    /** @return array{latitude: mixed, longitude: mixed} */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
