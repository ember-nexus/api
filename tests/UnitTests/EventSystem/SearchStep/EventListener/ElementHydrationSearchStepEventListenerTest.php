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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
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
            $client400MissingPropertyExceptionFactory,
            $client400BadContentExceptionFactory
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
        $method->setAccessible(true);

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
                    '718cf4c7-6815-49e1-8b1f-082949365c5f',
                    23847234,
                ],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $method->setAccessible(true);

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
                    '718cf4c7-6815-49e1-8b1f-082949365c5f',
                    'thisIsNotAnUuid',
                ],
            ],
            [],
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $method->setAccessible(true);

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
            $elementIds[] = sprintf('00000000-0000-0000-0000-00000000%04d', $i);
        }

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => $elementIds,
            ],
            [],
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $method->setAccessible(true);

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
        $base = '00000000-0000-0000-0000-000000000000';
        for ($i = 0; $i < 1000; ++$i) {
            $uuids[] = substr_replace($base, sprintf('%04d', $i), -4);
        }

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => $uuids,
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $method->setAccessible(true);
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        /** @var UuidInterface[] $elementIds */
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
        $method->setAccessible(true);
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
                    '32339603-72de-4f55-868a-9887fb267ae5',
                    '7c3e153f-7de6-4ae7-8161-60c8e631dce3',
                ],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $method->setAccessible(true);
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        /** @var UuidInterface[] $elementIds */
        $this->assertCount(2, $elementIds);
        $this->assertSame('32339603-72de-4f55-868a-9887fb267ae5', $elementIds[0]->toString());
        $this->assertSame('7c3e153f-7de6-4ae7-8161-60c8e631dce3', $elementIds[1]->toString());
    }

    public function testParseElementIdsAcceptObjectUuids(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    Uuid::fromString('972cb6ec-738b-4718-94f4-8fb4b21e441b'),
                    Uuid::fromString('e469ab3b-c6e6-4439-9503-c04234ca80f5'),
                ],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $method->setAccessible(true);
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        /** @var UuidInterface[] $elementIds */
        $this->assertCount(2, $elementIds);
        $this->assertSame('972cb6ec-738b-4718-94f4-8fb4b21e441b', $elementIds[0]->toString());
        $this->assertSame('e469ab3b-c6e6-4439-9503-c04234ca80f5', $elementIds[1]->toString());
    }

    public function testParseElementIdsRemovesRedundantElementIds(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '186ed38c-388b-456c-bfea-09a775c1bfb8',
                    '186ed38c-388b-456c-bfea-09a775c1bfb8',
                    '186ed38c-388b-456c-bfea-09a775c1bfb8',
                    '35076fee-c622-4377-8b7a-fc3acbc57459',
                    '35076fee-c622-4377-8b7a-fc3acbc57459',
                ],
            ],
            []
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIds');
        $method->setAccessible(true);
        $elementIds = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        /** @var UuidInterface[] $elementIds */
        $this->assertCount(2, $elementIds);
        $this->assertSame('186ed38c-388b-456c-bfea-09a775c1bfb8', $elementIds[0]->toString());
        $this->assertSame('35076fee-c622-4377-8b7a-fc3acbc57459', $elementIds[1]->toString());

        $debugData = $event->getDebugData();
        $this->assertArrayHasKey('element-hydration:redundantElementIds', $debugData);
        $redundantElementIdsDebugData = $debugData['element-hydration:redundantElementIds'];
        $this->assertSame('Removed 3 redundant elementIds.', $redundantElementIdsDebugData['message']);
        $this->assertSame(
            [
                '186ed38c-388b-456c-bfea-09a775c1bfb8',
                '35076fee-c622-4377-8b7a-fc3acbc57459',
            ],
            $redundantElementIdsDebugData['removedElementIds']
        );
    }

    public function testGetPreviousSearchStepResultsReturnsNullIfStepResultsIsMissing(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getPreviousSearchStepResults');
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

        $stepResults = [];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertNull($result);
    }

    public function testGetElementIdsFromElementsResultReturnsNullIfElementsIsNotArray(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromElementsResult');
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

        $stepResults = [
            'elements' => [
                [],
                [
                    'id' => 'e7842691-53a4-4482-8ab5-15e8c53439b3',
                ],
                [
                    'id' => Uuid::fromString('ae54e7bc-3b07-4508-b910-b4cac756f499'),
                ],
            ],
        ];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertCount(2, $result);
        $this->assertSame('e7842691-53a4-4482-8ab5-15e8c53439b3', (string) $result[0]);
        $this->assertSame('ae54e7bc-3b07-4508-b910-b4cac756f499', (string) $result[1]);
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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

        $stepResults = [];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertNull($result);
    }

    public function testGetElementIdsFromPathsResultReturnsNullIfPathsIsNotArray(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromPathsResult');
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
                    'nodeIds' => ['a4db6c8d-3475-4a29-bfe7-3f7080b03d10', Uuid::fromString('54bea3cb-227b-4cde-a77c-a749cd3ae112')],
                    'relationIds' => ['b36182c2-4f4e-453b-98ed-f4ecb642d725', Uuid::fromString('6f483b13-df86-4f0b-8da1-7b163a50aee5')],
                ],
                [
                    'nodeIds' => ['5a92d393-663e-4d4e-ac25-bd9f3b18eacd', '3f741928-03ff-4842-ab19-96aca977df1f'], // test unequal distribution I
                    'relationIds' => [],
                ],
                [
                    'nodeIds' => [],
                    'relationIds' => ['80bcac38-7f85-4a67-a024-dffe56a5c3c3', '3b649923-4ff0-4733-a441-c9afc1e3ef50'], // test unequal distribution II
                ],
            ],
        ];

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$stepResults]);
        $this->assertCount(8, $result);
        $this->assertSame('3b649923-4ff0-4733-a441-c9afc1e3ef50', (string) $result[0]);
        $this->assertSame('3f741928-03ff-4842-ab19-96aca977df1f', (string) $result[1]);
        $this->assertSame('54bea3cb-227b-4cde-a77c-a749cd3ae112', (string) $result[2]);
        $this->assertSame('5a92d393-663e-4d4e-ac25-bd9f3b18eacd', (string) $result[3]);
        $this->assertSame('6f483b13-df86-4f0b-8da1-7b163a50aee5', (string) $result[4]);
        $this->assertSame('80bcac38-7f85-4a67-a024-dffe56a5c3c3', (string) $result[5]);
        $this->assertSame('a4db6c8d-3475-4a29-bfe7-3f7080b03d10', (string) $result[6]);
        $this->assertSame('b36182c2-4f4e-453b-98ed-f4ecb642d725', (string) $result[7]);
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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
            ->runExpression(Argument::is('["96a7100a-e663-4fae-92b4-b450622a21fc"]'), Argument::is(['key' => 'value']))
            ->shouldBeCalledOnce()
            ->willReturn(['96a7100a-e663-4fae-92b4-b450622a21fc']);
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            expressionService: $expressionService->reveal()
        );
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => '{{["96a7100a-e663-4fae-92b4-b450622a21fc"]}}',
            ],
            [
                'key' => 'value',
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(1, $result);
        $this->assertSame('96a7100a-e663-4fae-92b4-b450622a21fc', (string) $result[0]);
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
            ->runExpression(Argument::is('"654eda21-8320-4bfd-9fe7-f3e6f892ac35"'), Argument::is(['key' => 'value']))
            ->shouldBeCalledOnce()
            ->willReturn('654eda21-8320-4bfd-9fe7-f3e6f892ac35');
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener(
            expressionService: $expressionService->reveal(),
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'getElementIdsFromQuery');
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => '{{"654eda21-8320-4bfd-9fe7-f3e6f892ac35"}}',
            ],
            [
                'key' => 'value',
            ]
        );

        try {
            $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'elementIds' to be array, got string '654eda21-8320-4bfd-9fe7-f3e6f892ac35'.", $exception->getDetail());
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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '6b61af2a-1ee3-4d6e-ba50-3dc3905bf10d',
                    Uuid::fromString('97faff07-8aff-48cb-8f7b-761f2df1ee78'),
                ],
            ],
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(2, $result);
        $this->assertSame('6b61af2a-1ee3-4d6e-ba50-3dc3905bf10d', (string) $result[0]);
        $this->assertSame('97faff07-8aff-48cb-8f7b-761f2df1ee78', (string) $result[1]);
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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => [
                    '45421836-ed2c-4848-8645-89b9cccd9274',
                    Uuid::fromString('67182ba2-3c39-4611-87f9-7d7e04b04606'),
                ],
            ],
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(2, $result);
        $this->assertSame('45421836-ed2c-4848-8645-89b9cccd9274', (string) $result[0]);
        $this->assertSame('67182ba2-3c39-4611-87f9-7d7e04b04606', (string) $result[1]);
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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [
                    [
                        'elements' => [
                            [
                                'id' => '49966bcc-6134-4662-81a1-1b717c3c4cbe',
                            ],
                            [
                                'id' => Uuid::fromString('dd899b7e-2579-4329-944c-fc7940241512'),
                            ],
                        ],
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(2, $result);
        $this->assertSame('49966bcc-6134-4662-81a1-1b717c3c4cbe', (string) $result[0]);
        $this->assertSame('dd899b7e-2579-4329-944c-fc7940241512', (string) $result[1]);
    }

    public function testParseElementIdsFromEventCorrectlyParsesElementsFromPathsResult(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [
                    [
                        'paths' => [
                            [
                                'nodeIds' => ['4c6b2e15-1559-4a76-9837-c123c9e87a5a'],
                                'relationIds' => [Uuid::fromString('2203f89c-6eeb-40fc-891f-574251348464')],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(2, $result);
        $this->assertSame('2203f89c-6eeb-40fc-891f-574251348464', (string) $result[0]);
        $this->assertSame('4c6b2e15-1559-4a76-9837-c123c9e87a5a', (string) $result[1]);
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
        $method->setAccessible(true);

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
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            [
                'elementIds' => ['309321cd-e74c-495f-9ddf-9a7badea188c'],
            ],
            [
                'stepResults' => [
                    [
                        'elements' => [
                            [
                                'id' => '9531925f-ed9f-4c04-9307-8ff933b02150',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(1, $result);
        $this->assertSame('309321cd-e74c-495f-9ddf-9a7badea188c', (string) $result[0]);
    }

    public function testParseElementIdsFromEventParsesElementsBeforePaths(): void
    {
        $elementHydrationSearchStepEventListener = $this->buildElementHydrationSearchStepEventListener();
        $method = new ReflectionMethod(ElementHydrationSearchStepEventListener::class, 'parseElementIdsFromEvent');
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            [
                'stepResults' => [
                    [
                        'elements' => [
                            [
                                'id' => 'ea262394-4c5c-420b-b690-fd4cc6f38d7e',
                            ],
                        ],
                        'paths' => [
                            [
                                'nodeIds' => ['3c792019-0aa5-4085-b8b2-f656548911e3'],
                                'relationIds' => [],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$event]);
        $this->assertCount(1, $result);
        $this->assertSame('ea262394-4c5c-420b-b690-fd4cc6f38d7e', (string) $result[0]);
    }

    public function testGetElementDataExecutesProperly(): void
    {
        $elementIds = [
            Uuid::fromString('7e0e1daa-f6a6-465f-aa97-2902b4f0f8ba'),
            Uuid::fromString('591331e6-3d74-4758-9bb1-7cbc1bbd3443'),
            Uuid::fromString('bc884188-e54b-449a-b49d-6f00f431cd05'),
        ];
        $elementA = new NodeElement();
        $elementA->setId($elementIds[0]);
        $elementA->setLabel('Something');
        $rawElementA = [
            'id' => '7e0e1daa-f6a6-465f-aa97-2902b4f0f8ba',
            'type' => 'Something',
            'data' => [],
        ];
        $elementB = new NodeElement();
        $elementB->setId($elementIds[2]);
        $elementB->setLabel('Something');
        $rawElementB = [
            'id' => 'bc884188-e54b-449a-b49d-6f00f431cd05',
            'type' => 'Something',
            'data' => [],
        ];

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager
            ->getElement(Argument::is($elementIds[0]))
            ->shouldBeCalledOnce()
            ->willReturn($elementA);
        $elementManager
            ->getElement(Argument::is($elementIds[1]))
            ->shouldBeCalledOnce()
            ->willReturn(null);
        $elementManager
            ->getElement(Argument::is($elementIds[2]))
            ->shouldBeCalledOnce()
            ->willReturn($elementB);

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
        $method->setAccessible(true);

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$elementIds]);
        $this->assertCount(2, $result);
        $this->assertEquals([$rawElementA, $rawElementB], $result);
    }

    public function testFilterElementIdsToAccessibleOnlyAddsNoDebugMessageWhenNoFilteringHasHappened(): void
    {
        $userId = Uuid::fromString('fe99d627-f0e0-42bd-925f-722b56a3b33a');

        $elementIds = [
            Uuid::fromString('dacd0536-3346-44d8-ae28-8c63e5809c75'),
            Uuid::fromString('77485325-62cb-48b8-87b4-61ed30e4fc85'),
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
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$elementIds, $event]);
        $this->assertCount(2, $result);
        $this->assertSame('dacd0536-3346-44d8-ae28-8c63e5809c75', (string) $result[0]);
        $this->assertSame('77485325-62cb-48b8-87b4-61ed30e4fc85', (string) $result[1]);
        $this->assertCount(1, $event->getDebugData());
    }

    public function testFilterElementIdsToAccessibleOnlyAddsDebugMessageWhenFilteringHasHappened(): void
    {
        $userId = Uuid::fromString('fe99d627-f0e0-42bd-925f-722b56a3b33a');

        $elementIds = [
            Uuid::fromString('dacd0536-3346-44d8-ae28-8c63e5809c75'),
            Uuid::fromString('77485325-62cb-48b8-87b4-61ed30e4fc85'),
        ];

        $filteredElementIds = [
            Uuid::fromString('dacd0536-3346-44d8-ae28-8c63e5809c75'),
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
        $method->setAccessible(true);

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        $result = $method->invokeArgs($elementHydrationSearchStepEventListener, [$elementIds, $event]);
        $this->assertCount(1, $result);
        $this->assertSame('dacd0536-3346-44d8-ae28-8c63e5809c75', (string) $result[0]);
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

        $userId = Uuid::fromString('fe99d627-f0e0-42bd-925f-722b56a3b33a');

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
}
