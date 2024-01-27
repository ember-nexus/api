<?php

namespace App\Helper;

use DateTimeInterface;
use Exception;
use Laudis\Neo4j\Types\Date;
use Laudis\Neo4j\Types\DateTime;
use Laudis\Neo4j\Types\DateTimeZoneId;
use Laudis\Neo4j\Types\LocalDateTime;

class DateTimeHelper
{
    public static function getDateTimeFromLaudisObject(
        mixed $laudisObject
    ): DateTimeInterface {
        if ($laudisObject instanceof Date) {
            return $laudisObject->toDateTime();
        }
        if ($laudisObject instanceof DateTime) {
            return $laudisObject->toDateTime();
        }
        if ($laudisObject instanceof DateTimeZoneId) {
            return $laudisObject->toDateTime();
        }
        if ($laudisObject instanceof LocalDateTime) {
            return $laudisObject->toDateTime();
        }
        throw new Exception(sprintf('Unable to get DateTime from %s.', get_class($laudisObject)));
    }
}
