<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\AccessType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
class PostElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private CypherEntityManager $cypherEntityManager,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory)
    {
    }

    #[Route(
        '/{id}/file',
        name: 'post-element-file',
        requirements: [
            'id' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['POST']
    )]
    public function postElementFile(string $id, Request $request): Response
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        // note: update is used because files are optional parts of already existing elements
        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $request->getContent(true);

        return new NoContentResponse();
    }
}
