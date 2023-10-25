<?php

namespace App\Controller\Element;

use App\Contract\NodeElementInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Response\CreatedResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CreateElementFromRawDataService;
use App\Service\ElementManager;
use App\Type\AccessType;
use App\Type\RelationElement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class PostIndexController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private UrlGeneratorInterface $router,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private CreateElementFromRawDataService $createElementFromRawDataService
    ) {
    }

    #[Route(
        '/',
        name: 'post-index',
        methods: ['POST']
    )]
    public function postIndex(Request $request): Response
    {
        $userId = $this->authProvider->getUserUuid();

        $body = \Safe\json_decode($request->getContent(), true);

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

        $startId = null;
        if (array_key_exists('start', $body)) {
            $startId = UuidV4::fromString($body['start']);
            $startElement = $this->elementManager->getElement($startId);
            if (null === $startElement) {
                throw $this->client404NotFoundExceptionFactory->createFromTemplate();
            }
            if (!$this->accessChecker->hasAccessToElement($userId, $startId, AccessType::CREATE)) {
                throw $this->client404NotFoundExceptionFactory->createFromTemplate();
            }
            if (!($startElement instanceof NodeElementInterface)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('start', 'id of a node', 'id of a relation');
            }
        }

        $endId = null;
        if (array_key_exists('end', $body)) {
            $endId = UuidV4::fromString($body['end']);
            $endElement = $this->elementManager->getElement($endId);
            if (null === $endElement) {
                throw $this->client404NotFoundExceptionFactory->createFromTemplate();
            }
            if (!$this->accessChecker->hasAccessToElement($userId, $endId, AccessType::READ)) {
                throw $this->client404NotFoundExceptionFactory->createFromTemplate();
            }
            if (!($endElement instanceof NodeElementInterface)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('end', 'id of a node', 'id of a relation');
            }
        }

        $element = $this->createElementFromRawDataService->createElementFromRawData(
            $elementId,
            $type,
            $startId,
            $endId,
            $rawData
        );
        $this->elementManager->create($element);

        if ($element instanceof NodeElementInterface) {
            $newNodeOwnsRelation = (new RelationElement())
                ->setIdentifier(UuidV4::uuid4())
                ->setType('OWNS')
                ->setStart($userId)
                ->setEnd($element->getIdentifier());
            $this->elementManager->create($newNodeOwnsRelation);

            $newNodeCreatedRelation = (new RelationElement())
                ->setIdentifier(UuidV4::uuid4())
                ->setType('CREATED')
                ->setStart($userId)
                ->setEnd($element->getIdentifier());
            $this->elementManager->create($newNodeCreatedRelation);
        }

        $this->elementManager->flush();

        return new CreatedResponse(
            $this->router->generate(
                'get-element',
                [
                    'uuid' => $element->getIdentifier(),
                ]
            )
        );
    }
}
