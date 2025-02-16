<?php

declare(strict_types=1);

namespace App\Logger;

use App\Service\RequestIdService;
use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

#[AsMonologProcessor]
class RequestProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestIdService $requestIdService,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['requestId'] = $this->requestIdService->getRequestId()->toString();

        return $record;
    }
}
