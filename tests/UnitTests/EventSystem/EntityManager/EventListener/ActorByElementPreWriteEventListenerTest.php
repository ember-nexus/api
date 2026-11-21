<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPreCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPreMergeEvent;
use App\EventSystem\EntityManager\EventListener\CreatedByElementPreWriteEventListener;
use App\EventSystem\EntityManager\EventListener\UpdatedByElementPreWriteEventListener;
use App\Security\AuthProvider;
use App\Service\AppStateService;
use App\Type\AppStateType;
use App\Type\NodeElement;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(CreatedByElementPreWriteEventListener::class)]
#[CoversClass(UpdatedByElementPreWriteEventListener::class)]
class ActorByElementPreWriteEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private const string ACTOR = '02IiNr0G8viOVKk0kQOokUeQle4I04rVnXsw9';
    private const string OTHER = '02IiNr0G8viOVKk93ZnAQKjaaGuTdNF728q5W';

    private function authProvider(): AuthProvider
    {
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn(Uuid::fromString(self::ACTOR));

        return $authProvider->reveal();
    }

    private function appState(AppStateType $state): AppStateService
    {
        $appState = $this->prophesize(AppStateService::class);
        $appState->getAppState()->willReturn($state);

        return $appState->reveal();
    }

    private function node(): NodeElement
    {
        return (new NodeElement())->setId(Uuid::generate())->setLabel('Data');
    }

    public function testCreatedByIsSetToActingUserOnCreate(): void
    {
        $node = $this->node();
        (new CreatedByElementPreWriteEventListener($this->authProvider(), $this->appState(AppStateType::DEFAULT)))
            ->onElementPreCreateEvent(new ElementPreCreateEvent($node));
        $this->assertSame(self::ACTOR, $node->getProperty('createdBy'));
    }

    public function testCreatedByPresetValueIsKept(): void
    {
        $node = $this->node()->addProperty('createdBy', self::OTHER);
        (new CreatedByElementPreWriteEventListener($this->authProvider(), $this->appState(AppStateType::DEFAULT)))
            ->onElementPreCreateEvent(new ElementPreCreateEvent($node));
        $this->assertSame(self::OTHER, $node->getProperty('createdBy'));
    }

    public function testCreatedByIsNotInventedWhileLoadingBackup(): void
    {
        $node = $this->node();
        (new CreatedByElementPreWriteEventListener($this->authProvider(), $this->appState(AppStateType::LOADING_BACKUP)))
            ->onElementPreCreateEvent(new ElementPreCreateEvent($node));
        $this->assertFalse($node->hasProperty('createdBy'));
    }

    public function testCreatedByIsNeverChangedOnMerge(): void
    {
        // the listener does not even subscribe to merges
        $this->assertFalse(method_exists(CreatedByElementPreWriteEventListener::class, 'onElementPreMergeEvent'));
    }

    public function testUpdatedByIsSetOnCreate(): void
    {
        $node = $this->node();
        (new UpdatedByElementPreWriteEventListener($this->authProvider(), $this->appState(AppStateType::DEFAULT)))
            ->onElementPreCreateEvent(new ElementPreCreateEvent($node));
        $this->assertSame(self::ACTOR, $node->getProperty('updatedBy'));
    }

    public function testUpdatedByPresetValueIsKeptOnCreate(): void
    {
        $node = $this->node()->addProperty('updatedBy', self::OTHER);
        (new UpdatedByElementPreWriteEventListener($this->authProvider(), $this->appState(AppStateType::DEFAULT)))
            ->onElementPreCreateEvent(new ElementPreCreateEvent($node));
        $this->assertSame(self::OTHER, $node->getProperty('updatedBy'));
    }

    public function testUpdatedByIsOverwrittenOnMerge(): void
    {
        $node = $this->node()->addProperty('updatedBy', self::OTHER);
        (new UpdatedByElementPreWriteEventListener($this->authProvider(), $this->appState(AppStateType::DEFAULT)))
            ->onElementPreMergeEvent(new ElementPreMergeEvent($node));
        $this->assertSame(self::ACTOR, $node->getProperty('updatedBy'));
    }

    public function testUpdatedByIsKeptOnMergeWhileLoadingBackup(): void
    {
        $node = $this->node()->addProperty('updatedBy', self::OTHER);
        (new UpdatedByElementPreWriteEventListener($this->authProvider(), $this->appState(AppStateType::LOADING_BACKUP)))
            ->onElementPreMergeEvent(new ElementPreMergeEvent($node));
        $this->assertSame(self::OTHER, $node->getProperty('updatedBy'));
    }
}
