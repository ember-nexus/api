<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Type\Response\ElementResponse;
use App\Type\Uuid;

class ElementResponseService
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    public function buildElementResponseFromId(
        Uuid $id,
    ): ElementResponse {
        $element = $this->elementManager->getElement($id);
        if (null === $element) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Unable to find element with the id '%s'.", $id->toString()));
        }
        $rawData = $this->elementToRawService->elementToRaw($element);

        return new ElementResponse(
            $rawData
        );
    }
}
