<?php

declare(strict_types=1);

namespace App\Controller\Element;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementResponseService;
use App\Type\AccessType;
use App\Type\EtagType;
use App\Type\Response\ElementResponse;
use App\Type\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class GetElementController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    #[Route(
        '/{id}',
        name: 'get-element',
        requirements: [
            'id' => Regex::UUID_CONTROLLER,
        ],
        methods: ['GET']
    )]
    #[EndpointSupportsEtag(EtagType::ELEMENT)]
    public function getElement(string $id): ElementResponse
    {
        $elementId = Uuid::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        return $this->elementResponseService->buildElementResponseFromId($elementId);
    }
}
