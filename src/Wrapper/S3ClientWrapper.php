<?php

declare(strict_types=1);

namespace App\Wrapper;

use AsyncAws\S3\Result\ObjectExistsWaiter;

/**
 * Call to `isSuccess()` can not be mocked, as relevant internal methods are final. See existing issue on GitHub:
 * https://github.com/async-aws/aws/issues/1306
 * @codeCoverageIgnore
 */
class S3ClientWrapper
{
    public function getIsSuccessFromObjectExistsWaiter(ObjectExistsWaiter $objectExistsWaiter): bool
    {
        return $objectExistsWaiter->isSuccess();
    }
}
