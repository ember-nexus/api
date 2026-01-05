<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicExceptionFactory;
use Ramsey\Uuid\UuidInterface;

class ElementService
{
    public function __construct(
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function getElementId(NodeElementInterface|RelationElementInterface $element): UuidInterface
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Expected element.id to not be null.');
        }

        return $elementId;
    }
}
