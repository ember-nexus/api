<?php

namespace App\Type;

use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;
use Syndesi\MongoDataStructures\Type\Document;

class FragmentGroup {

    private NodeInterface|RelationInterface $cypherFragment;
    private DocumentInterface $documentFragment;

    public function __construct(){}

    /**
     * @return NodeInterface|RelationInterface
     */
    public function getCypherFragment(): RelationInterface|NodeInterface
    {
        return $this->cypherFragment;
    }

    /**
     * @param NodeInterface|RelationInterface $cypherFragment
     * @return FragmentGroup
     */
    public function setCypherFragment(RelationInterface|NodeInterface $cypherFragment): FragmentGroup
    {
        $this->cypherFragment = $cypherFragment;
        return $this;
    }

    /**
     * @return DocumentInterface
     */
    public function getDocumentFragment(): DocumentInterface
    {
        return $this->documentFragment;
    }

    /**
     * @param DocumentInterface $documentFragment
     * @return FragmentGroup
     */
    public function setDocumentFragment(DocumentInterface $documentFragment): FragmentGroup
    {
        $this->documentFragment = $documentFragment;
        return $this;
    }

}