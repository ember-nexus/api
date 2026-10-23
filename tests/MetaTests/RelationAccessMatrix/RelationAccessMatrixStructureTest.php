<?php

declare(strict_types=1);

namespace App\Tests\MetaTests\RelationAccessMatrix;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Guards the generated tests of the relation access matrix: if a test class, a pair of codes, the test method or its
 * data provider is removed or renamed, this test fails. Needs no running services; whether the reference dataset matches
 * the codes is checked by the end-to-end test RelationAccessMatrixDatasetTest.
 *
 * Codes consist of six letters in the order O C R U D S (OWNS and HAS_{CREATE,READ,UPDATE,DELETE,SEARCH}_ACCESS), X
 * means that the letter is absent.
 *
 * Which letters can grant access depends on the request and the side: the start node needs OWNS or the access of the
 * request (read, create, update, delete), the end node needs OWNS or read access. A letter which can not grant access
 * for a request is irrelevant for it. The codes are chosen so that, for every request and side,
 *
 * - each granting letter is present on its own, with all irrelevant letters present;
 * - no granting letter is present, with all irrelevant letters present;
 * - all letters are absent.
 *
 * On top of that OWNS together with every other letter and every letter without any other are tested. Every start
 * code is combined with every end code, in every structure, with one class per structure and start code.
 */
class RelationAccessMatrixStructureTest extends TestCase
{
    private const string LETTERS = 'OCRUDS';

    private const array START_CODES = [
        'XXXXXX', 'OCXUDS', 'XCRUDS', 'XCXUDS', 'OXRUDS', 'XXRUDS', 'OCRXDS', 'XCRXDS', 'OCRUXS', 'XCRUXS',
        'OCRUDS', 'OXXXXX', 'XCXXXX', 'XXRXXX', 'XXXUXX', 'XXXXDX',
    ];

    private const array END_CODES = ['XXXXXX', 'OCXUDS', 'XCRUDS', 'XCXUDS', 'OCRUDS', 'OXXXXX', 'XXRXXX'];

    /**
     * The letters which can grant the access a request needs on the start node, and the one letter of the end node.
     */
    private const array START_REQUESTS = ['read' => 'R', 'create' => 'C', 'update' => 'U', 'delete' => 'D'];

    private const array END_REQUESTS = ['all requests' => 'R'];

    private const array STRUCTURES = ['Direct', 'Path', 'Group', 'StartDirectEndGroup', 'StartGroupEndDirect'];

    private const string TESTS_DIRECTORY = __DIR__.'/../../EndToEndTests/Security/RelationAccessMatrix';

    private const string TESTS_NAMESPACE = 'App\\Tests\\EndToEndTests\\Security\\RelationAccessMatrix';

    /**
     * @return array<string, array{string}>
     */
    public static function structureProvider(): array
    {
        $structures = [];
        foreach (self::STRUCTURES as $structure) {
            $structures[$structure] = [$structure];
        }

        return $structures;
    }

    /**
     * @param string[]              $codes
     * @param array<string, string> $requests request => letter, besides OWNS, which grants access
     *
     * @return array<string, true> the cases which are covered
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function getCoveredCases(array $codes, array $requests): array
    {
        $covered = [];
        foreach ($requests as $request => $grantingLetter) {
            $irrelevantLetters = array_values(array_diff(str_split(self::LETTERS), ['O', $grantingLetter]));
            foreach ($codes as $code) {
                $hasAllIrrelevantLetters = [] === array_filter($irrelevantLetters, static fn (string $letter): bool => !str_contains($code, $letter));
                $owns = str_contains($code, 'O');
                $grants = str_contains($code, $grantingLetter);
                if ($hasAllIrrelevantLetters && $owns && !$grants) {
                    $covered[sprintf('%s: OWNS on its own', $request)] = true;
                }
                if ($hasAllIrrelevantLetters && !$owns && $grants) {
                    $covered[sprintf('%s: %s on its own', $request, $grantingLetter)] = true;
                }
                if ($hasAllIrrelevantLetters && !$owns && !$grants) {
                    $covered[sprintf('%s: no granting letter', $request)] = true;
                }
            }
            if (in_array('XXXXXX', $codes, true)) {
                $covered[sprintf('%s: all letters absent', $request)] = true;
            }
        }

        return $covered;
    }

    /**
     * @param array<string, string> $requests
     *
     * @return string[]
     */
    private function getRequiredCases(array $requests): array
    {
        $cases = [];
        foreach ($requests as $request => $grantingLetter) {
            $cases[] = sprintf('%s: OWNS on its own', $request);
            $cases[] = sprintf('%s: %s on its own', $request, $grantingLetter);
            $cases[] = sprintf('%s: no granting letter', $request);
            $cases[] = sprintf('%s: all letters absent', $request);
        }

        return $cases;
    }

