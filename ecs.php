<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use PhpCsFixer\Fixer\Basic\BracesPositionFixer;
use PhpCsFixer\Fixer\Basic\SingleLineEmptyBodyFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\FinalClassFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\LanguageConstruct\NullableTypeDeclarationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocNoAliasTagFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitSetUpTearDownVisibilityFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestAnnotationFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use PhpCsFixer\Fixer\StringNotation\ExplicitStringVariableFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use Symplify\CodingStandard\Fixer\Annotation\RemoveMethodNameDuplicateDescriptionFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

$composer = \json_decode(\file_get_contents(__DIR__.'/composer.json'), true);

return ECSConfig::configure()
    ->withPaths(\array_map(
        fn(string $path) => __DIR__ . '/' . $path,
        [
            ...\array_values($composer['autoload']['psr-4']),
            ...\array_values($composer['autoload-dev']['psr-4']),
        ]
    ))
    ->withPreparedSets(
        perCs: true,
        arrays: true,
        comments: true,
        docblocks: true,
        namespaces: true,
    )
    ->withRules([
        DeclareStrictTypesFixer::class,
        FinalClassFixer::class,
        PhpUnitSetUpTearDownVisibilityFixer::class,
        PhpUnitTestAnnotationFixer::class,
        StrictComparisonFixer::class,
        StrictParamFixer::class,
    ])
    ->withConfiguredRule(BlankLineBeforeStatementFixer::class, [
        'statements' => ['case', 'continue', 'declare', 'default', 'return', 'throw', 'try'],
    ])
    ->withConfiguredRule(BracesPositionFixer::class, [
        // same as PER-CS 2.0 in PHP-CS-Fixer
        'allow_single_line_anonymous_functions' => true,
        'allow_single_line_empty_anonymous_classes' => true,
    ])
    ->withConfiguredRule(CastSpacesFixer::class, [
        'space' => 'none',
    ])
    ->withConfiguredRule(ForbiddenFunctionsSniff::class, [
        'forbiddenFunctions' => ['dump' => null, 'dd' => null, 'var_dump' => null, 'die' => null],
    ])
    ->withConfiguredRule(FunctionDeclarationFixer::class, [
        'closure_fn_spacing' => 'none',
    ])
    ->withConfiguredRule(LineLengthFixer::class, [
        LineLengthFixer::INLINE_SHORT_LINES => false,
    ])
    ->withConfiguredRule(NativeFunctionInvocationFixer::class, [
        'scope' => 'namespaced',
        'include' => ['@all'],
    ])
    ->withConfiguredRule(NullableTypeDeclarationFixer::class, [
        'syntax' => 'union',
    ])
    ->withConfiguredRule(YodaStyleFixer::class, [
        'equal' => false,
        'identical' => false,
        'less_and_greater' => false,
    ])
    ->withConfiguredRule(ClassDefinitionFixer::class, [
        'inline_constructor_arguments' => false,
        'space_before_parenthesis' => false,
    ])
    ->withSkip([
        SingleLineEmptyBodyFixer::class,
        ArrayListItemNewlineFixer::class,
        ArrayOpenerAndCloserNewlineFixer::class,
        ExplicitStringVariableFixer::class,
        StandaloneLineInMultilineArrayFixer::class,
        // conflicts with conventions: public methods must have a comment, links must use @link
        PhpdocNoAliasTagFixer::class,
        RemoveMethodNameDuplicateDescriptionFixer::class,
        NativeFunctionInvocationFixer::class => [
            'src/batch-symfony-framework/src/Resources/*',
        ],
        FinalClassFixer::class => [
            'src/batch/src/Finder/CallbackFinder.php',
            'src/batch/src/Trigger/Scheduler/CallbackScheduler.php',
            'src/batch/src/Exception/RuntimeException.php',
            'src/batch/src/Exception/LogicException.php',
            'src/batch/src/Exception/InvalidArgumentException.php',
            'src/batch-symfony-console/src/CommandRunner.php',
        ],
    ])
;
