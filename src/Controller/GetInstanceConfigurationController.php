<?php

namespace App\Controller;

use App\Exception\ClientForbiddenException;
use App\Response\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GetInstanceConfigurationController extends AbstractController
{
    public function __construct(
        private ParameterBagInterface $bag
    ) {
    }

    #[Route(
        '/instance-configuration',
        name: 'getInstanceConfiguration',
        methods: ['GET']
    )]
    public function getInstanceConfiguration(): Response
    {
        $instanceConfigurationConfig = $this->bag->get('instanceConfiguration');
        if (null === $instanceConfigurationConfig) {
            throw new \Exception("Unable to get unique identifier from config; key 'instanceConfiguration' must exist.");
        }
        if (!is_array($instanceConfigurationConfig)) {
            throw new \Exception("Configuration key 'instanceConfiguration' must be an array.");
        }

        if (true !== $instanceConfigurationConfig['enabled']) {
            throw new ClientForbiddenException();
        }

        $pageSizeConfig = $this->bag->get('pageSize');
        if (null === $pageSizeConfig) {
            throw new \Exception("Unable to get unique identifier from config; key 'pageSize' must exist.");
        }
        if (!is_array($pageSizeConfig)) {
            throw new \Exception("Configuration key 'pageSize' must be an array.");
        }

        $registerConfig = $this->bag->get('register');
        if (null === $registerConfig) {
            throw new \Exception("Unable to get unique identifier from config; key 'register' must exist.");
        }
        if (!is_array($registerConfig)) {
            throw new \Exception("Configuration key 'register' must be an array.");
        }

        $instanceConfiguration = [
            'version' => null,
            'pageSize' => [
                'min' => $pageSizeConfig['min'],
                'default' => $pageSizeConfig['default'],
                'max' => $pageSizeConfig['max'],
            ],
            'register' => [
                'enabled' => $registerConfig['enabled'],
                'uniqueIdentifier' => $registerConfig['uniqueIdentifier'],
                'uniqueIdentifierRegex' => $registerConfig['uniqueIdentifierRegex'],
            ],
        ];

        if (true === $instanceConfigurationConfig['showVersion']) {
            $instanceConfiguration['version'] = $this->bag->get('version');
        }

        return new JsonResponse($instanceConfiguration);
    }
}
