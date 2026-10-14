<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use MongoDB\Client as MongoClient;

/**
 * `ElementManager::flush()` tolerates an element whose MongoDB or Elasticsearch document is missing (e.g. after a
 * partial failure elsewhere, or data fixed up directly in Neo4j): a delete no longer throws trying to remove a
 * document that was never there, and a write (PUT/PATCH) recreates it instead of failing. Uses the dedicated
 * `general.consistency` scenario of the reference dataset for the elements under test, and only deletes the one
 * store's document under test directly, bypassing the application, to set up the inconsistency. The scenario's
 * elements are not used by any other test, so manipulating them directly is safe even under ParaTest's parallel
 * execution.
 */
class ElementStoreConsistencyTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function getMongoClient(): MongoClient
    {
        return new MongoClient($_ENV['MONGO_AUTH']);
    }

    private function getElasticsearchClient(): ElasticsearchClient
    {
        return ElasticsearchClientBuilder::create()->setHosts([$_ENV['ELASTIC_AUTH']])->build();
    }

    private function deleteMongoDocument(string $collection, string $id): void
    {
        $result = $this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection($collection)->deleteOne(['_id' => $id]);
        $this->assertSame(1, $result->getDeletedCount());
    }

    private function deleteElasticsearchDocument(string $index, string $id): void
    {
        $this->getElasticsearchClient()->delete(['index' => $index, 'id' => $id]);
    }

    public function testDeletingNodeWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('Data', 'effd07e6-cd38-4933-b451-ff8ff4090663');

        $response = $this->runDeleteRequest('/effd07e6-cd38-4933-b451-ff8ff4090663', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testDeletingRelationWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('RELATED', '24fe920f-314d-4f08-ae76-e3d7bcca3aae');

        $response = $this->runDeleteRequest('/24fe920f-314d-4f08-ae76-e3d7bcca3aae', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testDeletingNodeWithoutElasticsearchDocumentSucceeds(): void
    {
        $this->deleteElasticsearchDocument('node_data', '8c59a3e4-0a28-4b8b-8c1f-a3c9b2912205');

        $response = $this->runDeleteRequest('/8c59a3e4-0a28-4b8b-8c1f-a3c9b2912205', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testDeletingRelationWithoutElasticsearchDocumentSucceeds(): void
    {
        $this->deleteElasticsearchDocument('relation_related', '49d7f006-1505-4dfa-a411-06ac3dc51f35');

        $response = $this->runDeleteRequest('/49d7f006-1505-4dfa-a411-06ac3dc51f35', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    /**
     * `node/general/consistency/deleteMissingS3File/data.json` declares `hasFile: true` and `file` metadata but has
     * no corresponding object under the backup's `file/` directory, so it was never uploaded to S3 in the first
     * place - no corruption step is needed to set this one up.
     */
    public function testDeletingNodeWithoutS3FileSucceeds(): void
    {
        $response = $this->runDeleteRequest('/fed71d17-0ea6-416f-a3d8-f053b1a41217', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testDeletingRelationWithoutS3FileSucceeds(): void
    {
        $response = $this->runDeleteRequest('/80ba487c-8f57-4ebd-aae7-5ccb1aaf7572', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testPatchingNodeWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('Data', '1b1872c4-aeb6-4eb4-8a73-bad312a076d3');

        $response = $this->runPatchRequest('/1b1872c4-aeb6-4eb4-8a73-bad312a076d3', self::TOKEN, ['name' => 'patched without mongo document']);

        $this->assertNoContentResponse($response);
        $this->assertNotNull($this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection('Data')->findOne(['_id' => '1b1872c4-aeb6-4eb4-8a73-bad312a076d3']));
    }

    public function testPatchingRelationWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('RELATED', '61c44476-2aa7-4d13-9456-3ca58142a91f');

        $response = $this->runPatchRequest('/61c44476-2aa7-4d13-9456-3ca58142a91f', self::TOKEN, ['name' => 'patched without mongo document']);

        $this->assertNoContentResponse($response);
        $this->assertNotNull($this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection('RELATED')->findOne(['_id' => '61c44476-2aa7-4d13-9456-3ca58142a91f']));
    }

    public function testPuttingNodeWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('Data', '1b1872c4-aeb6-4eb4-8a73-bad312a076d3');

        $response = $this->runPutRequest('/1b1872c4-aeb6-4eb4-8a73-bad312a076d3', self::TOKEN, ['name' => 'put without mongo document']);

        $this->assertNoContentResponse($response);
        $this->assertNotNull($this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection('Data')->findOne(['_id' => '1b1872c4-aeb6-4eb4-8a73-bad312a076d3']));
    }

    public function testPuttingRelationWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('RELATED', '61c44476-2aa7-4d13-9456-3ca58142a91f');

        $response = $this->runPutRequest('/61c44476-2aa7-4d13-9456-3ca58142a91f', self::TOKEN, ['name' => 'put without mongo document']);

        $this->assertNoContentResponse($response);
        $this->assertNotNull($this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection('RELATED')->findOne(['_id' => '61c44476-2aa7-4d13-9456-3ca58142a91f']));
    }

    public function testPatchingNodeWithoutElasticsearchDocumentSucceeds(): void
    {
        $this->deleteElasticsearchDocument('node_data', '13af9a84-b879-4ef7-9e6c-7e556f3730db');

        $response = $this->runPatchRequest('/13af9a84-b879-4ef7-9e6c-7e556f3730db', self::TOKEN, ['name' => 'patched without elasticsearch document']);

        $this->assertNoContentResponse($response);
        $this->assertTrue($this->getElasticsearchClient()->exists(['index' => 'node_data', 'id' => '13af9a84-b879-4ef7-9e6c-7e556f3730db'])->asBool());
    }

    public function testPatchingRelationWithoutElasticsearchDocumentSucceeds(): void
    {
        $this->deleteElasticsearchDocument('relation_related', '05bd9faf-beb9-4e11-bed5-956d05f830ca');

        $response = $this->runPatchRequest('/05bd9faf-beb9-4e11-bed5-956d05f830ca', self::TOKEN, ['name' => 'patched without elasticsearch document']);

        $this->assertNoContentResponse($response);
        $this->assertTrue($this->getElasticsearchClient()->exists(['index' => 'relation_related', 'id' => '05bd9faf-beb9-4e11-bed5-956d05f830ca'])->asBool());
    }
}
