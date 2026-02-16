<?php

declare(strict_types=1);

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodeQuality\Rector\Ternary\TernaryEmptyArrayArrayDimFetchToCoalesceRector;
use Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Privatization\Rector\Class_\FinalizeTestCaseClassRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Renaming\Rector\ConstFetch\RenameConstantRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPhpSets(php82: true)
    ->withPreparedSets(deadCode: true, instanceOf: true, phpunitCodeQuality: true)
    ->withParallel(120, 8, 10)
    ->withCache(
        // Github action cache or local
        is_dir('/tmp') ? '/tmp/rector' : null,
        FileCacheStorage::class,
    )
    // paths to refactor; solid alternative to CLI arguments
    ->withPaths([
        __DIR__ . '/spec',
        __DIR__ . '/src',
    ])
    // do you need to include constants, class aliases or custom autoloader? files listed will be executed
    ->withBootstrapFiles([
        __DIR__ . '/spec/bootstrap.php',
    ])
    ->withPHPStanConfigs([
        __DIR__ . '/phpstan.neon.dist',
        __DIR__ . '/vendor/phpstan/phpstan-strict-rules/rules.neon',
    ])
    // is there a file you need to skip?
    ->withSkip([
        __DIR__ . '/src/Debug/Toolbar/Views/toolbar.tpl.php',
        __DIR__ . '/spec/support/application/app/Views',

        RemoveUnusedPrivateMethodRector::class,

        RemoveUnusedConstructorParamRector::class => [
            // there are deprecated parameters
            // __DIR__ . '/system/Debug/Exceptions.php',
            // @TODO remove if deprecated $httpVerb is removed
            __DIR__ . '/src/Router/AutoRouter.php',
        ],

        // Exclude test file because `is_cli()` is mocked and Rector might remove needed parameters.
        RemoveExtraParametersRector::class,

        // check on constant compare
        UnwrapFutureCompatibleIfPhpVersionRector::class,

        DeclareStrictTypesRector::class,

        // use mt_rand instead of random_int on purpose on non-cryptographically random
        RandomFunctionRector::class,

        ClassPropertyAssignToConstructorPromotionRector::class,

        ReturnNeverTypeRector::class => [
            __DIR__ . '/src/Router/Dispatcher.php',
            __DIR__ . '/src/Helpers/kint.php',
        ],

        // Unnecessary (string) is inserted
        NullToStrictStringFuncCallArgRector::class,

        CompactToVariablesRector::class,

        // possibly isset() on purpose, on updated Config classes property accross versions
        IssetOnPropertyObjectToPropertyExistsRector::class,

        // some tests extended by other tests
        FinalizeTestCaseClassRector::class,

        RemoveNullArgOnNullDefaultParamRector::class,

        StaticCallOnNonStaticToInstanceCallRector::class,
    ])
    // auto import fully qualified class names
    ->withImportNames(removeUnusedImports: true)
    ->withRules([
        DeclareStrictTypesRector::class,
        SimplifyUselessVariableRector::class,
        RemoveAlwaysElseRector::class,
        CountArrayToEmptyArrayComparisonRector::class,
        ChangeNestedForeachIfsToEarlyContinueRector::class,
        ChangeIfElseValueAssignToEarlyReturnRector::class,
        PreparedValueToEarlyReturnRector::class,
        FuncGetArgsToVariadicParamRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        TernaryEmptyArrayArrayDimFetchToCoalesceRector::class,
        DisallowedEmptyRuleFixerRector::class,
        PrivatizeFinalClassPropertyRector::class,
        VersionCompareFuncCallToConstantRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
        AddMethodCallBasedStrictParamTypeRector::class,
        TypedPropertyFromAssignsRector::class,
        ClosureReturnTypeRector::class,
        AddArrowFunctionReturnTypeRector::class,
    ])
    ->withConfiguredRule(RenameConstantRector::class, [
        'FILTER_DEFAULT' => 'FILTER_UNSAFE_RAW',
    ])
    ->withCodeQualityLevel(61);
