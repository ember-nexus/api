<?php

declare(strict_types=1);

namespace App\Factory;

use App\Contract\MphfInterface;
use App\Service\Mphf\GoMphf;
use App\Service\Mphf\NullMphf;

class MphfFactory
{
    public const string EXTENSION_FUNCTION = 'EmberNexus\Mphf\lookup';

    public function __construct(
        private bool $enabled,
    ) {
    }

    public function create(): MphfInterface
    {
        if ($this->enabled && function_exists(self::EXTENSION_FUNCTION)) {
            return new GoMphf();
        }

        return new NullMphf();
    }
}
