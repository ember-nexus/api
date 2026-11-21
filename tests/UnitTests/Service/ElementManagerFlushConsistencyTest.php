<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\Neo4jClientHelper;
use App\Service\AccessResultCacheService;
use App\Service\ElementDefragmentizeService;
use App\Service\ElementFragmentizeService;
use App\Service\ElementManager;
use App\Service\ElementTypeService;
use App\Service\GraphStructureService;
use App\Service\Mphf\ElementTypeOverlayService;
use App\Service\PendingSearchDocumentService;
use App\Tests\UnitTests\AssertLoggerTrait;
use App\Type\AccessType;
use App\Type\FragmentGroup;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\Uuid;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use stdClass;
use Syndesi\CypherDataStructures\Contract\NodeInterface as CypherNodeInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticDataStructures\Type\Document as ElasticDocument;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

/**
 * Covers only the part of {@see ElementManager::flush()} added to tolerate an element whose MongoDB or
 * Elasticsearch document is missing when it is deleted or updated (merged); the rest of {@see ElementManager} is
 * covered end-to-end by the feature tests instead, as it is mostly a thin pass-through to its collaborators.
 */
#[Small]
#[CoversClass(ElementManager::class)]
class ElementManagerFlushConsistencyTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    /**
     * @var ObjectProphecy<MongoEntityManager>
     */
    private ObjectProphecy $mongoEntityManager;
    /**
     * @var ObjectProphecy<ElasticEntityManager>
     */
    private ObjectProphecy $elasticEntityManager;
    /**
     * @var ObjectProphecy<CypherEntityManager>
     */
    private ObjectProphecy $cypherEntityManager;
    /**
     * @var ObjectProphecy<ElementFragmentizeService>
     */
    private ObjectProphecy $elementFragmentizeService;
    /**
     * @var ObjectProphecy<ElementTypeService>
     */
    private ObjectProphecy $elementTypeService;
    /**
     * @var ObjectProphecy<ElementTypeOverlayService>
     */
    private ObjectProphecy $elementTypeOverlayService;
    /**
     * @var ObjectProphecy<GraphStructureService>
     */
    private ObjectProphecy $graphStructureService;
    private TestLogger $logger;
    private PendingSearchDocumentService $pendingSearchDocumentService;
    private AccessResultCacheService $accessResultCacheService;
    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private ObjectProphecy $eventDispatcher;
    private ElementManager $elementManager;

    protected function setUp(): void
    {
        $this->mongoEntityManager = $this->prophesize(MongoEntityManager::class);
        $this->elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $this->cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $this->elementFragmentizeService = $this->prophesize(ElementFragmentizeService::class);
        $this->elementTypeService = $this->prophesize(ElementTypeService::class);
        $this->elementTypeService->resolve(Argument::any())->willReturn(null);
        $this->elementTypeService->forget(Argument::any())->will(static fn () => null);
        $this->elementTypeOverlayService = $this->prophesize(ElementTypeOverlayService::class);
        $this->graphStructureService = $this->prophesize(GraphStructureService::class);
        $this->logger = TestLogger::create();
        $this->pendingSearchDocumentService = new PendingSearchDocumentService();
        $this->accessResultCacheService = new AccessResultCacheService();
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->eventDispatcher->dispatch(Argument::any())->willReturnArgument(0);

        // these are fluent methods on concrete (non-interface) classes, so Prophecy needs an explicit return value
        $this->cypherEntityManager->create(Argument::cetera())->willReturn($this->cypherEntityManager->reveal());
        $this->cypherEntityManager->merge(Argument::cetera())->willReturn($this->cypherEntityManager->reveal());
        $this->cypherEntityManager->delete(Argument::cetera())->willReturn($this->cypherEntityManager->reveal());
        $this->mongoEntityManager->create(Argument::cetera())->willReturn($this->mongoEntityManager->reveal());
        $this->mongoEntityManager->merge(Argument::cetera())->willReturn($this->mongoEntityManager->reveal());
        $this->mongoEntityManager->delete(Argument::cetera())->willReturn($this->mongoEntityManager->reveal());
        $this->elasticEntityManager->create(Argument::cetera())->willReturn($this->elasticEntityManager->reveal());
        $this->elasticEntityManager->merge(Argument::cetera())->willReturn($this->elasticEntityManager->reveal());
        $this->elasticEntityManager->delete(Argument::cetera())->willReturn($this->elasticEntityManager->reveal());
        $this->cypherEntityManager->flush()->willReturn($this->cypherEntityManager->reveal());
        $this->mongoEntityManager->flush()->willReturn($this->mongoEntityManager->reveal());
        $this->elasticEntityManager->flush()->willReturn($this->elasticEntityManager->reveal());

        $this->elementManager = new ElementManager(
            $this->cypherEntityManager->reveal(),
            $this->mongoEntityManager->reveal(),
            $this->elasticEntityManager->reveal(),
            $this->elementFragmentizeService->reveal(),
            $this->prophesize(ElementDefragmentizeService::class)->reveal(),
            $this->prophesize(Neo4jClientHelper::class)->reveal(),
            $this->eventDispatcher->reveal(),
            $this->logger,
            $this->elementTypeService->reveal(),
            $this->elementTypeOverlayService->reveal(),
            $this->graphStructureService->reveal(),
            $this->pendingSearchDocumentService,
            $this->accessResultCacheService,
            $this->prophesize(Client404NotFoundExceptionFactory::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    private function buildElement(): NodeElementInterface
    {
        return (new NodeElement())->setId(Uuid::generate())->setLabel('Data');
    }

    private function stubFragmentize(NodeElementInterface $element): FragmentGroup
    {
        $mongoFragment = (new MongoDocument())->setCollection('Data')->setIdentifier($element->getId()->toString());
        $elasticFragment = (new ElasticDocument())->setIndex('node_data')->setIdentifier($element->getId()->toString());
        $fragmentGroup = new FragmentGroup(
            $this->prophesize(CypherNodeInterface::class)->reveal(),
            $mongoFragment,
            $elasticFragment,
        );
        $this->elementFragmentizeService->fragmentize($element)->willReturn($fragmentGroup);

        return $fragmentGroup;
    }

    public function testDeleteWithBothDocumentsPresentDeletesBothAndLogsNothing(): void
    {
        $element = $this->buildElement();
        $fragmentGroup = $this->stubFragmentize($element);
        $this->mongoEntityManager->getOneByIdentifier('Data', $element->getId()->toString())->willReturn(new MongoDocument());
        $this->elasticEntityManager->getOneByIdentifier('node_data', $element->getId()->toString())->willReturn(new ElasticDocument());

        $this->elementManager->delete($element);
        $this->elementManager->flush();

        $this->mongoEntityManager->delete($fragmentGroup->getMongoFragment())->shouldHaveBeenCalledOnce();
        $this->elasticEntityManager->delete($fragmentGroup->getElasticFragment())->shouldHaveBeenCalledOnce();
        $this->assertCount(0, $this->logger->records->all());
    }

    public function testDeleteWithoutMongoDocumentStillDeletesFromMongoAndLogsNotice(): void
    {
        $element = $this->buildElement();
        $this->stubFragmentize($element);
        $this->mongoEntityManager->getOneByIdentifier('Data', $element->getId()->toString())->willReturn(null);
        $this->elasticEntityManager->getOneByIdentifier('node_data', $element->getId()->toString())->willReturn(new ElasticDocument());

        $this->elementManager->delete($element);
        $this->elementManager->flush();

        $this->mongoEntityManager->delete(Argument::any())->shouldHaveBeenCalledOnce();
        $this->assertLogHappened(
            $this->logger,
            'notice',
            sprintf("To be deleted element with id '%s' does not have a MongoDB document.", $element->getId()->toString())
        );
    }

    public function testDeleteWithoutElasticsearchDocumentSkipsTheDeleteCallAndLogsNotice(): void
    {
        $element = $this->buildElement();
        $this->stubFragmentize($element);
        $this->mongoEntityManager->getOneByIdentifier('Data', $element->getId()->toString())->willReturn(new MongoDocument());
        $this->elasticEntityManager->getOneByIdentifier('node_data', $element->getId()->toString())->willReturn(null);

        $this->elementManager->delete($element);
        $this->elementManager->flush();

        $this->elasticEntityManager->delete(Argument::any())->shouldNotHaveBeenCalled();
        $this->assertLogHappened(
            $this->logger,
            'notice',
            sprintf("To be deleted element with id '%s' does not have an Elasticsearch document.", $element->getId()->toString())
        );
    }

    public function testMergeWithBothDocumentsPresentMergesBothAndLogsNothing(): void
    {
        $element = $this->buildElement();
        $fragmentGroup = $this->stubFragmentize($element);
        $this->mongoEntityManager->getOneByIdentifier('Data', $element->getId()->toString())->willReturn(new MongoDocument());
        $this->elasticEntityManager->getOneByIdentifier('node_data', $element->getId()->toString())->willReturn(new ElasticDocument());

        $this->elementManager->merge($element);
        $this->elementManager->flush();

        $this->mongoEntityManager->merge($fragmentGroup->getMongoFragment())->shouldHaveBeenCalledOnce();
        $this->elasticEntityManager->merge($fragmentGroup->getElasticFragment())->shouldHaveBeenCalledOnce();
        $this->assertCount(0, $this->logger->records->all());
    }

    public function testMergeWithoutMongoDocumentStillMergesIntoMongoAndLogsNotice(): void
    {
        $element = $this->buildElement();
        $fragmentGroup = $this->stubFragmentize($element);
        $this->mongoEntityManager->getOneByIdentifier('Data', $element->getId()->toString())->willReturn(null);
        $this->elasticEntityManager->getOneByIdentifier('node_data', $element->getId()->toString())->willReturn(new ElasticDocument());

        $this->elementManager->merge($element);
        $this->elementManager->flush();

        $this->mongoEntityManager->merge($fragmentGroup->getMongoFragment())->shouldHaveBeenCalledOnce();
        $this->assertLogHappened(
            $this->logger,
            'notice',
            sprintf("To be updated element with id '%s' does not have a MongoDB document.", $element->getId()->toString())
        );
    }

    public function testMergeWithoutElasticsearchDocumentStillMergesIntoElasticsearchAndLogsNotice(): void
    {
        $element = $this->buildElement();
        $fragmentGroup = $this->stubFragmentize($element);
        $this->mongoEntityManager->getOneByIdentifier('Data', $element->getId()->toString())->willReturn(new MongoDocument());
        $this->elasticEntityManager->getOneByIdentifier('node_data', $element->getId()->toString())->willReturn(null);

        $this->elementManager->merge($element);
        $this->elementManager->flush();

        $this->elasticEntityManager->merge($fragmentGroup->getElasticFragment())->shouldHaveBeenCalledOnce();
        $this->assertLogHappened(
            $this->logger,
            'notice',
            sprintf("To be updated element with id '%s' does not have an Elasticsearch document.", $element->getId()->toString())
        );
    }

    public function testCreateRegistersTheLabelInTheOverlayBeforeWritingAndForgetsItAfterwards(): void
    {
        $element = $this->buildElement();
        $order = [];
        $this->elementTypeOverlayService->register($element->getId()->toString(), 'n:Data')->will(function () use (&$order): void {
            $order[] = 'register';
        })->shouldBeCalledOnce();
        $fragmentGroup = $this->stubFragmentize($element);
        $this->elementFragmentizeService->fragmentize($element)->will(function () use (&$order, $fragmentGroup) {
            $order[] = 'fragmentize';

            return $fragmentGroup;
        });
        $this->elementTypeService->forget(Argument::that(fn (Uuid $id) => $id->equals($element->getId())))->will(function () use (&$order): void {
            $order[] = 'forget';
        });

        $this->elementManager->create($element);
        $this->elementManager->flush();

        $this->assertSame(['register', 'forget', 'fragmentize', 'forget'], $order);
    }

    public function testCreateNotesTheTypeBeforeWritingAndAppliesTheStructureInvalidationAfterwards(): void
    {
        $element = $this->buildElement();
        $order = [];
        $this->graphStructureService->noteType(true, 'Data')->will(function () use (&$order): void {
            $order[] = 'note';
        })->shouldBeCalledOnce();
        $this->graphStructureService->applyPendingInvalidation()->will(function () use (&$order): void {
            $order[] = 'apply';
        })->shouldBeCalledOnce();
        $fragmentGroup = $this->stubFragmentize($element);
        $this->elementFragmentizeService->fragmentize($element)->will(function () use (&$order, $fragmentGroup) {
            $order[] = 'fragmentize';

            return $fragmentGroup;
        });
        $this->cypherEntityManager->flush()->will(function () use (&$order) {
            $order[] = 'flush';

            return $this;
        });

        $this->elementManager->create($element);
        $this->elementManager->flush();

        $this->assertSame(['note', 'fragmentize', 'flush', 'apply', 'flush'], $order);
    }

    public function testCreateOfRelationRegistersItsType(): void
    {
        $relation = (new RelationElement())->setId(Uuid::generate())->setType('OWNS');
        $fragmentGroup = new FragmentGroup(
            $this->prophesize(CypherNodeInterface::class)->reveal(),
            (new MongoDocument())->setCollection('OWNS')->setIdentifier($relation->getId()->toString()),
            (new ElasticDocument())->setIndex('relation')->setIdentifier($relation->getId()->toString()),
        );
        $this->elementFragmentizeService->fragmentize($relation)->willReturn($fragmentGroup);

        $this->elementManager->create($relation);
        $this->elementManager->flush();

        $this->elementTypeOverlayService->register($relation->getId()->toString(), 'r:OWNS')->shouldHaveBeenCalledOnce();
    }

    public function testMergeAlwaysRegistersTheTypeBecauseTheLookupTableAnswersForUnknownIds(): void
    {
        $unknown = $this->buildElement();
        $known = $this->buildElement();
        $this->stubFragmentize($unknown);
        $this->stubFragmentize($known);
        foreach ([$unknown, $known] as $element) {
            $this->mongoEntityManager->getOneByIdentifier('Data', $element->getId()->toString())->willReturn(new MongoDocument());
            $this->elasticEntityManager->getOneByIdentifier('node_data', $element->getId()->toString())->willReturn(new ElasticDocument());
        }

        $this->elementManager->merge($unknown);
        $this->elementManager->merge($known);
        $this->elementManager->flush();

        $this->elementTypeOverlayService->register($unknown->getId()->toString(), 'n:Data')->shouldHaveBeenCalledOnce();
        $this->elementTypeOverlayService->register($known->getId()->toString(), 'n:Data')->shouldHaveBeenCalledOnce();
    }

    public function testDeleteForgetsTheCachedTypeAfterFlushButNeverRegisters(): void
    {
        $element = $this->buildElement();
        $this->stubFragmentize($element);
        $this->mongoEntityManager->getOneByIdentifier('Data', $element->getId()->toString())->willReturn(new MongoDocument());
        $this->elasticEntityManager->getOneByIdentifier('node_data', $element->getId()->toString())->willReturn(new ElasticDocument());

        $this->elementManager->delete($element);
        $this->elementManager->flush();

        $this->elementTypeService->forget(Argument::that(fn (Uuid $id) => $id->equals($element->getId())))->shouldHaveBeenCalledOnce();
        $this->elementTypeOverlayService->register(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testCreatedElementDocumentIsWrittenOnceAfterPostCreateEventsWithTheAccessAddedByListeners(): void
    {
        $element = $this->buildElement();
        $fragmentGroup = $this->stubFragmentize($element);
        $id = $element->getId()->toString();
        $order = [];
        $written = [];
        $pending = $this->pendingSearchDocumentService;
        $this->eventDispatcher->dispatch(Argument::type(ElementPostCreateEvent::class))->will(function () use (&$order, $pending, $id) {
            $order[] = 'post-create-event';
            $pending->get($id)?->addProperties(['_usersWithSearchAccess' => ['user-1']]);

            return new stdClass();
        });
        $this->elasticEntityManager->create(Argument::any())->will(function (array $args) use (&$order, &$written) {
            $order[] = 'elastic-create';
            $written[] = [$args[0]->getIdentifier(), $args[0]->getProperty('_usersWithSearchAccess')];

            return $this->reveal();
        });

        $this->elementManager->create($element);
        $this->elementManager->flush();

        $this->assertSame(['post-create-event', 'elastic-create'], $order);
        $this->assertSame([[$id, ['user-1']]], $written);
        $this->assertSame([], $this->pendingSearchDocumentService->releaseAll());
        $this->assertNotNull($fragmentGroup);
    }

    public function testCreatedElementDocumentIsStillWrittenIfNoListenerAddsAnything(): void
    {
        $element = $this->buildElement();
        $this->stubFragmentize($element);
        $this->eventDispatcher->dispatch(Argument::any())->willReturn(new stdClass());

        $this->elementManager->create($element);
        $this->elementManager->flush();

        $this->elasticEntityManager->create(Argument::that(fn (ElasticDocument $d) => $d->getIdentifier() === $element->getId()->toString()))->shouldHaveBeenCalledOnce();
    }

    public function testFlushForgetsRememberedAccessBeforeTheFirstWriteAndAfterWriting(): void
    {
        $element = $this->buildElement();
        $this->stubFragmentize($element);
        $this->eventDispatcher->dispatch(Argument::any())->willReturn(new stdClass());
        $user = Uuid::generate();
        $target = Uuid::generate();
        $cache = $this->accessResultCacheService;
        $cache->set($user, $target, AccessType::READ, true);
        $seenAtFirstWrite = null;
        $this->cypherEntityManager->create(Argument::cetera())->will(function () use ($cache, $user, $target, &$seenAtFirstWrite) {
            $seenAtFirstWrite = $cache->get($user, $target, AccessType::READ);
            // an answer remembered while the writes are going on must not survive them
            $cache->set($user, $target, AccessType::READ, false);

            return $this->reveal();
        });

        $this->elementManager->create($element);
        $this->elementManager->flush();

        $this->assertNull($seenAtFirstWrite);
        $this->assertNull($cache->get($user, $target, AccessType::READ));
    }
}
