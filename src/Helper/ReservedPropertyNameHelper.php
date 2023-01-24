<?php

namespace App\Helper;

class ReservedPropertyNameHelper
{
    public const RESERVED_PROPERTY_NAMES = [
        'id',
        '_id',
        'type',
        'start',
        'end',
    ];

    public static function removeReservedPropertyNamesFromArray(array $array): array
    {
        foreach (self::RESERVED_PROPERTY_NAMES as $name) {
            unset($array[$name]);
        }

        return $array;
    }
}
