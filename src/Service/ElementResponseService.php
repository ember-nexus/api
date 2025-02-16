<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\ElementResponse;
use Ramsey\Uuid\UuidInterface;

class ElementResponseService
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function buildElementResponseFromId(
        UuidInterface $id,
    ): ElementResponse {
        $element = $this->elementManager->getElement($id);
        if (null === $element) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf("Unable to find element with the id '%s'.", $id->toString()));
        }
        $rawData = $this->elementToRawService->elementToRaw($element);

        return new ElementResponse(
            $rawData
        );
    }
}
