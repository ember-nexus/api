<?php

declare(strict_types=1);

namespace App\Tests\ServerTests;

use PHPUnit\Framework\Attributes\Group;

/**
 * The threads of the web server have to be linked with a large stack (`-z stack-size` in docker/api/Dockerfile): musl's
 * default of 128 KiB lets PHP fail with "Maximum call stack size ... reached during compilation" as soon as a thread
 * has to compile a large file, e.g. while it warms the cache of the application. xcaddy ignores that linker flag
 * without any error if its build flags are not quoted correctly, therefore the limit is verified in a running server.
 *
 * Uses the script `public/server-test-stack-depth.php`, which compiles an expression of the given depth inside a PHP
 * thread of the web server.
 */
#[Group('server-php-failure')]
class StackSizeTest extends BaseServerTestCase
{
    /**
     * Fails with 128 KiB (a depth of 500 is already too much), while 8 MiB can compile several times this depth.
     */
    private const int DEPTH_WHICH_NEEDS_A_LARGE_STACK = 10_000;

    public function testPhpThreadsCanCompileDeeplyNestedExpressions(): void
    {
        $response = $this->runGetRequest(sprintf('/server-test-stack-depth.php?depth=%d', self::DEPTH_WHICH_NEEDS_A_LARGE_STACK), null);

        $body = (string) $response->getBody();
        $this->assertStringNotContainsString('Maximum call stack size', $body, 'The thread stack of the web server is too small, the binary was probably built without `-z stack-size`.');
        $this->assertSame(200, $response->getStatusCode(), $body);
        $this->assertJson($body);
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        $this->assertSame(self::DEPTH_WHICH_NEEDS_A_LARGE_STACK, $decoded['depth']);
        $this->assertSame(self::DEPTH_WHICH_NEEDS_A_LARGE_STACK + 1, $decoded['length']);
    }
}
