<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\RelationAccessMatrix\Direct;

use App\Tests\EndToEndTests\Security\RelationAccessMatrix\BaseRelationAccessMatrixTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Generated, structure "direct" (start nodes: direct, end nodes: direct). Every data set is
 * (start code, end code, relation id, start node id, end node id).
 */
class StartCodeXXXUXXTest extends BaseRelationAccessMatrixTestCase
{
    #[DataProvider('pairs')]
    public function testRelationAccess(string $startCode, string $endCode, string $relationId, string $startId, string $endId): void
    {
        $this->assertRelationAccess($startCode, $endCode, $relationId, $startId, $endId);
    }

    /**
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function pairs(): array
    {
        return [
            'XXXUXX -> XXXXXX' => ['XXXUXX', 'XXXXXX', '0d46676b-99ce-4287-beaa-5f211f49f7fe', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '5af4b244-a4cd-40ab-9903-078f80930168'],
            'XXXUXX -> OCXUDS' => ['XXXUXX', 'OCXUDS', '090fa4e4-9e30-4fec-bf4c-49379f035bbc', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '96240917-bccb-4dd7-8898-4659e18b4bfd'],
            'XXXUXX -> XCRUDS' => ['XXXUXX', 'XCRUDS', 'a90ce430-ea34-4bf7-9a4c-22b7c2f0ba09', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '0a222f24-0b0b-49b8-8262-3d421c05d4fb'],
            'XXXUXX -> XCXUDS' => ['XXXUXX', 'XCXUDS', '4773497b-4d29-459d-a6a1-eba52eb8e845', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', 'a3280778-7fb7-4aa9-ab38-e16423cc1a8f'],
            'XXXUXX -> OCRUDS' => ['XXXUXX', 'OCRUDS', '2f68d251-8e85-4873-b8ba-f129516318ce', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', 'b5299bbd-065c-47eb-b4a2-33eebbab9fcc'],
            'XXXUXX -> OXXXXX' => ['XXXUXX', 'OXXXXX', '8aeee707-f2fb-4508-9417-4b3bad24a895', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '98252595-7030-49e4-8563-6eaf0128797b'],
            'XXXUXX -> XXRXXX' => ['XXXUXX', 'XXRXXX', 'bf1d4ca6-56e5-4329-b4fb-21aa0bc1ee35', '55f10b8e-9bf5-4177-b8e3-ba9fad8df8a3', '9f4b51c9-169e-47fb-a7d4-90040118e915'],
        ];
    }
}
