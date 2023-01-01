<?php

namespace App\Helper;

use Syndesi\CypherDataStructures\Type\Node;

class Neo4jClientHelper
{
    public static function getNodeFromLaudisNode(\Laudis\Neo4j\Types\Node $node): Node
    {
        $newNode = (new Node())
            ->addLabels($node->getLabels())
            ->addProperties($node->getProperties());
        if ($newNode->hasProperty('id')) {
            $newNode->addIdentifier('id');
        }

        return $newNode;
    }
}
