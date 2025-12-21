<?php

declare(strict_types=1);

namespace App\Controller\Search;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Exception\ProblemJsonException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Response\JsonResponse;
use App\Type\SearchStepType;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class PostSearchController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory,
    ) {
    }

    private function getIsDebugFromBody(mixed $body): bool
    {
        if (!array_key_exists('debug', $body)) {
            return false;
        }
        $debug = $body['debug'];
        if (!is_bool($debug)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('debug', 'bool', $debug);
        }

        return $debug;
    }

    /**
     * @return array<string, mixed>
     */
    private function getGlobalParametersFromBody(mixed $body): array
    {
        $globalParameters = [];
        if (!array_key_exists('parameters', $body)) {
            return $globalParameters;
        }
        $globalParameters = $body['parameters'];
        if (!is_array($globalParameters)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('parameters', 'array', $globalParameters);
        }

        return $globalParameters;
    }

    /**
     * @return array<int, mixed>
     */
    private function getStepsFromBody(mixed $body): array
    {
        if (!array_key_exists('steps', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('steps', 'array');
        }
        $steps = $body['steps'];
        if (!is_array($steps)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('steps', 'array', $steps);
        }

        // force non-associative array
        return array_values($steps);
    }

    /**
     * @param array<string, mixed> $step
     */
    private function getTypeFromStepDefinition(array $step, int $stepIndex): SearchStepType
    {
        if (!array_key_exists('type', $step)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate(sprintf('steps[%d].type', $stepIndex), 'string');
        }
        $type = $step['type'];
        if (!is_string($type)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('steps[%d].type', $stepIndex), 'string', $type);
        }

        return SearchStepType::from($type);
    }

    /**
     * @param array<string, mixed> $step
     *
     * @return string|array<string, mixed>|null
     */
    private function getQueryFromStepDefinition(array $step, int $stepIndex): string|array|null
    {
        $query = null;
        if (!array_key_exists('query', $step)) {
            return $query;
        }
        $query = $step['query'];
        if (!(is_string($query) || is_array($query) || is_null($query))) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('steps[%d].query', $stepIndex), 'null | string | array', $query);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $step
     *
     * @return array<string, mixed>
     */
    private function getStepParametersFromStepDefinition(array $step, int $stepIndex): array
    {
        $stepParameters = [];
        if (!array_key_exists('parameters', $step)) {
            return $stepParameters;
        }
        $stepParameters = $step['parameters'];
        if (!is_array($stepParameters)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('steps[%d].parameters', $stepIndex), 'array', $stepParameters);
        }

        return $stepParameters;
    }

    #[Route(
        '/search',
        name: 'post-search',
        methods: ['POST']
    )]
    public function postSearch(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);

        $debug = $this->getIsDebugFromBody($body);
        $globalParameters = $this->getGlobalParametersFromBody($body);
        $steps = $this->getStepsFromBody($body);

        $stepResults = [];
        $lastStepResults = [];
        $debugData = [];
        foreach ($steps as $index => $step) {
            $type = $this->getTypeFromStepDefinition($step, $index);
            $query = $this->getQueryFromStepDefinition($step, $index);
            $stepParameters = $this->getStepParametersFromStepDefinition($step, $index);

            $parameters = [
                ...$globalParameters,
                ...$stepParameters,
                'stepResults' => $stepResults,
            ];

            $event = new SearchStepEvent(
                $type,
                $query,
                $parameters
            );
            try {
                $this->eventDispatcher->dispatch($event);
            } catch (Throwable $t) {
                if ($t instanceof ProblemJsonException) {
                    throw $t;
                }
                throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate(sprintf('Exception of type %s got thrown during search step %d (type %s): %s', get_debug_type($t), $index + 1, $type->value, $t->getMessage()), previous: $t);
            }

            $lastStepResults = $event->getResults();
            $stepResults[] = $lastStepResults;
            $debugData[] = $event->getDebugData();
        }

        $responseObject = [
            'type' => '_SearchResultResponse',
            'results' => $lastStepResults,
            'debug' => $debugData,
        ];
        if (!$debug) {
            unset($responseObject['debug']);
        }

        return new JsonResponse($responseObject);
    }
}