    public function testCodesAreWellFormedAndUnique(): void
    {
        foreach ([self::START_CODES, self::END_CODES] as $codes) {
            $this->assertSame(count($codes), count(array_unique($codes)), 'Codes must be unique.');
            foreach ($codes as $code) {
                $this->assertMatchesRegularExpression('/^[OX][CX][RX][UX][DX][SX]$/', $code);
            }
            $this->assertContains('XXXXXX', $codes);
            $this->assertContains('OCRUDS', $codes);
        }
        // every letter which can grant access without any other letter
        foreach (['OXXXXX', 'XCXXXX', 'XXRXXX', 'XXXUXX', 'XXXXDX'] as $code) {
            $this->assertContains($code, self::START_CODES);
        }
        foreach (['OXXXXX', 'XXRXXX'] as $code) {
            $this->assertContains($code, self::END_CODES);
        }
    }

    public function testCodesCoverEveryCaseOfEveryRequest(): void
    {
        $covered = $this->getCoveredCases(self::START_CODES, self::START_REQUESTS);
        foreach ($this->getRequiredCases(self::START_REQUESTS) as $case) {
            $this->assertArrayHasKey($case, $covered, sprintf('Start codes do not cover "%s".', $case));
        }
        $covered = $this->getCoveredCases(self::END_CODES, self::END_REQUESTS);
        foreach ($this->getRequiredCases(self::END_REQUESTS) as $case) {
            $this->assertArrayHasKey($case, $covered, sprintf('End codes do not cover "%s".', $case));
        }
    }

    /**
     * @return class-string<BaseRelationAccessMatrixTestCase>
     */
    private function getClassName(string $structure, string $shortName): string
    {
        /** @var class-string<BaseRelationAccessMatrixTestCase> $className */
        $className = sprintf('%s\\%s\\%s', self::TESTS_NAMESPACE, $structure, $shortName);

        return $className;
    }

    /**
     * @return string[]
     */
    private function getExpectedClassNames(): array
    {
        return array_map(static fn (string $code): string => sprintf('StartCode%sTest', $code), self::START_CODES);
    }

    public function testNoTestClassExistsWhichIsNotExpected(): void
    {
        foreach (self::STRUCTURES as $structure) {
            $files = glob(sprintf('%s/%s/*.php', self::TESTS_DIRECTORY, $structure));
            $this->assertIsArray($files, sprintf('Unable to list the test classes of structure %s.', $structure));
            $found = array_map(static fn (string $file): string => basename($file, '.php'), $files);
            $expected = $this->getExpectedClassNames();
            sort($found);
            sort($expected);
            $this->assertSame($expected, $found, sprintf('Test classes of structure %s do not match the expected classes.', $structure));
        }
        $directories = glob(self::TESTS_DIRECTORY.'/*', GLOB_ONLYDIR);
        $this->assertIsArray($directories);
        $found = array_map('basename', $directories);
        $expected = self::STRUCTURES;
        sort($found);
        sort($expected);
        $this->assertSame($expected, $found, 'Structure folders do not match the expected structures.');
    }

