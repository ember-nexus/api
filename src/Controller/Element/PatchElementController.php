<?php

namespace App\Controller\Element;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\UpdateElementFromRawDataService;
use App\Type\AccessType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PatchElementController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private UpdateElementFromRawDataService $updateElementFromRawDataService
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'patch-element',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PATCH']
    )]
    public function patchElement(string $uuid, Request $request): Response
    {
        $elementId = UuidV4::fromString($uuid);
        $userId = $this->authProvider->getUserUuid();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $element = $this->elementManager->getElement($elementId);
        if (null === $element) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        /**
         * @var array<string, mixed> $rawData
         */
        $rawData = \Safe\json_decode($request->getContent(), true);

        $element = $this->updateElementFromRawDataService->updateElementFromRawData($element, $rawData);

        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
