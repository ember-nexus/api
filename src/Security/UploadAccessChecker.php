<?php

declare(strict_types=1);

namespace App\Security;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Type\AccessType;
use Ramsey\Uuid\UuidInterface;

class UploadAccessChecker
{

    public function __construct(
        private AccessChecker $accessChecker,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    public function verifyUserCanUploadFileToElement(UuidInterface $userId, UuidInterface $elementId): void
    {
        // creating files only requires update privileges to the element itself
        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
    }

}
