<?php

namespace App\Service;

use App\Response\ElementResponse;
use Ramsey\Uuid\UuidInterface;

class ElementResponseService
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService
    ) {
    }

    /**
     * @param UuidInterface[] $nodeUuids
     * @param UuidInterface[] $relationUuids
     */
    public function buildElementResponseFromUuid(
        UuidInterface $uuid
    ): ElementResponse {
        $element = $this->elementManager->getElement($uuid);
        if (null === $element) {
            throw new \LogicException(sprintf("Unable to find element with the id '%s'.", $uuid->toString()));
        }
        $rawData = $this->elementToRawService->elementToRaw($element);

        return new ElementResponse(
            $rawData
        );
    }
}
