<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\SearchStep\EventListener;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\EventSystem\SearchStep\EventListener\ElementHydrationSearchStepEventListener;
use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\ElementToRawService;
use App\Service\ExpressionService;
use App\Type\NodeElement;
use App\Type\SearchStepType;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[Small]
#[CoversClass(ElementHydrationSearchStepEventListener::class)]
class ElementHydrationSearchStepEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function buildElementHydrationSearchStepEventListener(
        ?ElementManager $elementManager = null,
        ?ElementToRawService $elementToRawService = null,
        ?AccessChecker $accessChecker = null,
        ?AuthProvider $authProvider = null,
        ?ExpressionService $expressionService = null,
        ?Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
    ): ElementHydrationSearchStepEventListener {
        $elementManager = $elementManager ?? self::prophesize(ElementManager::class)->reveal();
        $elementToRawService = $elementToRawService ?? self::prophesize(ElementToRawService::class)->reveal();
        $accessChecker = $accessChecker ?? self::prophesize(AccessChecker::class)->reveal();
        $authProvider = $authProvider ?? self::prophesize(AuthProvider::class)->reveal();
        $expressionService = $expressionService ?? self::prophesize(ExpressionService::class)->reveal();
        $client400MissingPropertyExceptionFactory = $client400MissingPropertyExceptionFactory ?? self::prophesize(Client400MissingPropertyExceptionFactory::class)->reveal();
        $client400BadContentExceptionFactory = $client400BadContentExceptionFactory ?? self::prophesize(Client400BadContentExceptionFactory::class)->reveal();

        return new ElementHydrationSearchStepEventListener(
            $elementManager,
            $elementToRawService,
            $accessChecker,
            $authProvider,
            $expressionService,
            $client400BadContentExceptionFactory,
            $client400MissingPropertyExceptionFactory
        );
    }

    public function testParseElementIdsFailsOnMissingElementIdsQuery(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame('Expected either query to be string|array or previous step result to contain elements or paths.', $exception->getDetail());
        }
    }

    public function testParseElementIdsFailsOnNonStringElementId(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '02IiNr0G8viOVKkETVQFbgu6KmMBxZapcuFGX',
                    23847234,
                ],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame('Endpoint expects property \'elementId[1]\' to be string, got int 23847234.', $exception->getDetail());
        }
    }

    public function testParseElementIdsFailsOnUnparseableElementId(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '02IiNr0G8viOVKkETVQFbgu6KmMBxZapcuFGX',
                    'thisIsNotAnUuid',
                ],
            ],
            [],
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame('Endpoint expects property \'elementId[1]\' to be uuid (string), got string \'thisIsNotAnUuid\'.', $exception->getDetail());
        }
    }

    public function testParseElementIdsFailsTooManyElementIds(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $elementIds = [];
        for ($i = 0; $i < 1234; ++$i) {
            $elementIds[] = Uuid::generate()->toString();
        }

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => $elementIds,
            ],
            [],
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame('Number of elementIds to hydrate is 1234, which exceeds the limit of 1000; please limit the number of results returned by previous steps or parameters.', $exception->getDetail());
        }
    }

    public function testParseElementIdsDoesNotFailOnAlmostTooManyElementIds(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();

        $uuids = [];
        for ($i = 0; $i < 1000; ++$i) {
            $uuids[] = Uuid::generate()->toString();
        }

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => $uuids,
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        /** @var Uuid[] $elementIds */
        $this->assertCount(1000, $elementIds);
    }

    public function testParseElementIdsOnEmptyElementIdsParameterReturnsEmptyArray(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);

        $this->assertEmpty($elementIds);
    }

    public function testParseElementIdsAcceptStringUuids(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '02IiNr0G8viOVKjjdvufOUfNmf2TFmn7TCGUT',
                    '02IiNr0G8viOVKkJgBcpTKTA3NF0oCaSyMxYz',
                ],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        /** @var Uuid[] $elementIds */
        $this->assertCount(2, $elementIds);
        $this->assertSame('02IiNr0G8viOVKjjdvufOUfNmf2TFmn7TCGUT', $elementIds[0]->toString());
        $this->assertSame('02IiNr0G8viOVKkJgBcpTKTA3NF0oCaSyMxYz', $elementIds[1]->toString());
    }

    public function testParseElementIdsAcceptObjectUuids(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    Uuid::fromString('02IiNr0G8viOVKkWnSh17NG7foVI0dNlknOea'),
                    Uuid::fromString('02IiNr0G8viOVKl8PjoirTPdFGgA9To37EZU8'),
                ],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        /** @var Uuid[] $elementIds */
        $this->assertCount(2, $elementIds);
        $this->assertSame('02IiNr0G8viOVKkWnSh17NG7foVI0dNlknOea', $elementIds[0]->toString());
        $this->assertSame('02IiNr0G8viOVKl8PjoirTPdFGgA9To37EZU8', $elementIds[1]->toString());
    }

    public function testParseElementIdsRemovesRedundantElementIds(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '02IiNr0G8viOVKjX4hDcxHW7Y6rFd7YcfCc0m',
                    '02IiNr0G8viOVKjX4hDcxHW7Y6rFd7YcfCc0m',
                    '02IiNr0G8viOVKjX4hDcxHW7Y6rFd7YcfCc0m',
                    '02IiNr0G8viOVKjl0sKNeSbWj6ee42j0a1F6j',
                    '02IiNr0G8viOVKjl0sKNeSbWj6ee42j0a1F6j',
                ],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        /** @var Uuid[] $elementIds */
        $this->assertCount(2, $elementIds);
        $this->assertSame('02IiNr0G8viOVKjX4hDcxHW7Y6rFd7YcfCc0m', $elementIds[0]->toString());
        $this->assertSame('02IiNr0G8viOVKjl0sKNeSbWj6ee42j0a1F6j', $elementIds[1]->toString());

        $debugData = $event->getDebugData();
        $this->assertArrayHasKey('element-hydration:redundantElementIds', $debugData);
        $redundantElementIdsDebugData = $debugData['element-hydration:redundantElementIds'];
        $this->assertSame('Removed 3 redundant elementIds.', $redundantElementIdsDebugData['message']);
        $this->assertSame(
            [
                '02IiNr0G8viOVKjX4hDcxHW7Y6rFd7YcfCc0m',
                '02IiNr0G8viOVKjl0sKNeSbWj6ee42j0a1F6j',
            ],
            $redundantElementIdsDebugData['removedElementIds']
        );
    }

    public function testGetPreviousSearchStepResultsReturnsNullIfStepResultsIsMissing(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getPreviousSearchStepResults');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertNull($result);
    }

    public function testGetPreviousSearchStepResultsReturnsNullIfStepResultsNotArray(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getPreviousSearchStepResults');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => 'something else',
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertNull($result);
    }

    public function testGetPreviousSearchStepResultsReturnsNullIfStepResultsIsEmpty(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getPreviousSearchStepResults');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertNull($result);
    }

    public function testGetPreviousSearchStepResultsReturnsLastStepResult(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getPreviousSearchStepResults');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [
                    [
                        'identifier' => 'a',
                    ],
                    [
                        'identifier' => 'b',
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertEquals(
            [
                'identifier' => 'b',
            ],
            $result
        );
    }

    public function testGetElementIdsFromElementsResultReturnsNullIfElementsIsMissing(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromElementsResult');

        $stepResults = [];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertNull($result);
    }

    public function testGetElementIdsFromElementsResultReturnsNullIfElementsIsNotArray(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromElementsResult');

        $stepResults = [
            'elements' => 'something else',
        ];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertNull($result);
    }

    public function testGetElementIdsFromElementsResultReturnsEmptyArrayIfElementsIsEmpty(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromElementsResult');

        $stepResults = [
            'elements' => [],
        ];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertEmpty($result);
    }

    public function testGetElementIdsFromElementsResultParsesElementsArrayCorrectly(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromElementsResult');

        $stepResults = [
            'elements' => [
                [],
                [
                    'id' => '02IiNr0G8viOVKl9w2uaAaDm4kZJPrHGnKcKW',
                ],
                [
                    'id' => Uuid::fromString('02IiNr0G8viOVKki5THFhQbp1dxm9KHdBACtK'),
                ],
            ],
        ];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertCount(2, $result);
        $this->assertSame('02IiNr0G8viOVKl9w2uaAaDm4kZJPrHGnKcKW', (string) $result[0]);
        $this->assertSame('02IiNr0G8viOVKki5THFhQbp1dxm9KHdBACtK', (string) $result[1]);
    }

    public function testGetElementIdsFromElementsResultThrowsWhenElementIdIsNotString(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromElementsResult');

        $stepResults = [
            'elements' => [
                [
                    'id' => 12345,
                ],
            ],
        ];

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'elements[0].id' to be string, got int 12345.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromElementsResultThrowsWhenElementIdIsNotUuidString(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromElementsResult');

        $stepResults = [
            'elements' => [
                [
                    'id' => 'not an uuid string',
                ],
            ],
        ];

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'elements[0].id' to be uuid (string), got string 'not an uuid string'.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromPathsResultReturnsNullIfPathsIsMissing(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');

        $stepResults = [];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertNull($result);
    }

    public function testGetElementIdsFromPathsResultReturnsNullIfPathsIsNotArray(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');

        $stepResults = [
            'paths' => 'something else',
        ];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertNull($result);
    }

    public function testGetElementIdsFromPathsResultReturnsEmptyArrayIfPathsIsEmpty(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');

        $stepResults = [
            'paths' => [],
        ];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertEmpty($result);
    }

    public function testGetElementIdsFromPathsResultParsesPathsCorrectly(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');

        $stepResults = [
            'paths' => [
                [], // continue
                ['nodeIds' => []], // continue
                ['relationIds' => []], // continue
                [
                    'nodeIds' => [],
                    'relationIds' => [],
                ],
                [
                    'nodeIds' => ['02IiNr0G8viOVKkdShQGdK5jFCCcvfHqZ8L2N', Uuid::fromString('02IiNr0G8viOVKk0RcjGWQ1HjneKpnqiX9Wsh')],
                    'relationIds' => ['02IiNr0G8viOVKkkYI6XXsUQSIFXKEOT0C94v', Uuid::fromString('02IiNr0G8viOVKkDNXXUGWeZQvOjJS4uOd0TG')],
                ],
                [
                    'nodeIds' => ['02IiNr0G8viOVKk3IubpJwcsB9w8onekeCSCc', '02IiNr0G8viOVKjq5yWKMcFaAviWyozhLJMil'], // test unequal distribution I
                    'relationIds' => [],
                ],
                [
                    'nodeIds' => [],
                    'relationIds' => ['02IiNr0G8viOVKkLs2QcOsoeBJO3oVRuSynbI', '02IiNr0G8viOVKjo7KFPm0KfCpLtUYxavZfFk'], // test unequal distribution II
                ],
            ],
        ];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertCount(8, $result);
        $this->assertSame('02IiNr0G8viOVKjo7KFPm0KfCpLtUYxavZfFk', (string) $result[0]);
        $this->assertSame('02IiNr0G8viOVKjq5yWKMcFaAviWyozhLJMil', (string) $result[1]);
        $this->assertSame('02IiNr0G8viOVKk0RcjGWQ1HjneKpnqiX9Wsh', (string) $result[2]);
        $this->assertSame('02IiNr0G8viOVKk3IubpJwcsB9w8onekeCSCc', (string) $result[3]);
        $this->assertSame('02IiNr0G8viOVKkDNXXUGWeZQvOjJS4uOd0TG', (string) $result[4]);
        $this->assertSame('02IiNr0G8viOVKkLs2QcOsoeBJO3oVRuSynbI', (string) $result[5]);
        $this->assertSame('02IiNr0G8viOVKkdShQGdK5jFCCcvfHqZ8L2N', (string) $result[6]);
        $this->assertSame('02IiNr0G8viOVKkkYI6XXsUQSIFXKEOT0C94v', (string) $result[7]);
    }

    public function testGetElementIdsFromPathsResultThrowsWhenNodeIdIsNotString(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');

        $stepResults = [
            'paths' => [
                [
                    'nodeIds' => [12345],
                    'relationIds' => [],
                ],
            ],
        ];

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'paths[0].nodeIds[0]' to be string, got int 12345.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromPathsResultThrowsWhenNodeIdIsNotUuidString(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');

        $stepResults = [
            'paths' => [
                [
                    'nodeIds' => ['something else'],
                    'relationIds' => [],
                ],
            ],
        ];

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'paths[0].nodeIds[0]' to be uuid (string), got string 'something else'.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromPathsResultThrowsWhenRelationIdIsNotString(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');

        $stepResults = [
            'paths' => [
                [
                    'nodeIds' => [],
                    'relationIds' => [12345],
                ],
            ],
        ];

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'paths[0].relationIds[0]' to be string, got int 12345.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromPathsResultThrowsWhenRelationIdIsNotUuidString(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');

        $stepResults = [
            'paths' => [
                [
                    'nodeIds' => [],
                    'relationIds' => ['something else'],
                ],
            ],
        ];

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'paths[0].relationIds[0]' to be uuid (string), got string 'something else'.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromQueryReturnsNullIfQueryIsNull(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertNull($result);
    }

    public function testGetElementIdsFromQueryThrowsIfQueryIsNotArray(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            'something else',
            []
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'query' to be array, got string 'something else'.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromQueryThrowsIfQueryDoesNotContainElementIds(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'missing-property']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400MissingPropertyExceptionFactory: $client400MissingPropertyExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [],
            []
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400MissingPropertyException $exception) {
            $this->assertSame("Endpoint requires that the request contains property 'elementIds' to be set to array of element ids or expression yielding array of element ids.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromQueryExecutesExpression(): void
    {
        $expressionService = $this->prophesize(ExpressionService::class);
        $expressionService
            ->runExpression(Argument::is('["02IiNr0G8viOVKkWYKAgLiy1OD5FLphoim7zD"]'), Argument::is(['key' => 'value']))
            ->shouldBeCalledOnce()
            ->willReturn(['02IiNr0G8viOVKkWYKAgLiy1OD5FLphoim7zD']);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            expressionService: $expressionService->reveal()
        );
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => '{{["02IiNr0G8viOVKkWYKAgLiy1OD5FLphoim7zD"]}}',
            ],
            [
                'key' => 'value',
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(1, $result);
        $this->assertSame('02IiNr0G8viOVKkWYKAgLiy1OD5FLphoim7zD', (string) $result[0]);
    }

    public function testGetElementIdsFromQueryThrowsIfElementIdsIsExpressionNotReturningArray(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $expressionService = $this->prophesize(ExpressionService::class);
        $expressionService
            ->runExpression(Argument::is('"02IiNr0G8viOVKk8VfnDCatFjTHNc1ixNIc37"'), Argument::is(['key' => 'value']))
            ->shouldBeCalledOnce()
            ->willReturn('02IiNr0G8viOVKk8VfnDCatFjTHNc1ixNIc37');
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            expressionService: $expressionService->reveal(),
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => '{{"02IiNr0G8viOVKk8VfnDCatFjTHNc1ixNIc37"}}',
            ],
            [
                'key' => 'value',
            ]
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'elementIds' to be array, got string '02IiNr0G8viOVKk8VfnDCatFjTHNc1ixNIc37'.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromQueryThrowsIfElementIdsIsNotArray(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => 'something else',
            ],
            []
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'elementIds' to be array, got string 'something else'.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromQueryCanParseElementIdsFromArrayCorrectly(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '02IiNr0G8viOVKkBUV1UZlzAyzBdcAVxsSEUK',
                    Uuid::fromString('02IiNr0G8viOVKkXByo93erV2RUcHZQgMsVx2'),
                ],
            ],
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(2, $result);
        $this->assertSame('02IiNr0G8viOVKkBUV1UZlzAyzBdcAVxsSEUK', (string) $result[0]);
        $this->assertSame('02IiNr0G8viOVKkXByo93erV2RUcHZQgMsVx2', (string) $result[1]);
    }

    public function testGetElementIdsFromQueryThrowsIfElementIdIsNotString(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [12345],
            ],
            []
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'elementId[0]' to be string, got int 12345.", $exception->getDetail());
        }
    }

    public function testGetElementIdsFromQueryThrowsIfElementIdIsNotUuidString(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => ['something else'],
            ],
            []
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'elementId[0]' to be uuid (string), got string 'something else'.", $exception->getDetail());
        }
    }

    public function testParseElementIdsFromEventCorrectlyParsesElementsFromQuery(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '02IiNr0G8viOVKjsvRtJR0W5rEIxBx8DJq8uP',
                    Uuid::fromString('02IiNr0G8viOVKk9OLW68lL1RAdQaCW49Uhr8'),
                ],
            ],
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(2, $result);
        $this->assertSame('02IiNr0G8viOVKjsvRtJR0W5rEIxBx8DJq8uP', (string) $result[0]);
        $this->assertSame('02IiNr0G8viOVKk9OLW68lL1RAdQaCW49Uhr8', (string) $result[1]);
    }

    public function testParseElementIdsFromEventFailsIfNeitherQueryNorPreviousResultIsAvailable(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame('Expected either query to be string|array or previous step result to contain elements or paths.', $exception->getDetail());
        }
    }

    public function testParseElementIdsFromEventCorrectlyParsesElementsFromElementsResult(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [
                    [
                        'elements' => [
                            [
                                'id' => '02IiNr0G8viOVKjv1gL4gz2cXuEtg31kA87be',
                            ],
                            [
                                'id' => Uuid::fromString('02IiNr0G8viOVKl54AdJIqMAtE6DQylgJouNH'),
                            ],
                        ],
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(2, $result);
        $this->assertSame('02IiNr0G8viOVKjv1gL4gz2cXuEtg31kA87be', (string) $result[0]);
        $this->assertSame('02IiNr0G8viOVKl54AdJIqMAtE6DQylgJouNH', (string) $result[1]);
    }

    public function testParseElementIdsFromEventCorrectlyParsesElementsFromPathsResult(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [
                    [
                        'paths' => [
                            [
                                'nodeIds' => ['02IiNr0G8viOVKjwOdAVTRfZeAZH1z0LVf8rC'],
                                'relationIds' => [Uuid::fromString('02IiNr0G8viOVKjblD4nCx3v5CWVX77O8wAXd')],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(2, $result);
        $this->assertSame('02IiNr0G8viOVKjblD4nCx3v5CWVX77O8wAXd', (string) $result[0]);
        $this->assertSame('02IiNr0G8viOVKjwOdAVTRfZeAZH1z0LVf8rC', (string) $result[1]);
    }

    public function testParseElementIdsFromEventThrowsIfNoElementIdsCanBeLocated(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [
                    [],
                ],
            ]
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame('Expected either query to be string|array or previous step result to contain elements or paths.', $exception->getDetail());
        }
    }

    public function testParseElementIdsFromEventParsesQueryBeforePreviousResults(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => ['02IiNr0G8viOVKjiqruAotU5Rlt31RzcSCEV8'],
            ],
            [
                'stepResults' => [
                    [
                        'elements' => [
                            [
                                'id' => '02IiNr0G8viOVKkVqsq2onijXJ5Q6RaRhJKAe',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(1, $result);
        $this->assertSame('02IiNr0G8viOVKjiqruAotU5Rlt31RzcSCEV8', (string) $result[0]);
    }

    public function testParseElementIdsFromEventParsesElementsBeforePaths(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [
                    [
                        'elements' => [
                            [
                                'id' => '02IiNr0G8viOVKlBDJUwIPrc5ACqUouDMmcVs',
                            ],
                        ],
                        'paths' => [
                            [
                                'nodeIds' => ['02IiNr0G8viOVKjodRBbKDZ2ctMUZB9cmepgr'],
                                'relationIds' => [],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(1, $result);
        $this->assertSame('02IiNr0G8viOVKlBDJUwIPrc5ACqUouDMmcVs', (string) $result[0]);
    }

    public function testGetElementDataExecutesProperly(): void
    {
        $elementIds = [
            Uuid::fromString('02IiNr0G8viOVKkKYuPxtRmJz09u2qdKJPUMu'),
            Uuid::fromString('02IiNr0G8viOVKk2ZcxgSwQRLaVrwLAqjIMaI'),
            Uuid::fromString('02IiNr0G8viOVKkozq4Cg2vCEH9iJ4EBjDnrU'),
        ];
        $elementA = new NodeElement();
        $elementA->setId($elementIds[0]);
        $elementA->setLabel('Something');
        $rawElementA = [
            'id' => '02IiNr0G8viOVKkKYuPxtRmJz09u2qdKJPUMu',
            'type' => 'Something',
            'data' => [],
        ];
        $elementB = new NodeElement();
        $elementB->setId($elementIds[2]);
        $elementB->setLabel('Something');
        $rawElementB = [
            'id' => '02IiNr0G8viOVKkozq4Cg2vCEH9iJ4EBjDnrU',
            'type' => 'Something',
            'data' => [],
        ];

        $elementManager = $this->prophesize(ElementManager::class);
        // elements which don't exist are left out by the element manager
        $elementManager
            ->getElements($elementIds)
            ->shouldBeCalledOnce()
            ->willReturn([$elementA, $elementB]);

        $elementToRawService = $this->prophesize(ElementToRawService::class);
        $elementToRawService
            ->elementToRaw(Argument::is($elementA))
            ->shouldBeCalledOnce()
            ->willReturn($rawElementA);
        $elementToRawService
            ->elementToRaw(Argument::is($elementB))
            ->shouldBeCalledOnce()
            ->willReturn($rawElementB);

        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            elementManager: $elementManager->reveal(),
            elementToRawService: $elementToRawService->reveal()
        );
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementData');

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$elementIds]);
        $this->assertCount(2, $result);
        $this->assertEquals([$rawElementA, $rawElementB], $result);
    }

    public function testFilterElementIdsToAccessibleOnlyAddsNoDebugMessageWhenNoFilteringHasHappened(): void
    {
        $userId = Uuid::fromString('02IiNr0G8viOVKlLANg8yGxSrIfBj8rdDajmf');

        $elementIds = [
            Uuid::fromString('02IiNr0G8viOVKl3jAWwgWR143yMf5as7QwHP'),
            Uuid::fromString('02IiNr0G8viOVKkHH4bG3hcZXuzn6jp7uD33f'),
        ];

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider
            ->getUserId()
            ->shouldBeCalledOnce()
            ->willReturn($userId);

        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker
            ->checkUserAccessToMultipleElements(Argument::is($userId), Argument::is($elementIds))
            ->shouldBeCalledOnce()
            ->willReturn($elementIds);

        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            accessChecker: $accessChecker->reveal(),
            authProvider: $authProvider->reveal()
        );
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'filterElementIdsToAccessibleOnly');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$elementIds, $event]);
        $this->assertCount(2, $result);
        $this->assertSame('02IiNr0G8viOVKl3jAWwgWR143yMf5as7QwHP', (string) $result[0]);
        $this->assertSame('02IiNr0G8viOVKkHH4bG3hcZXuzn6jp7uD33f', (string) $result[1]);
        $this->assertCount(1, $event->getDebugData());
    }

    public function testFilterElementIdsToAccessibleOnlyAddsDebugMessageWhenFilteringHasHappened(): void
    {
        $userId = Uuid::fromString('02IiNr0G8viOVKlLANg8yGxSrIfBj8rdDajmf');

        $elementIds = [
            Uuid::fromString('02IiNr0G8viOVKl3jAWwgWR143yMf5as7QwHP'),
            Uuid::fromString('02IiNr0G8viOVKkHH4bG3hcZXuzn6jp7uD33f'),
        ];

        $filteredElementIds = [
            Uuid::fromString('02IiNr0G8viOVKl3jAWwgWR143yMf5as7QwHP'),
        ];

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider
            ->getUserId()
            ->shouldBeCalledOnce()
            ->willReturn($userId);

        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker
            ->checkUserAccessToMultipleElements(Argument::is($userId), Argument::is($elementIds))
            ->shouldBeCalledOnce()
            ->willReturn($filteredElementIds);

        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            accessChecker: $accessChecker->reveal(),
            authProvider: $authProvider->reveal()
        );
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'filterElementIdsToAccessibleOnly');

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$elementIds, $event]);
        $this->assertCount(1, $result);
        $this->assertSame('02IiNr0G8viOVKl3jAWwgWR143yMf5as7QwHP', (string) $result[0]);
        $this->assertCount(2, $event->getDebugData());
        $debugMessage = array_values($event->getDebugData())[1];
        $this->assertSame('Removed elementIds due to missing access rights.', $debugMessage['message']);
    }

    public function testOnSearchStepEventReturnsEarlyOnWrongEventType(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            null,
            []
        );

        $elementHydrationSearchStepEventListener->onSearchStepEvent($event);

        $this->assertCount(1, $event->getDebugData());
    }

    public function testOnSearchStepEventHandlesEventCorrectly(): void
    {
        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [],
            ],
            []
        );

        $userId = Uuid::fromString('02IiNr0G8viOVKlLANg8yGxSrIfBj8rdDajmf');

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider
            ->getUserId()
            ->shouldBeCalledOnce()
            ->willReturn($userId);

        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker
            ->checkUserAccessToMultipleElements(Argument::is($userId), Argument::is([]))
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            accessChecker: $accessChecker->reveal(),
            authProvider: $authProvider->reveal()
        );

        $elementHydrationSearchStepEventListener->onSearchStepEvent($event);

        $this->assertCount(6, $event->getDebugData());
        $this->assertTrue($event->isPropagationStopped());

        $this->assertArrayHasKey('step-handler', $event->getDebugData());
        $this->assertArrayHasKey('start', $event->getDebugData());
        $this->assertArrayHasKey('accessibleElementIds', $event->getDebugData());
        $this->assertArrayHasKey('end', $event->getDebugData());
        $this->assertArrayHasKey('duration', $event->getDebugData());
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $elementMetadata
     * @param Uuid[]                                          $elementIds
     *
     * @return array<mixed[]>
     */
    private function getElementDataWithMetadata(array $elementIds, array $elementMetadata): array
    {
        $elementManager = $this->prophesize(ElementManager::class);
        $elementToRawService = $this->prophesize(ElementToRawService::class);
        $elements = [];
        foreach ($elementIds as $elementId) {
            $element = new NodeElement();
            $element->setId($elementId);
            $element->setLabel('Something');
            $elements[] = $element;
            $elementToRawService->elementToRaw(Argument::is($element))->willReturn([
                'type' => 'Something',
                'id' => $elementId->toString(),
                'data' => ['name' => 'x'],
                'file' => ['size' => 1],
            ]);
        }
        $elementManager->getElements($elementIds)->willReturn($elements);
        $listener = $this->buildElementHydrationSearchStepEventListener(
            elementManager: $elementManager->reveal(),
            elementToRawService: $elementToRawService->reveal()
        );
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementData');

        /** @var array<mixed[]> $result */
        $result = $method->invokeArgs($listener, [$elementIds, $elementMetadata]);

        return $result;
    }

    public function testGetElementDataAddsMetadataOfPreviousStepsAfterNativeProperties(): void
    {
        $elementId = Uuid::fromString('02IiNr0G8viOVKkKYuPxtRmJz09u2qdKJPUMu');

        $result = $this->getElementDataWithMetadata([$elementId], [
            $elementId->toString() => [
                0 => ['score' => 1.5, 'highlight' => ['name' => ['<em>x</em>']]],
            ],
        ]);

        $this->assertSame(['type', 'id', 'data', 'file', 'metadata'], array_keys($result[0]));
        $this->assertEquals(
            ['score' => 1.5, 'highlight' => ['name' => ['<em>x</em>']]],
            $result[0]['metadata']
        );
    }

    public function testGetElementDataMergesMetadataOfStepsWhereLaterStepsOverwriteEarlierOnes(): void
    {
        $elementId = Uuid::fromString('02IiNr0G8viOVKkKYuPxtRmJz09u2qdKJPUMu');

        $result = $this->getElementDataWithMetadata([$elementId], [
            $elementId->toString() => [
                2 => ['score' => 2.0, 'sort' => ['b']],
                0 => ['score' => 1.0, 'highlight' => ['name' => ['<em>x</em>']]],
            ],
        ]);

        // steps are applied in the order of their index, not in the order they were added
        $this->assertSame(
            '{"score":2,"highlight":{"name":["<em>x<\\/em>"]},"sort":["b"]}',
            json_encode($result[0]['metadata'])
        );
        $singleStepResult = $this->getElementDataWithMetadata([$elementId], [
            $elementId->toString() => [0 => ['score' => 1.0]],
        ]);
        $this->assertSame('{"score":1}', json_encode($singleStepResult[0]['metadata']));
    }

    public function testGetElementDataOnlyAddsMetadataToElementsWhichHaveSome(): void
    {
        $idWithMetadata = Uuid::fromString('02IiNr0G8viOVKkKYuPxtRmJz09u2qdKJPUMu');
        $idWithoutMetadata = Uuid::fromString('02IiNr0G8viOVKk2ZcxgSwQRLaVrwLAqjIMaI');

        $result = $this->getElementDataWithMetadata([$idWithMetadata, $idWithoutMetadata], [
            $idWithMetadata->toString() => [0 => ['score' => 1.5]],
            // metadata of elements which are not part of the hydration is ignored
            '02IiNr0G8viOVKkozq4Cg2vCEH9iJ4EBjDnrU' => [0 => ['score' => 9.9]],
            // empty metadata does not result in an empty metadata property
            $idWithoutMetadata->toString() => [],
        ]);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('metadata', $result[0]);
        $this->assertArrayNotHasKey('metadata', $result[1]);
        $this->assertSame(['type', 'id', 'data', 'file'], array_keys($result[1]));
    }

    public function testOnSearchStepEventPropagatesMetadataOfElasticsearchStepResultOnlyForAccessibleElements(): void
    {
        $accessibleId = Uuid::fromString('02IiNr0G8viOVKkKYuPxtRmJz09u2qdKJPUMu');
        $inaccessibleId = Uuid::fromString('02IiNr0G8viOVKk2ZcxgSwQRLaVrwLAqjIMaI');
        $userId = Uuid::fromString('02IiNr0G8viOVKlLANg8yGxSrIfBj8rdDajmf');

        // synthetic result of a previous Elasticsearch step, as the controller would hand it over
        $previousStepResult = [
            'elements' => [
                ['id' => $inaccessibleId, 'type' => 'Something', 'metadata' => ['score' => 9.0, 'highlight' => ['secret' => ['<em>secret</em>']]]],
                ['id' => $accessibleId, 'type' => 'Something', 'metadata' => ['score' => 1.5, 'highlight' => ['name' => ['<em>x</em>']]]],
            ],
            'totalHits' => 2,
            'maxScore' => 9.0,
        ];
        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            ['stepResults' => [$previousStepResult]],
            [
                $inaccessibleId->toString() => [0 => ['score' => 9.0, 'highlight' => ['secret' => ['<em>secret</em>']]]],
                $accessibleId->toString() => [0 => ['score' => 1.5, 'highlight' => ['name' => ['<em>x</em>']]]],
            ]
        );

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn($userId);
        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker
            ->checkUserAccessToMultipleElements(Argument::is($userId), Argument::any())
            ->willReturn([$accessibleId]);

        $element = new NodeElement();
        $element->setId($accessibleId);
        $element->setLabel('Something');
        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElements([$accessibleId])->shouldBeCalledOnce()->willReturn([$element]);
        $elementToRawService = $this->prophesize(ElementToRawService::class);
        $elementToRawService->elementToRaw(Argument::is($element))->willReturn([
            'type' => 'Something',
            'id' => $accessibleId->toString(),
            'data' => ['name' => 'x'],
        ]);

        $listener = $this->buildElementHydrationSearchStepEventListener(
            elementManager: $elementManager->reveal(),
            elementToRawService: $elementToRawService->reveal(),
            accessChecker: $accessChecker->reveal(),
            authProvider: $authProvider->reveal()
        );

        $listener->onSearchStepEvent($event);

        $results = $event->getResults();
        $this->assertCount(1, $results);
        $this->assertSame($accessibleId->toString(), $results[0]['id']);
        $this->assertSame(
            '{"score":1.5,"highlight":{"name":["<em>x<\\/em>"]}}',
            json_encode($results[0]['metadata'])
        );
        $this->assertStringNotContainsString('secret', json_encode($results) ?: '');
    }
}
