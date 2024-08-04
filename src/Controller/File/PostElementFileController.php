<?php

declare(strict_types=1);

namespace App\Controller\File;

use App\Attribute\EndpointImplementsTusIo;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Helper\Regex;
use App\Response\NoContentResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\LockService;
use App\Type\AccessType;
use App\Type\Lock\FileUploadCheckLock;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class PostElementFileController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private LockService $lockService,
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
    #[EndpointImplementsTusIo]
    public function postElementFile(string $id, Request $request): Response
    {
        $elementId = UuidV4::fromString($id);
        $userId = $this->authProvider->getUserId();

        // note: update is used because files are optional parts of already existing elements
        if (!$this->accessChecker->hasAccessToElement($userId, $elementId, AccessType::UPDATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $lock = new FileUploadCheckLock($elementId, $userId);
        $this->lockService->acquireLock($lock);

        $cypherClient = $this->cypherEntityManager->getClient();
        $currentlyHappeningUploads = $cypherClient->runStatement(Statement::create(
            "MATCH (upload:FileUpload {uploadForElement: \$elementId})\n".
            "WHERE upload.status <> 'finished'\n".
            "RETURN upload\n".
            "ORDER BY upload.id",
            [
                'elementId' => $elementId->toString()
            ]
        ));

        $canCreateNewUpload = true;
        foreach ($currentlyHappeningUploads as $currentlyHappeningUploadsRow) {
            /**
             * @var $currentlyHappeningUploadsRow CypherMap
             */
            $upload = $currentlyHappeningUploadsRow->get('upload');
            /**
             * @var $upload Node
             */
            $uploadProperties = $upload->getProperties();


            if (!$uploadProperties->hasKey('id')) {
                throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to handle file upload with attribute \'id\' missing.');
            }
            $uploadId = Uuid::fromString($uploadProperties->get('id'));


            if (!$uploadProperties->hasKey('variant')) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf(
                    "Unable to handle upload '%s' with missing attribute 'variant'.",
                    $uploadId->toString()
                ));
            }
            $variant = $uploadProperties->get('variant');

            if ($variant !== 'tus.io') {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf(
                    "Unable to handle upload '%s' of unknown variant '%s'.",
                    $uploadId->toString(),
                    $variant
                ));
            }


        }
//        $nodeIds = [];
//        foreach ($res as $resultSet) {
//            $nodeIds[] = UuidV4::fromString($resultSet->get('element.id'));
//        }


        $this->lockService->releaseLock($lock);

        return new NoContentResponse();
    }
}
