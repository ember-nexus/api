<?php

namespace App\Type;

use App\contract\NodeInterface;
use App\Trait\IdentifierTrait;
use App\Trait\PropertiesTrait;
use Syndesi\CypherDataStructures\Contract\NodeInterface as CypherNodeInterface;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;
use Syndesi\MongoDataStructures\Type\Document;

class User implements NodeInterface
{
    use PropertiesTrait;
    use IdentifierTrait;

    private array $otherLabels = [];

    public function __construct()
    {
    }

    public function toCypherNode(): CypherNodeInterface
    {
        return (new Node())
            ->addLabels($this->otherLabels)
            ->addLabel('User')
            ->addProperties($this->properties)
            ->addProperty('id', $this->getIdentifier()->toString())
            ->addIdentifier('id');
    }

    public function toMongoDocument(): DocumentInterface
    {
        return (new Document())
            ->setCollection('User')
            ->addProperties($this->getProperties())
            ->setIdentifier($this->getIdentifier()->toString());
    }

    public function getOtherLabels(): array
    {
        return $this->otherLabels;
    }

    public function setOtherLabels(array $otherLabels): self
    {
        $this->otherLabels = $otherLabels;

        return $this;
    }

    public static function load(CypherNodeInterface $node, DocumentInterface $document): NodeInterface
    {
        return (new self())
            ->setOtherLabels($node->getLabels())
            ->addProperties($document->getProperties())
            ->addProperties($node->getProperties())
            ->setIdentifier($node->getProperty('id'));
    }
}
