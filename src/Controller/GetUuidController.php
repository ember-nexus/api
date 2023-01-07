<?php

namespace App\Controller;

use App\Helper\Regex;
use App\Service\ElementManager;
use App\Service\ElementToRawService;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetUuidController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'getUuid',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getUuid(string $uuid): Response
    {
        $element = $this->elementManager->getElement(UuidV4::fromString($uuid));
        $rawElement = $this->elementToRawService->elementToRaw($element);

        return new JsonResponse($rawElement);
    }
}
