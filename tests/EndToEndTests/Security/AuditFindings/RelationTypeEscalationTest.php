<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\AuditFindings;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Relations which grant access (`OWNS`, `IS_IN_GROUP`, `HAS_<ACCESS>_ACCESS`) may only be created through `POST /` by
 * users who hold that access on the target themselves. Before, read access to the target (and create access to the
 * start node, which everybody has on the nodes they own) was enough to become its owner.
 *
 * Uses the reference dataset scenario `security.accessGranting`: the owner owns the document and the group, the reader
 * may read both, the delegate may read and update the document; reader and delegate own one node each.
 */
class RelationTypeEscalationTest extends BaseRequestTestCase
{
    use AuditHelperTrait;

    private const string OWNER_TOKEN = 'secret-token:VrWluB7WlZCuKEaTYP37LJ';
    private const string READER_TOKEN = 'secret-token:LhScLTzSYm0MiaYQeSaUJa';
    private const string DELEGATE_TOKEN = 'secret-token:3Afb0Pa5exeTA52rCVSjtb';
    private const string READER = '02IiNr0GOset2CeQGkTVUIDocRXXRlStI1KIP';
    private const string DOCUMENT = '02IiNr0GOset2CfWts3mseByuFM8L56chPCS8';
    private const string GROUP = '02IiNr0GOset2CfweEcg0suDVgdj7opxlTMW5';
    private const string READER_NODE = '02IiNr0GOset2CeYUC4A7VgvtGdnqLumtT95U';
    private const string DELEGATE_NODE = '02IiNr0GOset2Cg4HoWyh0sn1SZeowEyQNOeJ';

    /**
     * @return iterable<string, array{string}>
     */
    public static function accessGrantingRelationTypesAReaderMayNotCreate(): iterable
    {
        // HAS_READ_ACCESS is missing on purpose: whoever may read an element may pass on read access
        yield 'OWNS' => ['OWNS'];
        yield 'HAS_UPDATE_ACCESS' => ['HAS_UPDATE_ACCESS'];
        yield 'HAS_DELETE_ACCESS' => ['HAS_DELETE_ACCESS'];
        yield 'HAS_CREATE_ACCESS' => ['HAS_CREATE_ACCESS'];
        yield 'HAS_SEARCH_ACCESS' => ['HAS_SEARCH_ACCESS'];
        yield 'IS_IN_GROUP' => ['IS_IN_GROUP'];
    }

    #[DataProvider('accessGrantingRelationTypesAReaderMayNotCreate')]
    public function testReaderCanNotPlantAccessRelationOnReadableElement(string $relationType): void
    {
        $this->assertSame(200, $this->runGetRequest('/'.self::DOCUMENT, self::READER_TOKEN)->getStatusCode(), 'precondition: reader can read');
        $this->assertContains($this->runPatchRequest('/'.self::DOCUMENT, self::READER_TOKEN, ['name' => 'x'])->getStatusCode(), [403, 404], 'precondition: reader can not update');

        $response = $this->runPostRequest('/', self::READER_TOKEN, [
            'type' => $relationType,
            'start' => self::READER_NODE,
            'end' => self::DOCUMENT,
        ]);

        $this->assertSame(403, $response->getStatusCode(), sprintf('%s relation to a merely readable element was accepted', $relationType));
        $this->assertSame(0, $this->countRelationsInGraph($relationType, self::READER_NODE, self::DOCUMENT), 'the relation was written to the graph');
    }

    public function testReaderCanNotBecomeOwnerOfReadableElement(): void
    {
        $this->runPostRequest('/', self::READER_TOKEN, ['type' => 'OWNS', 'start' => self::READER_NODE, 'end' => self::DOCUMENT]);

        $this->assertContains($this->runPatchRequest('/'.self::DOCUMENT, self::READER_TOKEN, ['name' => 'taken over'])->getStatusCode(), [403, 404], 'reader became able to update the document');
        $this->assertContains($this->runDeleteRequest('/'.self::DOCUMENT, self::READER_TOKEN)->getStatusCode(), [403, 404], 'reader became able to delete the document');
        $this->assertSame(200, $this->runGetRequest('/'.self::DOCUMENT, self::OWNER_TOKEN)->getStatusCode());
    }

    public function testReaderCanNotJoinGroupByCreatingMembershipRelation(): void
    {
        $response = $this->runPostRequest('/', self::READER_TOKEN, ['type' => 'IS_IN_GROUP', 'start' => self::READER, 'end' => self::GROUP]);

        $this->assertSame(403, $response->getStatusCode(), 'reader was able to add themselves to a group');
        $this->assertSame(0, $this->countRelationsInGraph('IS_IN_GROUP', self::READER, self::GROUP));
    }

    public function testOwnerCanStillGrantAccessToAGroup(): void
    {
        $response = $this->runPostRequest('/', self::OWNER_TOKEN, ['type' => 'HAS_READ_ACCESS', 'start' => self::GROUP, 'end' => self::DOCUMENT]);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testReaderCanPassOnReadAccess(): void
    {
        $response = $this->runPostRequest('/', self::READER_TOKEN, ['type' => 'HAS_READ_ACCESS', 'start' => self::READER_NODE, 'end' => self::DOCUMENT]);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testUserCanOnlyGrantTheAccessTheyHold(): void
    {
        $post = fn (string $type): int => $this->runPostRequest('/', self::DELEGATE_TOKEN, ['type' => $type, 'start' => self::DELEGATE_NODE, 'end' => self::DOCUMENT])->getStatusCode();

        $this->assertSame(403, $post('HAS_DELETE_ACCESS'));
        $this->assertSame(403, $post('OWNS'));
        $this->assertSame(403, $post('IS_IN_GROUP'));
        $this->assertSame(201, $post('HAS_UPDATE_ACCESS'));
        $this->assertSame(201, $post('HAS_READ_ACCESS'));
    }

    public function testOrdinaryRelationsStillNeedOnlyReadAccessToTheTarget(): void
    {
        $response = $this->runPostRequest('/', self::READER_TOKEN, ['type' => 'REFERENCES', 'start' => self::READER_NODE, 'end' => self::DOCUMENT]);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testRelationsWithAnUnknownAccessNameAreOrdinaryRelations(): void
    {
        $response = $this->runPostRequest('/', self::READER_TOKEN, ['type' => 'HAS_FOO_ACCESS', 'start' => self::READER_NODE, 'end' => self::DOCUMENT]);

        $this->assertSame(201, $response->getStatusCode());
    }
}
