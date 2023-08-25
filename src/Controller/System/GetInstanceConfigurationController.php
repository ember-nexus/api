<?php

namespace App\Controller\System;

use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Response\JsonResponse;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetInstanceConfigurationController extends AbstractController
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private ParameterBagInterface $bag,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory
    ) {
    }

    #[Route(
        '/instance-configuration',
        name: 'getInstanceConfiguration',
        methods: ['GET']
    )]
    public function getInstanceConfiguration(): Response
    {
        if (!$this->emberNexusConfiguration->isInstanceConfigurationEnabled()) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }

        $instanceConfiguration = [
            'version' => null,
            'pageSize' => [
                'min' => $this->emberNexusConfiguration->getPageSizeMin(),
                'default' => $this->emberNexusConfiguration->getPageSizeDefault(),
                'max' => $this->emberNexusConfiguration->getPageSizeMax(),
            ],
            'register' => [
                'enabled' => $this->emberNexusConfiguration->isRegisterEnabled(),
                'uniqueIdentifier' => $this->emberNexusConfiguration->getRegisterUniqueIdentifier(),
                'uniqueIdentifierRegex' => $this->emberNexusConfiguration->getRegisterUniqueIdentifierRegex(),
            ],
        ];

        if ($this->emberNexusConfiguration->isInstanceConfigurationShowVersion()) {
            $instanceConfiguration['version'] = $this->bag->get('version');
        }

        return new JsonResponse($instanceConfiguration);
    }
}
