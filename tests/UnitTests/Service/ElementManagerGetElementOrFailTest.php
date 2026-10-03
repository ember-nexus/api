<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Exception\Client404NotFoundException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\Neo4jClientHelper;
use App\Service\ElementDefragmentizeService;
use App\Service\ElementFragmentizeService;
use App\Service\ElementManager;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

#[Small]
#[CoversClass(ElementManager::class)]
class ElementManagerGetElementOrFailTest extends TestCase
{
    use ProphecyTrait;

    private const string ID = '224a787e-3b32-4822-8697-61047175505d';

    private Client404NotFoundException $notFoundException;

    protected function setUp(): void
    {
        $this->notFoundException = new Client404NotFoundException('some type');
    }

    private function buildElementManager(): ElementManager
    {
        $notFoundFactory = $this->prophesize(Client404NotFoundExceptionFactory::class);
        $notFoundFactory->createFromTemplate(Argument::cetera())->willReturn($this->notFoundException);

        $arguments = [
            $this->prophesize(CypherEntityManager::class)->reveal(),
            $this->prophesize(MongoEntityManager::class)->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $this->prophesize(ElementFragmentizeService::class)->reveal(),
            $this->prophesize(ElementDefragmentizeService::class)->reveal(),
            $this->prophesize(Neo4jClientHelper::class)->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            $notFoundFactory->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        ];

        return new class(...$arguments) extends ElementManager {
            public ?NodeElementInterface $node = null;
            public ?RelationElementInterface $relation = null;

            public function getNode(UuidInterface $id): ?NodeElementInterface
            {
                return $this->node;
            }

            public function getRelation(UuidInterface $id): ?RelationElementInterface
            {
                return $this->relation;
            }
        };
    }

    public function testReturnsNodeIfItExists(): void
    {
        $node = new NodeElement();
        $elementManager = $this->buildElementManager();
        $elementManager->node = $node;

        $this->assertSame($node, $elementManager->getElementOrFail(Uuid::fromString(self::ID)));
    }

    public function testReturnsRelationIfNoNodeExists(): void
    {
        $relation = new RelationElement();
        $elementManager = $this->buildElementManager();
        $elementManager->relation = $relation;

        $this->assertSame($relation, $elementManager->getElementOrFail(Uuid::fromString(self::ID)));
    }

    public function testThrowsNotFoundIfNeitherNodeNorRelationExists(): void
    {
        $elementManager = $this->buildElementManager();

        $this->expectExceptionObject($this->notFoundException);

        $elementManager->getElementOrFail(Uuid::fromString(self::ID));
    }

    public function testGetElementReturnsNullIfNothingExists(): void
    {
        $elementManager = $this->buildElementManager();

        $this->assertNull($elementManager->getElement(Uuid::fromString(self::ID)));
    }
}
