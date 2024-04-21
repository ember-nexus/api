<?php

declare(strict_types=1);

namespace App\Helper;

class ReservedPropertyNameHelper
{
    /**
     * @var string[] RESERVED_PROPERTY_NAMES
     */
    public const array RESERVED_PROPERTY_NAMES = [
        'id',
        '_id',
        'type',
        'start',
        'end',
    ];

    /**
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    public static function removeReservedPropertyNamesFromArray(array $array): array
    {
        foreach (self::RESERVED_PROPERTY_NAMES as $name) {
            unset($array[$name]);
        }

        return $array;
    }
}
