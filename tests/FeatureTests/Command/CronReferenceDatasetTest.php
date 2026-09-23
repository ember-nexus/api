<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Command;

use PHPUnit\Framework\Attributes\Group;

/**
 * The cron jobs must never remove data of the reference dataset, e.g. because of timestamps in the past: the
 * dataset holds no uploads and no element other than uploads carries an `expires` property in the past. Explicitly
 * expired data used by other tests has to be created by these tests themselves.
 */
#[Group('command')]
class CronReferenceDatasetTest extends BaseCronTestCase
{
    /**
     * @return array<string, int>
     */
    private function getCounts(): array
    {
        $client = $this->getCypherClient();
        $counts = [];
        foreach ([
            'nodes without uploads' => 'MATCH (n) WHERE NOT n:Upload RETURN count(n) AS count',
            'relations' => 'MATCH ()-[r]->() RETURN count(r) AS count',
            'tokens' => 'MATCH (t:Token) RETURN count(t) AS count',
            'users' => 'MATCH (u:User) RETURN count(u) AS count',
            'elements with a file' => 'MATCH (n) WHERE n.hasFile = true RETURN count(n) AS count',
        ] as $name => $query) {
            $counts[$name] = $client->run($query)->first()->get('count');
        }

        return $counts;
    }

    public function testNoElementOtherThanUploadsHasAnExpirationInThePast(): void
    {
        $result = $this->getCypherClient()->run(
            'MATCH (n) WHERE NOT n:Upload AND n.expires IS NOT NULL AND n.expires < datetime() RETURN count(n) AS count'
        );

        $this->assertSame(0, $result->first()->get('count'));
    }

    public function testCronCommandKeepsAllDataWhichIsNotAnExpiredUpload(): void
    {
        $countsBefore = $this->getCounts();
        $filesBefore = $this->runGetRequest('/0fdd52ba-55da-430c-b015-3277a231e895/file', self::TOKEN);
        $this->assertSame(200, $filesBefore->getStatusCode());

        [$exitCode, $output] = $this->runConsoleCommand('cron');

        $this->assertSame(0, $exitCode, $output);
        $this->assertSame($countsBefore, $this->getCounts());
        $filesAfter = $this->runGetRequest('/0fdd52ba-55da-430c-b015-3277a231e895/file', self::TOKEN);
        $this->assertSame(200, $filesAfter->getStatusCode());
        $this->assertSame((string) $filesBefore->getBody(), (string) $filesAfter->getBody());
    }
}
