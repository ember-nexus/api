<?php

namespace App\Controller;

use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Security\PropertyChecker;
use App\Service\ElementManager;
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
        private PropertyChecker $propertyChecker
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'putElement',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['PUT']
    )]
    public function putElement(string $uuid, Request $request): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw new ClientUnauthorizedException();
        }

        if (!$this->accessChecker->hasAccessToElement($userUuid, $elementUuid, AccessType::UPDATE)) {
            throw new ClientNotFoundException();
        }

        $element = $this->elementManager->getElement($elementUuid);
        if (null === $element) {
            throw new ClientNotFoundException();
        }

        /**
         * @var array<string, mixed> $data
         */
        $data = \Safe\json_decode($request->getContent(), true);
        $this->propertyChecker->runCheckUserSuppliedProperties($element, $data);

        foreach ($element->getProperties() as $name => $value) {
            if ('id' === $name) {
                continue;
            }
            if ('created' === $name) {
                continue;
            }
            if ('updated' === $name) {
                continue;
            }
            $element->addProperty($name, null);
        }

        $element->addProperties($data);
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
