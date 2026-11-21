<?php

declare(strict_types=1);

namespace App\Controller\Element;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\RequestUtilService;
use App\Service\ResetElementPropertiesService;
use App\Service\UpdateElementFromRawDataService;
use App\Type\AccessType;
use App\Type\EtagType;
use App\Type\Response\NoContentResponse;
use App\Type\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PutElementController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private UpdateElementFromRawDataService $updateElementFromRawDataService,
        private ResetElementPropertiesService $resetElementPropertiesService,
        private RequestUtilService $requestUtilService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    #[Route(
        '/{id}',
        name: 'put-element',
        requirements: [
            'id' => Regex::UUID_CONTROLLER,
        ],
        methods: ['PUT']
    )]
    #[EndpointSupportsEtag(EtagType::ELEMENT)]
    public function putElement(string $id, Request $request): NoContentResponse
    {
        $elementId = Uuid::fromString($id);
        $userId = $this->authProvider->getUserId();

        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $element = $this->elementManager->getElementOrFail($elementId);

        $rawData = $this->requestUtilService->getJsonBodyFromRequest($request);

        $element = $this->resetElementPropertiesService->resetElementProperties($element);
        $element = $this->updateElementFromRawDataService->updateElementFromRawData($element, $rawData);

        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
