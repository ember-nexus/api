<?php

declare(strict_types=1);

namespace App\Attribute;

use Attribute;

/**
 * Marks an endpoint as conceptually unauthenticated rather than one acting as the anonymous user (e.g. `/register`,
 * `POST /token`, `/change-password`): it is exempt from the `ANONYMOUS_USER_READONLY` write restriction because
 * that restriction is about limiting what the anonymous user may do, and these endpoints are not invoked as any
 * user at all.
 *
 * @see EndpointCanBeUsedByReadonlyAnonymousUser for the other reason an endpoint may be exempt: it does write as
 * the anonymous user, but does not write anything on the anonymous user's own data (e.g. `/search`).
 */
#[Attribute]
readonly class EndpointWithoutAuthentication
{
}
