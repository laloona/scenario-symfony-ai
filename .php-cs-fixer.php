<?php declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$header = <<<'HEADER'
This file is part of Stateforge\Scenario\SymfonyAi package.

(c) Christina Koenig <christina.koenig@looriva.de>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,

        'declare_strict_types' => true,
        'header_comment' => [
            'comment_type' => 'comment',
            'header'       => $header,
            'location'     => 'after_declare_strict',
            'separate'     => 'both',
        ],

        'linebreak_after_opening_tag' => false,
        'blank_line_after_opening_tag' => false,

        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,

        'final_class' => true,
        'protected_to_private' => true,
        'no_unneeded_final_method' => true,
        'self_accessor' => true,
        'strict_comparison' => true,
        'strict_param' => true,

        'native_function_invocation' => [
            'include' => ['@all'],
        ],
        'native_constant_invocation' => [
            'include' => ['@all'],
        ],

        'ternary_to_null_coalescing' => true,
        'visibility_required' => ['elements' => ['method', 'property']],

        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'blank_line_between_import_groups' => false,
        'single_line_after_imports' => true,
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_functions' => true,
            'import_constants' => true,
            'import_classes' => true,
        ],

        'no_superfluous_phpdoc_tags' => true,
        'no_empty_phpdoc' => true,

        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters', 'match']],
        'no_extra_blank_lines' => ['tokens' => ['extra']],
        'new_with_parentheses' => true, // needed for php 8.2
    ])
    ->setFinder($finder);