<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\OrderedImportsFixer;

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->append([__FILE__])
;

return (new PhpCsFixer\Config())
    ->setUsingCache(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_indentation' => true,
        'compact_nullable_typehint' => true,
        'declare_strict_types' => true,
        'heredoc_to_nowdoc' => true,
        'list_syntax' => ['syntax' => 'short'],
        'no_null_property_initialization' => true,
        'no_superfluous_phpdoc_tags' => true,
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_imports' => [
            'imports_order' => [
                OrderedImportsFixer::IMPORT_TYPE_CONST,
                OrderedImportsFixer::IMPORT_TYPE_FUNCTION,
                OrderedImportsFixer::IMPORT_TYPE_CLASS,
            ],
        ],
        'pow_to_exponentiation' => true,
        'single_line_throw' => false,
        'ternary_to_null_coalescing' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