    #[DataProvider('structureProvider')]
    public function testEveryClassHasTheTestMethodAndItsDataProvider(string $structure): void
    {
        foreach ($this->getExpectedClassNames() as $shortName) {
            $className = $this->getClassName($structure, $shortName);
            $this->assertTrue(class_exists($className), sprintf('Class %s does not exist.', $className));
            $reflection = new ReflectionClass($className);
            $this->assertTrue($reflection->isSubclassOf(BaseRelationAccessMatrixTestCase::class), sprintf('%s must extend the base case.', $className));
            $this->assertFalse($reflection->isAbstract(), sprintf('%s must not be abstract.', $className));

            $this->assertTrue($reflection->hasMethod('testRelationAccess'), sprintf('%s has no method testRelationAccess.', $className));
            $method = $reflection->getMethod('testRelationAccess');
            $this->assertSame($className, $method->getDeclaringClass()->getName(), sprintf('testRelationAccess of %s is not declared in the class.', $className));
            $this->assertTrue($method->isPublic());
            $this->assertSame(
                ['startCode', 'endCode', 'relationId', 'startId', 'endId'],
                array_map(static fn ($parameter): string => $parameter->getName(), $method->getParameters()),
                sprintf('Unexpected parameters of testRelationAccess in %s.', $className)
            );

            $dataProviders = $method->getAttributes(DataProvider::class);
            $this->assertCount(1, $dataProviders, sprintf('testRelationAccess of %s needs exactly one data provider.', $className));
            $this->assertSame('pairs', $dataProviders[0]->newInstance()->methodName(), sprintf('Unexpected data provider in %s.', $className));
            $this->assertTrue($reflection->hasMethod('pairs'), sprintf('%s has no data provider method pairs.', $className));
            $provider = $reflection->getMethod('pairs');
            $this->assertTrue($provider->isPublic() && $provider->isStatic(), sprintf('pairs of %s must be public static.', $className));

            // a test which no longer runs the checks would still pass
            $source = file_get_contents((string) $reflection->getFileName());
            $this->assertIsString($source);
            $this->assertStringContainsString(
                '$this->assertRelationAccess($startCode, $endCode, $relationId, $startId, $endId);',
                $source,
                sprintf('testRelationAccess of %s does not call assertRelationAccess.', $className)
            );
        }
    }

    #[DataProvider('structureProvider')]
    public function testPairsOfEveryClassAreComplete(string $structure): void
    {
        $foundPairs = [];
        foreach (self::START_CODES as $startCode) {
            $className = $this->getClassName($structure, sprintf('StartCode%sTest', $startCode));
            $expectedNames = array_map(static fn (string $endCode): string => sprintf('%s -> %s', $startCode, $endCode), self::END_CODES);
            $pairs = $className::pairs();
            $this->assertSame($expectedNames, array_keys($pairs), sprintf('%s does not test the expected pairs.', $className));
            foreach ($pairs as $name => $dataSet) {
                $this->assertCount(5, $dataSet, sprintf('Data set "%s" of %s must have 5 values.', $name, $className));
                $this->assertSame($name, sprintf('%s -> %s', $dataSet[0], $dataSet[1]), sprintf('Name of a data set of %s does not match its codes.', $className));
                $this->assertArrayNotHasKey($name, $foundPairs, sprintf('Pair "%s" of structure %s is tested more than once.', $name, $structure));
                $foundPairs[$name] = true;
            }
        }
        $this->assertCount(count(self::START_CODES) * count(self::END_CODES), $foundPairs);
    }

    public function testIdsAreValidAndUnique(): void
    {
        $relationIds = [];
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
        foreach (self::STRUCTURES as $structure) {
            foreach ($this->getExpectedClassNames() as $shortName) {
                foreach ($this->getClassName($structure, $shortName)::pairs() as $name => $dataSet) {
                    [, , $relationId, $startId, $endId] = $dataSet;
                    foreach ([$relationId, $startId, $endId] as $id) {
                        $this->assertMatchesRegularExpression($uuidPattern, $id, sprintf('Invalid id in data set "%s" of %s.', $name, $shortName));
                    }
                    $this->assertNotContains($relationId, $relationIds, sprintf('Relation id of "%s" in %s is used twice.', $name, $shortName));
                    $relationIds[] = $relationId;
                    $this->assertNotSame($startId, $endId);
                }
            }
        }
    }
}
