<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\AuditFindings;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The element type (node label / relation type) is written into the id -> type lookup file by `mphf:build`, whose
 * reader accepts 1..255 bytes without `;` and control characters. A user who can create an element with any other type
 * would make every following build fail (a persistent denial of service for the lookup refresh), so such types have to
 * be rejected when the element is created. Empty names and names with backticks currently end in an HTTP 500.
 */
class TypeNameValidationTest extends BaseRequestTestCase
{
    use AuditHelperTrait;

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTypes(): iterable
    {
        yield 'semicolon' => ['a;b'];
        yield 'line feed' => ["a\nb"];
        yield 'carriage return' => ["a\rb"];
        yield 'nul byte' => ["a\0b"];
        yield 'longer than 128 characters' => [str_repeat('x', 129)];
        yield 'empty' => [''];
        yield 'backtick' => ['a`b'];
        yield 'space' => ['a b'];
        yield 'dash' => ['a-b'];
        yield 'leading digit' => ['1abc'];
        yield 'leading underscore' => ['_abc'];
        yield 'trailing newline' => ["Abc\n"];
        yield 'umlaut' => ['Dätä'];
        yield 'reserved-like injection' => ['A) DETACH DELETE (n'];
    }

    #[DataProvider('invalidTypes')]
    public function testNodeWithUnusableTypeIsRejected(string $type): void
    {
        $user = $this->registerUser('type-name');

        $response = $this->runPostRequest('/', $user['token'], ['type' => $type, 'data' => ['name' => 'type name probe']]);

        // keep the shared development database usable for `mphf:build`, whatever the response was
        $this->getCypherClient()->run('MATCH (n {name: "type name probe"}) DETACH DELETE n');
        $this->assertSame(400, $response->getStatusCode(), 'the type is accepted or fails with a server error');
    }

    #[DataProvider('invalidTypes')]
    public function testRelationWithUnusableTypeIsRejected(string $type): void
    {
        $user = $this->registerUser('type-name-rel');
        $a = $this->createNodeAs($user['token']);
        $b = $this->createNodeAs($user['token']);

        $response = $this->runPostRequest('/', $user['token'], ['type' => $type, 'start' => $a, 'end' => $b]);

        $this->assertSame(400, $response->getStatusCode());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validTypes(): iterable
    {
        yield 'simple' => ['Data'];
        yield 'with underscore and digits' => ['Some_Type_2'];
        yield 'single letter' => ['A'];
        yield 'longest' => [str_repeat('a', 128)];
    }

    #[DataProvider('validTypes')]
    public function testValidTypesAreAccepted(string $type): void
    {
        $user = $this->registerUser('type-name-ok');

        $response = $this->runPostRequest('/', $user['token'], ['type' => $type, 'data' => ['name' => 'type name probe']]);

        $this->getCypherClient()->run('MATCH (n {name: "type name probe"}) DETACH DELETE n');
        $this->assertSame(201, $response->getStatusCode());
    }
}
