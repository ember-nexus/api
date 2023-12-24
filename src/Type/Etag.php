<?php

namespace App\Type;

use DateTimeInterface;
use HashContext;
use Ramsey\Uuid\UuidInterface;
use Tuupola\Base58;

use function Safe\pack;
use function Safe\unpack;

class Etag
{
    private const string HASH_ALGORITHM = 'xxh3';

    private HashContext $hashContext;
    private Base58 $encoder;

    public function __construct(
        string $seed,
        string $algorithm = self::HASH_ALGORITHM
    ) {
        $this->hashContext = hash_init($algorithm);
        hash_update($this->hashContext, $seed);
        $this->encoder = new Base58();
    }

    public function addDatetime(DateTimeInterface $dateTime): self
    {
        $timestamp = $dateTime->getTimestamp();
        $timestampAsBinaryString = pack('C*', ...array_reverse(unpack('C*', pack('L', $timestamp))));
        hash_update($this->hashContext, $timestampAsBinaryString);

        return $this;
    }

    public function addUuid(UuidInterface $uuid): self
    {
        hash_update($this->hashContext, $uuid->getBytes());

        return $this;
    }

    public function getEtag(): string
    {
        $rawHash = hash_final($this->hashContext, true);

        return $this->encoder->encode($rawHash);
    }
}
