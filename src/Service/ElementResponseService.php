<?php

namespace App\Service;

use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\ElementResponse;
use Ramsey\Uuid\UuidInterface;

class ElementResponseService
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
    }

    public function buildElementResponseFromUuid(
        UuidInterface $uuid
    ): ElementResponse {
        $element = $this->elementManager->getElement($uuid);
        if (null === $element) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf("Unable to find element with the id '%s'.", $uuid->toString()));
        }
        $rawData = $this->elementToRawService->elementToRaw($element);

        return new ElementResponse(
            $rawData
        );
    }
}
