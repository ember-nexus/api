<?php

declare(strict_types=1);

namespace App\Tests\ServerTests;

use PHPUnit\Framework\Attributes\Group;

/**
 * Started with `memory_limit = 3M`, see tests/ServerTests/ini/low-memory: PHP fails before the application, i.e.
 * Symfony's error handler, can answer. The web server (`intercept` in docker/Caddyfile) replaces the empty answer.
 */
#[Group('server-php-boot-failure')]
class PhpBootFailureTest extends BaseServerTestCase
{
    public function testFailureOutsideOfTheApplicationIsAnsweredWithProblemJson(): void
    {
        $response = $this->runGetRequest('/', $this->getToken());

        $this->assertSame(500, $response->getStatusCode());
        $this->assertResponseMatchesDocumentation('php-boot-failure', $response);
        $this->assertStringNotContainsStringIgnoringCase('memory', (string) $response->getBody());
    }
}
