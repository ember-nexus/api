<?php

declare(strict_types=1);

/**
 * Only mounted into the container by tests/ServerTests/docker-compose.yml, never part of the application.
 *
 * Compiles an expression with `depth` nested operands inside a PHP thread of the web server. The compiler recurses once
 * per operand, so the depth which fits depends on the stack size of the thread: with musl's default of 128 KiB PHP
 * fails with "Maximum call stack size ... reached during compilation" (see `-z stack-size` in docker/api/Dockerfile),
 * the 8 MiB the binary is linked with handle depths far beyond what the application needs.
 */
header('Content-Type: application/json');

$depth = max(1, min(100_000, (int) ($_GET['depth'] ?? 1000)));
// a variable instead of literals, as PHP folds the concatenation of literals while parsing, without recursion
$result = eval('$a = "a"; return '.str_repeat('$a.', $depth).'$a;');

echo json_encode([
    'depth' => $depth,
    'length' => strlen($result),
    'maxAllowedStackSize' => ini_get('zend.max_allowed_stack_size'),
]);
