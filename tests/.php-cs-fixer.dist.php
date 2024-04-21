<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/../')
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'phpdoc_to_comment' => false,
        'declare_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true
        ],
        'self_accessor' => true
    ])
    ->setFinder($finder)
;
