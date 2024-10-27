<?php

declare(strict_types=1);

namespace App\Controller\Search;

use App\Contract\SearchStepInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Response\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class PostSearch3Controller extends AbstractController
{
    public function __construct(
        #[TaggedIterator('app.searchStep')] private iterable $searchStepHandlers,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory
    ) {
    }

    #[Route(
        '/search3',
        name: 'post-search3',
        methods: ['POST']
    )]
    public function postSearch3(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);

        $debug = false;
        if (array_key_exists('debug', $body)) {
            $debug = $body['debug'];
            if (!is_bool($debug)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('debug', 'bool', $debug);
            }
        }

        $globalParameters = [];
        if (array_key_exists('parameters', $body)) {
            $globalParameters = $body['parameters'];
            if (!is_array($globalParameters)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('parameters', 'array', $globalParameters);
            }
        }

        if (!array_key_exists('steps', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('steps', 'array');
        }
        $steps = $body['steps'];
        if (!is_array($steps)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('steps', 'array', $steps);
        }

        $searchStepHandlersDictionary = [];
        foreach ($this->searchStepHandlers as $searchStepHandler) {
            /**
             * @var $searchStepHandler SearchStepInterface
             */
            $searchStepHandlersDictionary[$searchStepHandler->getIdentifier()] = $searchStepHandler;
        }

        $lastStepResult = [];
        $debugData = [];
        foreach ($steps as $index => $step) {
            if (!array_key_exists('type', $step)) {
                throw $this->client400MissingPropertyExceptionFactory->createFromTemplate(sprintf('steps[%d].type', $index), 'string');
            }
            $type = $step['type'];
            if (!is_string($type)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('steps[%d].type', $index), 'string', $type);
            }

            $query = null;
            if (array_key_exists('query', $step)) {
                $query = $step['query'];
                if (!(is_string($query) || is_array($query) || is_null($query))) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('steps[%d].query', $index), 'null | string | array', $query);
                }
            }

            $parameters = [];
            if (array_key_exists('parameters', $step)) {
                $parameters = $step['parameters'];
                if (!is_array($parameters)) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('steps[%d].parameters', $index), 'array', $parameters);
                }
            }

            $currentStepParameters = [...$lastStepResult, ...$parameters, ...$globalParameters];

            if (!array_key_exists($type, $searchStepHandlersDictionary)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('steps[%d].type', $index), 'valid type', $type);
            }
            $currentStepResult = $searchStepHandlersDictionary[$type]->executeStep($query, $currentStepParameters);

            $lastStepResult = $currentStepResult->getResults();
            $debugData[] = $currentStepResult->getDebugData();
        }

        $responseObject = [
            'result' => $lastStepResult,
            'debug' => $debugData,
        ];
        if (!$debug) {
            unset($responseObject['debug']);
        }

        return new JsonResponse($responseObject);
    }
}
