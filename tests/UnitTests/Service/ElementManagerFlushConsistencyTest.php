<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\Neo4jClientHelper;
use App\Service\ElementDefragmentizeService;
use App\Service\ElementFragmentizeService;
use App\Service\ElementManager;
use App\Tests\UnitTests\AssertLoggerTrait;
use App\Type\FragmentGroup;
use App\Type\NodeElement;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Rfc4122\UuidV4;
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
    private TestLogger $logger;
    private ElementManager $elementManager;

    protected function setUp(): void
    {
        $this->mongoEntityManager = $this->prophesize(MongoEntityManager::class);
        $this->elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $this->cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $this->elementFragmentizeService = $this->prophesize(ElementFragmentizeService::class);
        $this->logger = TestLogger::create();

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
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            $this->prophesize(Client404NotFoundExceptionFactory::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
            $this->logger,
        );
    }

    private function buildElement(): NodeElementInterface
    {
        return (new NodeElement())->setId(UuidV4::uuid4())->setLabel('Data');
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
}
