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
        $this->deleteMongoDocument('Data', '02IiNr0GOoKiPMMzng0Nj1bjmKvTwXnZkmVy4');

        $response = $this->runDeleteRequest('/02IiNr0GOoKiPMMzng0Nj1bjmKvTwXnZkmVy4', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testDeletingRelationWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('RELATED', '02IiNr0GOoKiPMLOwndASqXc0axmIi7mCksFK');

        $response = $this->runDeleteRequest('/02IiNr0GOoKiPMLOwndASqXc0axmIi7mCksFK', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testDeletingNodeWithoutElasticsearchDocumentSucceeds(): void
    {
        $this->deleteElasticsearchDocument('node_data', '02IiNr0GOoKiPMMDHogcQYJsShA0IaXiGxVs0');

        $response = $this->runDeleteRequest('/02IiNr0GOoKiPMMDHogcQYJsShA0IaXiGxVs0', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testDeletingRelationWithoutElasticsearchDocumentSucceeds(): void
    {
        $this->deleteElasticsearchDocument('relation_related', '02IiNr0GOoKiPMLgu44YBYZWraYEB4gU8yTF1');

        $response = $this->runDeleteRequest('/02IiNr0GOoKiPMLgu44YBYZWraYEB4gU8yTF1', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    /**
     * `node/general/consistency/deleteMissingS3File/data.json` declares `hasFile: true` and `file` metadata but has
     * no corresponding object under the backup's `file/` directory, so it was never uploaded to S3 in the first
     * place - no corruption step is needed to set this one up.
     */
    public function testDeletingNodeWithoutS3FileSucceeds(): void
    {
        $response = $this->runDeleteRequest('/02IiNr0GOoKiPMN72jTV1OmXNJykilzet71ma', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testDeletingRelationWithoutS3FileSucceeds(): void
    {
        $response = $this->runDeleteRequest('/02IiNr0GOoKiPMM7cqPJzXZvaBeHjVlCENyJN', self::TOKEN);

        $this->assertIsDeletedResponse($response);
    }

    public function testPatchingNodeWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('Data', '02IiNr0GOoKiPMLK8c2rgNjMUmGC4Z8VdHnF1');

        $response = $this->runPatchRequest('/02IiNr0GOoKiPMLK8c2rgNjMUmGC4Z8VdHnF1', self::TOKEN, ['name' => 'patched without mongo document']);

        $this->assertNoContentResponse($response);
        $this->assertNotNull($this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection('Data')->findOne(['_id' => '02IiNr0GOoKiPMLK8c2rgNjMUmGC4Z8VdHnF1']));
    }

    public function testPatchingRelationWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('RELATED', '02IiNr0GOoKiPMLsYkSM9O7aj4T2Nt0i8ITZ9');

        $response = $this->runPatchRequest('/02IiNr0GOoKiPMLsYkSM9O7aj4T2Nt0i8ITZ9', self::TOKEN, ['name' => 'patched without mongo document']);

        $this->assertNoContentResponse($response);
        $this->assertNotNull($this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection('RELATED')->findOne(['_id' => '02IiNr0GOoKiPMLsYkSM9O7aj4T2Nt0i8ITZ9']));
    }

    public function testPuttingNodeWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('Data', '02IiNr0GOoKiPMLK8c2rgNjMUmGC4Z8VdHnF1');

        $response = $this->runPutRequest('/02IiNr0GOoKiPMLK8c2rgNjMUmGC4Z8VdHnF1', self::TOKEN, ['name' => 'put without mongo document']);

        $this->assertNoContentResponse($response);
        $this->assertNotNull($this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection('Data')->findOne(['_id' => '02IiNr0GOoKiPMLK8c2rgNjMUmGC4Z8VdHnF1']));
    }

    public function testPuttingRelationWithoutMongoDocumentSucceeds(): void
    {
        $this->deleteMongoDocument('RELATED', '02IiNr0GOoKiPMLsYkSM9O7aj4T2Nt0i8ITZ9');

        $response = $this->runPutRequest('/02IiNr0GOoKiPMLsYkSM9O7aj4T2Nt0i8ITZ9', self::TOKEN, ['name' => 'put without mongo document']);

        $this->assertNoContentResponse($response);
        $this->assertNotNull($this->getMongoClient()->selectDatabase('ember-nexus')->selectCollection('RELATED')->findOne(['_id' => '02IiNr0GOoKiPMLsYkSM9O7aj4T2Nt0i8ITZ9']));
    }

    public function testPatchingNodeWithoutElasticsearchDocumentSucceeds(): void
    {
        $this->deleteElasticsearchDocument('node_data', '02IiNr0GOoKiPMLGW7EDMDXlAJjoxeJAaVp1T');

        $response = $this->runPatchRequest('/02IiNr0GOoKiPMLGW7EDMDXlAJjoxeJAaVp1T', self::TOKEN, ['name' => 'patched without elasticsearch document']);

        $this->assertNoContentResponse($response);
        $this->assertTrue($this->getElasticsearchClient()->exists(['index' => 'node_data', 'id' => '02IiNr0GOoKiPMLGW7EDMDXlAJjoxeJAaVp1T'])->asBool());
    }

    public function testPatchingRelationWithoutElasticsearchDocumentSucceeds(): void
    {
        $this->deleteElasticsearchDocument('relation_related', '02IiNr0GOoKiPML9jJ2CnKlwWQ8DidmBaJbU7');

        $response = $this->runPatchRequest('/02IiNr0GOoKiPML9jJ2CnKlwWQ8DidmBaJbU7', self::TOKEN, ['name' => 'patched without elasticsearch document']);

        $this->assertNoContentResponse($response);
        $this->assertTrue($this->getElasticsearchClient()->exists(['index' => 'relation_related', 'id' => '02IiNr0GOoKiPML9jJ2CnKlwWQ8DidmBaJbU7'])->asBool());
    }
}
