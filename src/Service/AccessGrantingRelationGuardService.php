<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\Client403ForbiddenException;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Security\AccessChecker;
use App\Type\AccessType;
use App\Type\Uuid;

/**
 * Relations which grant access (`OWNS`, `IS_IN_GROUP`, `HAS_<ACCESS>_ACCESS`) may only be created by users who
 * themselves hold that access on the target. Without this, read access to an element would be enough to make oneself
 * (or any group) its owner.
 */
class AccessGrantingRelationGuardService
{
    public function __construct(
        private AccessChecker $accessChecker,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
    ) {
    }

    /**
     * @return AccessType[] the access types the creator needs on the end node, empty for ordinary relations
     */
    public function getRequiredAccessTypes(string $relationType): array
    {
        if ('OWNS' === $relationType || 'IS_IN_GROUP' === $relationType) {
            // ownership and group membership pass on everything: only a full owner may hand them out
            return AccessType::cases();
        }
        if (1 === preg_match('/^HAS_([A-Z]+)_ACCESS$/', $relationType, $matches)) {
            $accessType = AccessType::tryFrom($matches[1]);

            return null === $accessType ? [] : [$accessType];
        }

        return [];
    }

    /**
     * @throws Client403ForbiddenException
     */
    public function assertMayCreate(Uuid $userId, string $relationType, Uuid $endNodeId): void
    {
        foreach ($this->getRequiredAccessTypes($relationType) as $accessType) {
            if (!$this->accessChecker->hasAccessToElement($userId, $endNodeId, $accessType)) {
                throw $this->client403ForbiddenExceptionFactory->createFromTemplate(sprintf(
                    'Creating a %s relation requires %s access to the target element.',
                    $relationType,
                    $accessType->value
                ));
            }
        }
    }
}
