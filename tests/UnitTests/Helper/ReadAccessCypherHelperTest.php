<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Helper;

use App\Helper\ReadAccessCypherHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ReadAccessCypherHelper::class)]
class ReadAccessCypherHelperTest extends TestCase
{
    public function testReadPathMatchWalksFromTheGivenElementUpToTheUser(): void
    {
        $cypher = ReadAccessCypherHelper::readPathMatch('child');

        $this->assertStringContainsString('OPTIONAL MATCH path=(child)<-[:OWNS|HAS_READ_ACCESS*0..]-()<-[:IS_IN_GROUP*0..]-(user)', $cypher);
        $this->assertStringContainsString('user.id = child.id', $cypher);
        $this->assertStringContainsString('relation.onLabel IN labels(child)', $cypher);
        $this->assertStringContainsString('relation.onParentLabel IN labels(child)', $cypher);
        $this->assertStringContainsString('(child)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})', $cypher);
        $this->assertStringContainsString('child.createdBy = user.id', $cypher);
        $this->assertStringNotContainsString('element', $cypher);
    }

    public function testReadPathMatchEndsWithANewlineSoQueriesCanContinueDirectly(): void
    {
        $this->assertStringEndsWith("\n", ReadAccessCypherHelper::readPathMatch('element'));
    }

    public function testUnconditionalPathOnlyAcceptsRelationsWithoutConditions(): void
    {
        $cypher = ReadAccessCypherHelper::unconditionalReadPathMatch('parent');

        $this->assertStringContainsString('OPTIONAL MATCH path=(parent)<-[:OWNS|HAS_READ_ACCESS*0..]-()<-[:IS_IN_GROUP*0..]-(user)', $cypher);
        foreach (['onLabel', 'onParentLabel', 'onState', 'onCreatedByUser'] as $property) {
            $this->assertStringContainsString(sprintf('relation.%s IS NULL', $property), $cypher);
        }
        $this->assertStringNotContainsString('labels(', $cypher);
        $this->assertStringNotContainsString('user.id =', $cypher);
        $this->assertStringEndsWith("\n", $cypher);
    }

    public function testDirectReadRelationConditionOnlyRestrictsReadAccessRelations(): void
    {
        $cypher = ReadAccessCypherHelper::directReadRelationCondition('rel', 'node');

        $this->assertStringContainsString('type(rel) <> "HAS_READ_ACCESS"', $cypher);
        $this->assertStringContainsString('rel.onLabel IN labels(node)', $cypher);
        $this->assertStringContainsString('rel.onParentLabel IN labels(node)', $cypher);
        $this->assertStringContainsString('(node)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: rel.onState})', $cypher);
        $this->assertStringContainsString('node.createdBy = user.id', $cypher);
    }

    public function testDirectReadRelationConditionUsesOnlyTheGivenVariables(): void
    {
        $cypher = ReadAccessCypherHelper::directReadRelationCondition('rel', 'node');

        $this->assertStringNotContainsString('relation', $cypher);
        $this->assertStringNotContainsString('element', $cypher);
    }

    public function testDirectReadRelationConditionIsBalancedAndEndsWithANewline(): void
    {
        $cypher = ReadAccessCypherHelper::directReadRelationCondition('relation', 'element');

        $this->assertSame(substr_count($cypher, '('), substr_count($cypher, ')'));
        $this->assertStringEndsWith("\n", $cypher);
    }
}
