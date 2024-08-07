<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodeQuality\Rector\BooleanAnd\SimplifyEmptyArrayCheckRector;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Foreach_\UnusedForeachValueToArrayKeysRector;
use Rector\CodeQuality\Rector\FuncCall\ChangeArrayPushToArrayAssignRector;
use Rector\CodeQuality\Rector\FuncCall\SimplifyStrposLowerRector;
use Rector\CodeQuality\Rector\FuncCall\SingleInArrayToCompareRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Ternary\TernaryEmptyArrayArrayDimFetchToCoalesceRector;
use Rector\CodeQuality\Rector\Ternary\UnnecessaryTernaryExpressionRector;
use Rector\CodingStyle\Rector\ClassMethod\FuncGetArgsToVariadicParamRector;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php70\Rector\FuncCall\RandomFunctionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\Strict\Rector\If_\BooleanInIfConditionRuleFixerRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Empty_\EmptyOnNullableObjectToInstanceOfRector;
use Rector\TypeDeclaration\Rector\Function_\AddFunctionVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
	->withPhpSets(php81: true)
	->withPreparedSets(deadCode: true)
	->withParallel(120, 8, 10)
    ->withCache(
        // Github action cache or local
        is_dir('/tmp') ? '/tmp/rector' : null,
        FileCacheStorage::class
    )
    // paths to refactor; solid alternative to CLI arguments
    ->withPaths([
        __DIR__ . '/spec',
        // __DIR__ . '/src',
    ])
    // do you need to include constants, class aliases or custom autoloader? files listed will be executed
    ->withBootstrapFiles([
        __DIR__ . '/spec/bootstrap.php',
    ])
    ->withPHPStanConfigs([
        // __DIR__ . '/phpstan.neon.dist',
    ])
	// is there a file you need to skip?
    ->withSkip([
        __DIR__ . '/src/Debug/Toolbar/Views/toolbar.tpl.php',
        __DIR__ . '/spec/support/application/app/Views',

        RemoveUnusedConstructorParamRector::class => [
            // @TODO remove if deprecated $httpVerb is removed
            __DIR__ . '/src/Router/AutoRouter.php',
        ],

        // use mt_rand instead of random_int on purpose on non-cryptographically random
        RandomFunctionRector::class,

        MixedTypeRector::class,

        // Unnecessary (string) is inserted
        NullToStrictStringFuncCallArgRector::class,
    ])
    // auto import fully qualified class names
    ->withImportNames(removeUnusedImports: true)
    ->withRules([
        // DeclareStrictTypesRector::class,
        SimplifyUselessVariableRector::class,
        RemoveAlwaysElseRector::class,
        CountArrayToEmptyArrayComparisonRector::class,
        ChangeNestedForeachIfsToEarlyContinueRector::class,
        ChangeIfElseValueAssignToEarlyReturnRector::class,
        SimplifyStrposLowerRector::class,
        CombineIfRector::class,
        SimplifyIfReturnBoolRector::class,
        InlineIfToExplicitIfRector::class,
        PreparedValueToEarlyReturnRector::class,
        ShortenElseIfRector::class,
        SimplifyIfElseToTernaryRector::class,
        UnusedForeachValueToArrayKeysRector::class,
        ChangeArrayPushToArrayAssignRector::class,
        UnnecessaryTernaryExpressionRector::class,
        FuncGetArgsToVariadicParamRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        SimplifyEmptyArrayCheckRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        TernaryEmptyArrayArrayDimFetchToCoalesceRector::class,
        EmptyOnNullableObjectToInstanceOfRector::class,
        DisallowedEmptyRuleFixerRector::class,
        PrivatizeFinalClassPropertyRector::class,
        CompleteDynamicPropertiesRector::class,
        BooleanInIfConditionRuleFixerRector::class,
        SingleInArrayToCompareRector::class,
        VersionCompareFuncCallToConstantRector::class,
        ExplicitBoolCompareRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddFunctionVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withConfiguredRule(StringClassNameToClassConstantRector::class, [
        // keep '\\' prefix string on string '\Foo\Bar'
        StringClassNameToClassConstantRector::SHOULD_KEEP_PRE_SLASH => true,
    ])

	;

