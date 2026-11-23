<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\AccessResultCacheService;
use App\Type\AccessType;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(AccessResultCacheService::class)]
class AccessResultCacheServiceTest extends TestCase
{
    public function testUnknownTripleIsNull(): void
    {
        $this->assertNull((new AccessResultCacheService())->get(Uuid::generate(), Uuid::generate(), AccessType::READ));
    }

    public function testGrantsAndDenialsAreRemembered(): void
    {
        $cache = new AccessResultCacheService();
        $user = Uuid::generate();
        $granted = Uuid::generate();
        $denied = Uuid::generate();
        $cache->set($user, $granted, AccessType::READ, true);
        $cache->set($user, $denied, AccessType::READ, false);

        $this->assertTrue($cache->get($user, $granted, AccessType::READ));
        $this->assertFalse($cache->get($user, $denied, AccessType::READ));
    }

    public function testUserElementAndAccessTypeAreAllPartOfTheKey(): void
    {
        $cache = new AccessResultCacheService();
        $user = Uuid::generate();
        $element = Uuid::generate();
        $cache->set($user, $element, AccessType::READ, true);

        $this->assertNull($cache->get($user, $element, AccessType::UPDATE));
        $this->assertNull($cache->get($user, Uuid::generate(), AccessType::READ));
        $this->assertNull($cache->get(Uuid::generate(), $element, AccessType::READ));
    }

    public function testForgetAndResetDropEverything(): void
    {
        $cache = new AccessResultCacheService();
        $user = Uuid::generate();
        $element = Uuid::generate();

        $cache->set($user, $element, AccessType::READ, true);
        $cache->forget();
        $this->assertNull($cache->get($user, $element, AccessType::READ));

        $cache->set($user, $element, AccessType::READ, true);
        $cache->reset();
        $this->assertNull($cache->get($user, $element, AccessType::READ));
    }
}
