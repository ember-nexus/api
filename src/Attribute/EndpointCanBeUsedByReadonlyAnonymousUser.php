<?php

declare(strict_types=1);

namespace App\Attribute;

use Attribute;

/**
 * Marks an endpoint as exempt from the `ANONYMOUS_USER_READONLY` write restriction because, despite its HTTP
 * method, it does not write anything on behalf of the anonymous user's own data (e.g. `/search`).
 *
 * @see EndpointWithoutAuthentication for the other reason an endpoint may be exempt: it is not invoked as the
 * anonymous user (or any user) at all.
 */
#[Attribute]
readonly class EndpointCanBeUsedByReadonlyAnonymousUser
{
}
