<?php

declare(strict_types=1);

namespace App\Controller\Element;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Response\CreatedResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CreateElementFromRawDataService;
use App\Service\ElementManager;
use App\Type\AccessType;
use App\Type\ElementType;
use App\Type\RelationElement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PostElementController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private UrlGeneratorInterface $router,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private CreateElementFromRawDataService $createElementFromRawDataService,
    ) {
    }

    #[Route(
        '/{id}',
        name: 'post-element',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['POST']
    )]
    public function postElement(string $id, Request $request): Response
    {
        $parentElementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        $parentType = $this->accessChecker->getElementType($parentElementId);
        if (ElementType::NODE !== $parentType) {
            // relations can not own nodes
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if (!$this->accessChecker->hasAccessToElement($userId, $parentElementId, AccessType::CREATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $body = \Safe\json_decode($request->getContent(), true);

        if (array_key_exists('start', $body)) {
            // owns-relation can only target nodes
            throw $this->client400BadContentExceptionFactory->createFromTemplate('start', 'non-existent', 'existent');
        }
        if (array_key_exists('end', $body)) {
            // owns-relation can only target nodes
            throw $this->client400BadContentExceptionFactory->createFromTemplate('start', 'non-existent', 'existent');
        }

        if (array_key_exists('id', $body)) {
            $elementId = UuidV4::fromString($body['id']);
        } else {
            $elementId = UuidV4::uuid4();
        }

        if (!array_key_exists('type', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'a valid type');
        }
        $type = $body['type'];

        $rawData = [];
        if (array_key_exists('data', $body)) {
            $rawData = $body['data'];
        }

        $element = $this->createElementFromRawDataService->createElementFromRawData(
            $elementId,
            $type,
            rawData: $rawData
        );
        $this->elementManager->create($element);

        $newNodeOwnsRelation = (new RelationElement())
            ->setId(UuidV4::uuid4())
            ->setType('OWNS')
            ->setStart($parentElementId)
            ->setEnd($element->getId());
        $this->elementManager->create($newNodeOwnsRelation);

        $newNodeCreatedRelation = (new RelationElement())
            ->setId(UuidV4::uuid4())
            ->setType('CREATED')
            ->setStart($userId)
            ->setEnd($element->getId());
        $this->elementManager->create($newNodeCreatedRelation);

        $this->elementManager->flush();

        return new CreatedResponse(
            $this->router->generate(
                'get-element',
                [
                    'id' => $element->getId(),
                ]
            )
        );
    }
}
