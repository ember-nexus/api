<?php

namespace App\Exception;

use App\Contract\ExceptionInterface;

class LogicException extends \LogicException implements ExceptionInterface
{
}
