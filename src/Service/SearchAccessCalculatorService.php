<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Security\AccessChecker;
use App\Type\AccessType;
use Ramsey\Uuid\UuidInterface;

/**
 * Calculates the direct groups/users with search access to a node or relation, and the Elasticsearch index name its
 * document is stored under. Shared by the code paths that calculate it at create time and the ones that recalculate
 * it later, so both use the exact same access-calculation logic.
 */
class SearchAccessCalculatorService
{
    public function __construct(
        private AccessChecker $accessChecker,
    ) {
    }

    /**
     * @return array{groups: string[], users: string[]}
     */
    public function calculateSearchAccess(NodeElementInterface|RelationElementInterface $element): array
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            return ['groups' => [], 'users' => []];
        }

        if ($element instanceof RelationElementInterface) {
            $groups = $this->accessChecker->getDirectGroupsWithAccessToRelation($elementId, AccessType::SEARCH);
            $users = $this->accessChecker->getDirectUsersWithAccessToRelation($elementId, AccessType::SEARCH);
        } else {
            $groups = $this->accessChecker->getDirectGroupsWithAccessToNode($elementId, AccessType::SEARCH);
            $users = $this->accessChecker->getDirectUsersWithAccessToNode($elementId, AccessType::SEARCH);
        }

        return [
            'groups' => $this->convertArrayOfUuidsToArrayOfStrings($groups),
            'users' => $this->convertArrayOfUuidsToArrayOfStrings($users),
        ];
    }

    public function getIndexForElement(NodeElementInterface|RelationElementInterface $element): string
    {
        if ($element instanceof RelationElementInterface) {
            return sprintf('relation_%s', strtolower($element->getType() ?? ''));
        }

        return sprintf('node_%s', strtolower($element->getLabel() ?? ''));
    }

    /**
     * @param UuidInterface[] $ids
     *
     * @return string[]
     */
    private function convertArrayOfUuidsToArrayOfStrings(array $ids): array
    {
        $output = [];
        foreach ($ids as $id) {
            $output[] = $id->toString();
        }

        return $output;
    }
}
