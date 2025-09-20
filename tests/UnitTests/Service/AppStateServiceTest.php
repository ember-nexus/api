<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Service;

use App\Service\AppStateService;
use App\Type\AppStateType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(AppStateService::class)]
class AppStateServiceTest extends TestCase
{
    public function testAppStateService(): void
    {
        $appStateService = new AppStateService();

        $this->assertSame(AppStateType::DEFAULT, $appStateService->getAppState());
        $res = $appStateService->setAppState(AppStateType::LOADING_BACKUP);
        $this->assertSame(AppStateType::LOADING_BACKUP, $appStateService->getAppState());
        $this->assertSame($appStateService, $res);
    }
}
