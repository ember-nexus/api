<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\AuditFindings;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use Predis\Client as RedisClient;

/**
 * Audit findings "someone without access can trigger the edit lock" and "login response times reveal registered
 * e-mail addresses".
 */
class LockAndTimingTest extends BaseRequestTestCase
{
    use AuditHelperTrait;

    private const string STRANGER_TOKEN = 'secret-token:msp7o1b2OPxDwr2hkmgkjx';
    private const string DOCUMENT = '02IiNr0GOset2CfWts3mseByuFM8L56chPCS8';

    /**
     * Uses the reference dataset scenario `security.accessGranting`, the stranger has no access to the document.
     */
    public function testCallerWithoutAccessDoesNotLearnThatAnElementIsBeingEdited(): void
    {
        $this->assertSame(404, $this->runPatchRequest('/'.self::DOCUMENT, self::STRANGER_TOKEN, ['name' => 'x'])->getStatusCode(), 'precondition: no access looks like a missing element');

        // a request of the owner holds the lock of the element while it is being processed
        $redis = new RedisClient($_ENV['REDIS_AUTH']);
        $lockKey = (string) new RedisKey(RedisPrefixType::LOCK_ELEMENT_REQUEST, self::DOCUMENT);
        $redis->set($lockKey, 'held-by-a-request-of-the-owner', 'PX', 30000);
        try {
            $patch = $this->runPatchRequest('/'.self::DOCUMENT, self::STRANGER_TOKEN, ['name' => 'x']);
            $delete = $this->runDeleteRequest('/'.self::DOCUMENT, self::STRANGER_TOKEN);
        } finally {
            $redis->del([$lockKey]);
        }

        $this->assertSame(404, $patch->getStatusCode(), 'a caller without access reaches the element lock: 409 instead of 404 (existence and activity oracle)');
        $this->assertSame(404, $delete->getStatusCode());
    }

    public function testUnknownAndKnownUserTakeTheSameTimeToLogin(): void
    {
        $knownEmail = 'victim@searchSideChannel.security.localhost.dev'; // reference dataset, password 1234

        $time = function (string $email): float {
            $samples = [];
            for ($i = 0; $i < 7; ++$i) {
                $start = hrtime(true);
                $response = $this->runPostRequest('/token', null, [
                    'type' => 'Token',
                    'uniqueUserIdentifier' => $email,
                    'password' => 'wrong-password',
                ]);
                $samples[] = (hrtime(true) - $start) / 1e6;
                $this->assertSame(401, $response->getStatusCode());
            }
            sort($samples);

            return $samples[3];
        };

        $time($knownEmail); // warm up
        $known = $time($knownEmail);
        $unknown = $time('nobody-'.bin2hex(random_bytes(6)).'@localhost.dev');

        $this->assertLessThan(
            1.5,
            $known / max($unknown, 0.001),
            sprintf('a wrong password for a registered address takes %.1f ms, an unknown address %.1f ms: registered addresses can be enumerated', $known, $unknown)
        );
    }
}
