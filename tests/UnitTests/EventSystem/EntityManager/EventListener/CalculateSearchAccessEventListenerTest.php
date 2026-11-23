<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementUpdateAfterBackupLoadEvent;
use App\EventSystem\EntityManager\EventListener\CalculateSearchAccessEventListener;
use App\Service\AppStateService;
use App\Service\PendingSearchDocumentService;
use App\Service\SearchAccessCalculatorService;
use App\Type\AppStateType;
use App\Type\NodeElement;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Syndesi\ElasticDataStructures\Type\Document;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

#[Small]
#[CoversClass(CalculateSearchAccessEventListener::class)]
class CalculateSearchAccessEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private const array ACCESS = ['groups' => ['g1'], 'users' => ['u1']];

    private function buildListener(PendingSearchDocumentService $pending, $elastic, AppStateType $state = AppStateType::DEFAULT): CalculateSearchAccessEventListener
    {
        $appState = $this->prophesize(AppStateService::class);
        $appState->getAppState()->willReturn($state);
        $calculator = $this->prophesize(SearchAccessCalculatorService::class);
        $calculator->calculateSearchAccess(Argument::any())->willReturn(self::ACCESS);
        $calculator->getIndexForElement(Argument::any())->willReturn('node_data');

        return new CalculateSearchAccessEventListener($appState->reveal(), $elastic->reveal(), $calculator->reveal(), $pending);
    }

    private function buildElement(): NodeElement
    {
        return (new NodeElement())->setId(Uuid::generate())->setLabel('Data');
    }

    public function testAccessIsAddedToThePendingDocumentOfAnElementWhichIsCreatedRightNow(): void
    {
        $element = $this->buildElement();
        $pending = new PendingSearchDocumentService();
        $document = (new Document())->setIndex('node_data')->setIdentifier($element->getId()->toString());
        $pending->hold($element->getId()->toString(), $document);
        $elastic = $this->prophesize(ElasticEntityManager::class);

        $this->buildListener($pending, $elastic)->onElementPostCreate(new ElementPostCreateEvent($element));

        $this->assertSame(['g1'], $document->getProperty('_groupsWithSearchAccess'));
        $this->assertSame(['u1'], $document->getProperty('_usersWithSearchAccess'));
        $elastic->merge(Argument::any())->shouldNotHaveBeenCalled();
    }

    public function testAccessIsMergedIntoElasticsearchIfThereIsNoPendingDocument(): void
    {
        $element = $this->buildElement();
        $elastic = $this->prophesize(ElasticEntityManager::class);
        $elastic->merge(Argument::that(
            fn (Document $d) => $d->getIdentifier() === $element->getId()->toString()
                && 'node_data' === $d->getIndex()
                && ['g1'] === $d->getProperty('_groupsWithSearchAccess')
                && ['u1'] === $d->getProperty('_usersWithSearchAccess')
        ))->willReturn($elastic->reveal())->shouldBeCalledOnce();

        $this->buildListener(new PendingSearchDocumentService(), $elastic)->onElementUpdateAfterBackupLoadEvent(new ElementUpdateAfterBackupLoadEvent($element));
    }

    public function testNothingIsCalculatedWhileLoadingABackup(): void
    {
        $element = $this->buildElement();
        $elastic = $this->prophesize(ElasticEntityManager::class);

        $this->buildListener(new PendingSearchDocumentService(), $elastic, AppStateType::LOADING_BACKUP)->onElementPostCreate(new ElementPostCreateEvent($element));

        $elastic->merge(Argument::any())->shouldNotHaveBeenCalled();
    }
}
