<?php

namespace App\Controller\Element;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementResponseService;
use App\Service\EtagService;
use App\Type\AccessType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetElementController extends AbstractController
{
    public function __construct(
        private ElementResponseService $elementResponseService,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private EtagService $etagService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'get-element',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getElement(string $uuid): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        if (!$this->accessChecker->hasAccessToElement($userUuid, $elementUuid, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $response = $this->elementResponseService->buildElementResponseFromUuid($elementUuid);

        $etag = $this->etagService->getElementEtag($elementUuid);
        if ($etag) {
            $response->headers->set('Etag', $etag);
        }

        return $response;
    }
}
