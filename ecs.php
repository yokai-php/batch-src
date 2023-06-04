<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\PHP\ForbiddenFunctionsSniff;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return function (ECSConfig $ecsConfig): void {
    $ecsConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests/convention',
        __DIR__ . '/tests/integration',
        __DIR__ . '/tests/symfony/src',
        __DIR__ . '/tests/symfony/tests',
    ]);

    $ecsConfig->sets([
        SetList::NAMESPACES,
        SetList::COMMENTS,
        SetList::STRICT,
        SetList::PSR_12,
    ]);

    $ecsConfig->ruleWithConfiguration(ForbiddenFunctionsSniff::class, [
        'forbiddenFunctions' => ['dump' => null, 'dd' => null, 'var_dump' => null, 'die' => null],
    ]);
    $ecsConfig->ruleWithConfiguration(FunctionDeclarationFixer::class, [
        'closure_fn_spacing' => 'none',
    ]);
};
