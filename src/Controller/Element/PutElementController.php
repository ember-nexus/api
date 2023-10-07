<?php

namespace App\Controller\Element;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\ResetElementPropertiesService;
use App\Service\UpdateElementFromRawDataService;
use App\Type\AccessType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PutElementController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private UpdateElementFromRawDataService $updateElementFromRawDataService,
        private ResetElementPropertiesService $resetElementPropertiesService
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'put-element',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PUT']
    )]
    public function putElement(string $uuid, Request $request): Response
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

        $element = $this->resetElementPropertiesService->resetElementProperties($element);
        $element = $this->updateElementFromRawDataService->updateElementFromRawData($element, $rawData);

        //        foreach (array_keys($element->getProperties()) as $name) {
        //            if ('id' === $name) {
        //                continue;
        //            }
        //            if ('created' === $name) {
        //                continue;
        //            }
        //            if ('updated' === $name) {
        //                continue;
        //            }
        //            $element->addProperty($name, null);
        //        }
        //
        //        $element->addProperties($data);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
