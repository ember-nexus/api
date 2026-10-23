<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix;

use App\Tests\EndToEndTests\BaseRequestTestCase;

/**
 * The generated tests derive the expected result from the codes of their start and end nodes, so the nodes of the
 * reference dataset have to carry exactly the access relations which their code promises. The structure of the generated
 * tests themselves is guarded by RelationAccessMatrixStructureTest in the meta tests.
 */
class RelationAccessMatrixDatasetTest extends BaseRequestTestCase
{
    private const string LETTERS = 'OCRUDS';

    private const array RELATION_TYPE_BY_LETTER = [
        'O' => 'OWNS',
        'C' => 'HAS_CREATE_ACCESS',
        'R' => 'HAS_READ_ACCESS',
        'U' => 'HAS_UPDATE_ACCESS',
        'D' => 'HAS_DELETE_ACCESS',
        'S' => 'HAS_SEARCH_ACCESS',
    ];

    /**
     * Connection kind of the start and the end nodes per structure, which also determines their origin: the user for
     * "direct", the middle node for "path" and the group for "group".
     */
    private const array STRUCTURES = [
        'Direct' => ['direct', 'direct'],
        'Path' => ['path', 'path'],
        'Group' => ['group', 'group'],
        'StartDirectEndGroup' => ['direct', 'group'],
        'StartGroupEndDirect' => ['group', 'direct'],
    ];

    private const string USER_EMAIL = 'user@relation-access-matrix.localhost.dev';

    /**
     * @return class-string<BaseRelationAccessMatrixTestCase>[]
     */
    private function getClassNames(string $structure): array
    {
        $files = glob(sprintf('%s/%s/*.php', __DIR__, $structure));
        $this->assertNotEmpty($files, sprintf('Found no test classes for structure %s.', $structure));

        /** @var class-string<BaseRelationAccessMatrixTestCase>[] $classNames */
        $classNames = array_map(
            static fn (string $file): string => sprintf('%s\\%s\\%s', __NAMESPACE__, $structure, basename($file, '.php')),
            $files
        );

        return $classNames;
    }

    /**
     * The tests derive the expected result from the codes, so the nodes of the dataset have to carry exactly the access
     * relations which their code promises, from the origin of their connection kind and from no other node.
     */
    public function testNodesCarryTheAccessOfTheirCode(): void
    {
        $client = $this->getCypherClient();
        $originRows = $client->run(
            'MATCH (user:User {email: $email})-[:OWNS]->(middle {name: "Middle node of the path structure"}) '.
            'MATCH (user)-[:IS_IN_GROUP]->(group:Group) '.
            'RETURN user.id AS user, middle.id AS middle, group.id AS `group`',
            ['email' => self::USER_EMAIL]
        );
        $this->assertCount(1, $originRows, 'Unable to find the user, the middle node and the group of the matrix scenario.');
        $originIdByKind = [
            'direct' => $originRows->first()->get('user'),
            'path' => $originRows->first()->get('middle'),
            'group' => $originRows->first()->get('group'),
        ];

        /** @var array<string, array{code: string, kind: string}> $expectedNodes */
        $expectedNodes = [];
        foreach (self::STRUCTURES as $structure => [$startKind, $endKind]) {
            foreach ($this->getClassNames($structure) as $className) {
                foreach ($className::pairs() as $dataSet) {
                    [$startCode, $endCode, , $startId, $endId] = $dataSet;
                    $this->registerNode($expectedNodes, $startId, $startCode, $startKind);
                    $this->registerNode($expectedNodes, $endId, $endCode, $endKind);
                }
            }
        }

        $typeToLetter = array_flip(self::RELATION_TYPE_BY_LETTER);
        $rows = $client->run(
            'MATCH (node) WHERE node.id IN $ids '.
            'OPTIONAL MATCH (origin)-[access]->(node) WHERE type(access) IN $types '.
            'RETURN node.id AS id, collect([origin.id, type(access)]) AS accesses',
            ['ids' => array_keys($expectedNodes), 'types' => array_values(self::RELATION_TYPE_BY_LETTER)]
        );
        $this->assertCount(count($expectedNodes), $rows, 'Not all nodes used by the matrix tests exist in the database.');
        foreach ($rows as $row) {
            $id = $row->get('id');
            $letters = [];
            foreach ($row->get('accesses') as $access) {
                if (null === $access[0]) {
                    continue;
                }
                $this->assertSame(
                    $originIdByKind[$expectedNodes[$id]['kind']],
                    $access[0],
                    sprintf('Node %s is connected to an unexpected origin.', $id)
                );
                $letters[] = $typeToLetter[$access[1]];
            }
            $actualCode = implode('', array_map(
                static fn (string $letter): string => in_array($letter, $letters, true) ? $letter : 'X',
                str_split(self::LETTERS)
            ));
            $this->assertSame($expectedNodes[$id]['code'], $actualCode, sprintf('Node %s does not carry the access of its code.', $id));
        }
    }

    /**
     * @param array<string, array{code: string, kind: string}> $nodes
     */
    private function registerNode(array &$nodes, string $id, string $code, string $kind): void
    {
        if (array_key_exists($id, $nodes)) {
            $this->assertSame(['code' => $code, 'kind' => $kind], $nodes[$id], sprintf('Node %s is used with different codes or kinds.', $id));

            return;
        }
        $nodes[$id] = ['code' => $code, 'kind' => $kind];
    }
}
