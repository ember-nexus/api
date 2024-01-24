<?php

namespace App\Type;

use DateTimeInterface;
use HashContext;
use LogicException;
use Ramsey\Uuid\UuidInterface;
use Tuupola\Base58;

use function Safe\pack;
use function Safe\unpack;

class EtagCalculator
{
    private const string HASH_ALGORITHM = 'xxh3';

    private HashContext $hashContext;
    private Base58 $encoder;
    private ?Etag $finalEtag = null;

    public function __construct(
        string $seed,
        string $algorithm = self::HASH_ALGORITHM
    ) {
        $this->hashContext = hash_init($algorithm);
        hash_update($this->hashContext, $seed);
        $this->encoder = new Base58();
    }

    public function addDateTime(DateTimeInterface $dateTime): self
    {
        if ($this->finalEtag) {
            throw new LogicException('Etag is already finalized, no new data can be added.');
        }
        $timestamp = (int) $dateTime->format('Uu');
        $timestampAsBinaryString = pack('C*', ...array_reverse(unpack('C*', pack('L', $timestamp))));
        hash_update($this->hashContext, $timestampAsBinaryString);

        return $this;
    }

    public function addUuid(UuidInterface $uuid): self
    {
        if ($this->finalEtag) {
            throw new LogicException('Etag is already finalized, no new data can be added.');
        }
        hash_update($this->hashContext, $uuid->getBytes());

        return $this;
    }

    public function getEtag(): Etag
    {
        if ($this->finalEtag) {
            return $this->finalEtag;
        }
        $rawHash = hash_final($this->hashContext, true);
        $this->finalEtag = new Etag($this->encoder->encode($rawHash));

        return $this->finalEtag;
    }
}
