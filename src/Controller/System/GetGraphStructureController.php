<?php

declare(strict_types=1);

namespace App\Controller\System;

use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Response\JsonResponse;
use App\Service\GraphStructureService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetGraphStructureController extends AbstractController
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private GraphStructureService $graphStructureService,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
    ) {
    }

    #[Route(
        '/graph-structure',
        name: 'get-graph-structure',
        methods: ['GET']
    )]
    public function getGraphStructure(): Response
    {
        if (!$this->emberNexusConfiguration->isInstanceConfigurationEnabled()) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }
        $nodeTypes = $this->graphStructureService->getNodeTypes();
        sort($nodeTypes);
        $relationTypes = $this->graphStructureService->getRelationTypes();
        sort($relationTypes);

        $graphStructure = [
            'nodeTypes' => $nodeTypes,
            'relationTypes' => $relationTypes,
        ];

        return new JsonResponse($graphStructure);
    }
}
