<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEventFactory;
use App\Factory\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEventFactory;
use App\Helper\FragmentHelper;
use App\Type\FragmentGroup;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElementFragmentizeService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private NodeElementFragmentizeEventFactory $nodeElementFragmentizeEventFactory,
        private RelationElementFragmentizeEventFactory $relationElementFragmentizeEventFactory,
    ) {
    }

    public function fragmentize(NodeElementInterface|RelationElementInterface $element): FragmentGroup
    {
        if ($element instanceof NodeElementInterface) {
            $event = $this->nodeElementFragmentizeEventFactory->createNodeElementFragmentizeEvent($element);
        } else {
            $event = $this->relationElementFragmentizeEventFactory->createRelationElementFragmentizeEvent($element);
        }
        $this->eventDispatcher->dispatch($event);

        return FragmentHelper::getFragmentGroupFromFragmentizeEvent($event);
    }
}
