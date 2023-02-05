<?php

namespace App\Controller;

use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Security\PermissionChecker;
use App\Service\ElementManager;
use App\Service\ProblemJsonGeneratorService;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PutUuidController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private AuthProvider $authProvider,
        private ProblemJsonGeneratorService $problemJsonGeneratorService,
        private PermissionChecker $permissionChecker
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'putUuid',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['PUT']
    )]
    public function putUuid(string $uuid, Request $request): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $hasPermission = $this->permissionChecker->checkPermissionToElement(
            $this->authProvider->getUserUuid(),
            $elementUuid,
            'WRITE'
        );
        if (!$hasPermission) {
            return $this->problemJsonGeneratorService->createProblemJsonFor404();
        }

        $data = $request->getContent();
        $data = \Safe\json_decode($data, true);

        $element = $this->elementManager->getElement($elementUuid);
        foreach ($element->getProperties() as $name => $value) {
            $element->addProperty($name, null);
        }
        $element->addProperties($data);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
