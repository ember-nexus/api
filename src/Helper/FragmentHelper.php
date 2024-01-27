<?php

namespace App\Helper;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Type\FragmentGroup;

class FragmentHelper
{
    public static function getFragmentGroupFromFragmentizeEvent(NodeElementFragmentizeEvent|RelationElementFragmentizeEvent $event): FragmentGroup
    {
        return new FragmentGroup(
            $event->getCypherFragment(),
            $event->getMongoFragment(),
            $event->getElasticFragment(),
            $event->getFileFragment()
        );
    }
}
