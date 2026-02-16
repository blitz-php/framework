<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Cache\\\\Clear\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Cache/Clear.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Cache\\\\Info\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Cache/Info.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Cli\\\\Commands\\\\Config\\\\About\\:\\:\\$options \\(array\\<string, string\\>\\) does not accept default value of type array\\<string, list\\<string\\>\\>\\.$#',
	'identifier' => 'property.defaultValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Config/About.php',
];
$ignoreErrors[] = [
	'message' => '#^Invalid array key type list\\<string\\>\\.$#',
	'identifier' => 'offsetAccess.invalidOffset',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Config/ConfigPublish.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset array on array\\<int\\<1, max\\>, \\(int\\|string\\)\\> on left side of \\?\\? does not exist\\.$#',
	'identifier' => 'nullCoalesce.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Config/ConfigPublish.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Config\\\\Namespaces\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Config/Namespaces.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Encryption\\\\GenerateKey\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Encryption/GenerateKey.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Generators\\\\Command\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Generators/Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Generators\\\\Controller\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Generators/Controller.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Generators\\\\Mail\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Generators/Mail.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Generators\\\\Middleware\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Generators/Middleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Cli\\\\Commands\\\\Generators\\\\Middleware\\:\\:\\$options \\(array\\<string, array\\{0\\: string, 1\\?\\: mixed, 2\\?\\: \\(callable\\(\\)\\: mixed\\)\\|null\\}\\>\\) does not accept default value of type array\\{\'\\-\\-namespace\'\\: array\\{\'Définit l\\\\\'espace de…\', \'App\'\\}, \'\\-\\-suffix\'\\: \'Ajouter le titre du…\', \'\\-\\-force\'\\: \'Forcer à écraser le…\', \'\\-\\-standard\'\\: \'Le standard utilisé…\'\\}\\.$#',
	'identifier' => 'property.defaultValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Generators/Middleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Generators\\\\Scaffold\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 2,
	'path' => __DIR__ . '/src/Cli/Commands/Generators/Scaffold.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Cli\\\\Commands\\\\Generators\\\\Scaffold\\:\\:\\$options \\(array\\<string, array\\{0\\: string, 1\\?\\: mixed, 2\\?\\: \\(callable\\(\\)\\: mixed\\)\\|null\\}\\>\\) does not accept default value of type array\\{\'\\-\\-bare\'\\: \'Ajoute l\\\\\'option "\\-…\', \'\\-\\-restful\'\\: \'Ajoute l\\\\\'option "\\-…\', \'\\-\\-table\'\\: \'Ajoute l\\\\\'option "\\-…\', \'\\-\\-dbgroup\'\\: \'Ajoute l\\\\\'option "\\-…\', \'\\-\\-return\'\\: \'Ajoute l\\\\\'option "\\-…\', \'\\-\\-namespace\'\\: array\\{\'Définissez l\\\\\'espace…\', \'App\'\\}, \'\\-\\-suffix\'\\: array\\{\'Ajoutez le titre du…\', true\\}, \'\\-\\-force\'\\: \'Forcer l\\\\\'écrasement…\'\\}\\.$#',
	'identifier' => 'property.defaultValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Generators/Scaffold.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Generators\\\\Validation\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Generators/Validation.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Housekeeping\\\\ClearDebugbar\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Housekeeping/ClearDebugbar.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Housekeeping\\\\ClearLogs\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Housekeeping/ClearLogs.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Routes\\\\Routes\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Routes/Routes.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Server\\\\Serve\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 2,
	'path' => __DIR__ . '/src/Cli/Commands/Server/Serve.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Utilities\\\\Environment\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Utilities/Environment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Utilities\\\\Publish\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Utilities/Publish.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Cli\\\\Commands\\\\Various\\\\Inspiring\\:\\:handle\\(\\) should return mixed but return statement is missing\\.$#',
	'identifier' => 'return.missing',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Commands/Various/Inspiring.php',
];
$ignoreErrors[] = [
	'message' => '#^Trait BlitzPHP\\\\Cli\\\\Traits\\\\ContentReplacer is used zero times and is not analysed\\.$#',
	'identifier' => 'trait.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Traits/ContentReplacer.php',
];
$ignoreErrors[] = [
	'message' => '#^Dead catch \\- BlitzPHP\\\\Exceptions\\\\UnknownOptionException is never thrown in the try block\\.$#',
	'identifier' => 'catch.neverThrown',
	'count' => 1,
	'path' => __DIR__ . '/src/Config/Configurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Dead catch \\- Dflydev\\\\DotAccessData\\\\Exception\\\\MissingPathException is never thrown in the try block\\.$#',
	'identifier' => 'catch.neverThrown',
	'count' => 1,
	'path' => __DIR__ . '/src/Config/Configurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Config\\\\Configurator\\:\\:get\\(\\) has BlitzPHP\\\\Exceptions\\\\ConfigException in PHPDoc @throws tag but it\'s not thrown\\.$#',
	'identifier' => 'throws.unusedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Config/Configurator.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined static method BlitzPHP\\\\Container\\\\BaseServices\\:\\:config\\(\\)\\.$#',
	'identifier' => 'staticMethod.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Container/BaseServices.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined static method BlitzPHP\\\\Container\\\\BaseServices\\:\\:container\\(\\)\\.$#',
	'identifier' => 'staticMethod.notFound',
	'count' => 4,
	'path' => __DIR__ . '/src/Container/BaseServices.php',
];
$ignoreErrors[] = [
	'message' => '#^Template type T of method BlitzPHP\\\\Container\\\\BaseServices\\:\\:factory\\(\\) is not referenced in a parameter\\.$#',
	'identifier' => 'method.templateTypeNotInParameter',
	'count' => 1,
	'path' => __DIR__ . '/src/Container/BaseServices.php',
];
$ignoreErrors[] = [
	'message' => '#^Template type T of method BlitzPHP\\\\Container\\\\BaseServices\\:\\:singleton\\(\\) is not referenced in a parameter\\.$#',
	'identifier' => 'method.templateTypeNotInParameter',
	'count' => 1,
	'path' => __DIR__ . '/src/Container/BaseServices.php',
];
$ignoreErrors[] = [
	'message' => '#^Template type T of method BlitzPHP\\\\Container\\\\Container\\:\\:get\\(\\) is not referenced in a parameter\\.$#',
	'identifier' => 'method.templateTypeNotInParameter',
	'count' => 1,
	'path' => __DIR__ . '/src/Container/Container.php',
];
$ignoreErrors[] = [
	'message' => '#^Template type T of method BlitzPHP\\\\Container\\\\Container\\:\\:make\\(\\) is not referenced in a parameter\\.$#',
	'identifier' => 'method.templateTypeNotInParameter',
	'count' => 1,
	'path' => __DIR__ . '/src/Container/Container.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$result might not be defined\\.$#',
	'identifier' => 'variable.undefined',
	'count' => 1,
	'path' => __DIR__ . '/src/Debug/Toolbar.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Event\\\\Event\\:\\:\\$name on left side of \\?\\? is not nullable nor uninitialized\\.$#',
	'identifier' => 'nullCoalesce.initializedProperty',
	'count' => 1,
	'path' => __DIR__ . '/src/Event/Event.php',
];
$ignoreErrors[] = [
	'message' => '#^Trait BlitzPHP\\\\Event\\\\EventListenerManagerTrait is used zero times and is not analysed\\.$#',
	'identifier' => 'trait.unused',
	'count' => 1,
	'path' => __DIR__ . '/src/Event/EventListenerManagerTrait.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Event/EventManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between Closure\\(mixed \\.\\.\\.\\)\\: mixed and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Event/EventManager.php',
];
$ignoreErrors[] = [
	'message' => '#^Unsafe usage of new static\\(\\)\\.$#',
	'identifier' => 'new.static',
	'count' => 4,
	'path' => __DIR__ . '/src/Exceptions/MailException.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Cache\\:\\:remember\\(\\) parameter \\#2 \\$ttl contains unknown class BlitzPHP\\\\Facades\\\\DateInterval\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Cache.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Cache\\:\\:set\\(\\) parameter \\#3 \\$ttl contains unknown class BlitzPHP\\\\Facades\\\\DateInterval\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Cache.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Cache\\:\\:setMultiple\\(\\) parameter \\#2 \\$ttl contains unknown class BlitzPHP\\\\Facades\\\\DateInterval\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Cache.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Cache\\:\\:write\\(\\) parameter \\#3 \\$ttl contains unknown class BlitzPHP\\\\Facades\\\\DateInterval\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Cache.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Cache\\:\\:writeMany\\(\\) parameter \\#2 \\$ttl contains unknown class BlitzPHP\\\\Facades\\\\DateInterval\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Cache.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:action\\(\\) parameter \\#1 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:critical\\(\\) parameter \\#1 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:debug\\(\\) parameter \\#1 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:emergency\\(\\) parameter \\#1 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:error\\(\\) parameter \\#1 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:info\\(\\) parameter \\#1 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:log\\(\\) parameter \\#2 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:notice\\(\\) parameter \\#1 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Log\\:\\:warning\\(\\) parameter \\#1 \\$message contains unknown class BlitzPHP\\\\Facades\\\\Stringable\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Log.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method has invalid value \\(static void         configure\\(callable \\$callback\\(RouteBuilder \\$route\\)\\)                         Configure les parametres de routing\\.\\)\\: Unexpected token "\\(", expected \'\\)\' at offset 63 on line 2$#',
	'identifier' => 'phpDoc.parseError',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Route.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Storage\\:\\:download\\(\\) return type contains unknown class Symfony\\\\Component\\\\HttpFoundation\\\\StreamedResponse\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Storage.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Storage\\:\\:putFile\\(\\) parameter \\#2 \\$file contains unknown class Illuminate\\\\Http\\\\File\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Storage.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Storage\\:\\:putFile\\(\\) parameter \\#2 \\$file contains unknown class Illuminate\\\\Http\\\\UploadedFile\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Storage.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Storage\\:\\:putFileAs\\(\\) parameter \\#2 \\$file contains unknown class Illuminate\\\\Http\\\\File\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Storage.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Storage\\:\\:putFileAs\\(\\) parameter \\#2 \\$file contains unknown class Illuminate\\\\Http\\\\UploadedFile\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Storage.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Storage\\:\\:response\\(\\) return type contains unknown class Symfony\\\\Component\\\\HttpFoundation\\\\StreamedResponse\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Storage.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\Storage\\:\\:setApplication\\(\\) parameter \\#1 \\$app contains unknown class Illuminate\\\\Contracts\\\\Foundation\\\\Application\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Storage.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Facades\\\\View\\:\\:share\\(\\) parameter \\#1 \\$key contains unknown class BlitzPHP\\\\Facades\\\\Closure\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/View.php',
];
$ignoreErrors[] = [
	'message' => '#^Negated boolean expression is always false\\.$#',
	'identifier' => 'booleanNot.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/assets.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method regenerate\\(\\) on array\\|bool\\|float\\|int\\|object\\|string\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/common.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method token\\(\\) on array\\|bool\\|float\\|int\\|object\\|string\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/common.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$result might not be defined\\.$#',
	'identifier' => 'variable.undefined',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/filesystem.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method BlitzPHP\\\\Http\\\\Request\\:\\:getFormat\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method BlitzPHP\\\\Http\\\\Request\\:\\:getMimeType\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Http\\\\Request\\:\\:validation\\(\\) should return BlitzPHP\\\\Validation\\\\Validation but returns Dimtrovich\\\\Validation\\\\Validation\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$default of method BlitzPHP\\\\Http\\\\Request\\:\\:old\\(\\) has invalid type BlitzPHP\\\\Wolke\\\\Model\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between null and BlitzPHP\\\\Session\\\\Store will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type list\\<BlitzPHP\\\\Session\\\\Cookie\\\\Cookie\\> is not subtype of native type BlitzPHP\\\\Session\\\\Cookie\\\\CookieCollection\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Response.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in require_once\\(\\) "D\\:\\\\dev\\\\github\\\\blitzphp\\\\framework\\\\spec\\\\support\\\\application\\\\app\\\\Config/constants\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'requireOnce.fileNotFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Initializer/Boot.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in require_once\\(\\) "D\\:\\\\dev\\\\github\\\\blitzphp\\\\framework\\\\spec\\\\support\\\\application\\\\app\\\\Config\\\\constants\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'requireOnce.fileNotFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Initializer/Boot.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in require_once\\(\\) "D\\:\\\\dev\\\\github\\\\blitzphp\\\\framework\\\\spec\\\\support\\\\application\\\\app\\\\Helpers/common\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'requireOnce.fileNotFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Initializer/Boot.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in require_once\\(\\) "D\\:\\\\dev\\\\github\\\\blitzphp\\\\framework\\\\spec\\\\support\\\\application\\\\app\\\\Helpers\\\\common\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'requireOnce.fileNotFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Initializer/Boot.php',
];
$ignoreErrors[] = [
	'message' => '#^Path in require_once\\(\\) "D\\:\\\\dev\\\\github\\\\blitzphp\\\\framework\\\\testing\\.php" is not a file or it does not exist\\.$#',
	'identifier' => 'requireOnce.fileNotFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Initializer/Boot.php',
];
$ignoreErrors[] = [
	'message' => '#^Unsafe usage of new static\\(\\)\\.$#',
	'identifier' => 'new.static',
	'count' => 1,
	'path' => __DIR__ . '/src/Initializer/Boot.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Loader/DotEnv.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of \\|\\| is always false\\.$#',
	'identifier' => 'booleanOr.rightAlwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Loader/DotEnv.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$line in empty\\(\\) always exists and is not falsy\\.$#',
	'identifier' => 'empty.variable',
	'count' => 1,
	'path' => __DIR__ . '/src/Loader/DotEnv.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Symfony\\\\Component\\\\Mailer\\\\Transport\\\\Dsn\\:\\:withOption\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Mail\\\\Adapters\\\\SymfonyMailer\\:\\:\\$mailer \\(Symfony\\\\Component\\\\Mime\\\\Email\\) does not accept Symfony\\\\Component\\\\Mime\\\\Message\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Mail\\\\Adapters\\\\SymfonyMailer\\:\\:\\$timeout is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type \\(Symfony\\\\Component\\\\Mime\\\\Address\\) of method BlitzPHP\\\\Mail\\\\Adapters\\\\SymfonyMailer\\:\\:makeAddress\\(\\) should be compatible with return type \\(array\\{string, string\\}\\) of method BlitzPHP\\\\Mail\\\\Adapters\\\\AbstractAdapter\\:\\:makeAddress\\(\\)$#',
	'identifier' => 'method.childReturnType',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method convert\\(\\) on an unknown class League\\\\CommonMark\\\\CommonMarkConverter\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Mail.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class League\\\\CommonMark\\\\CommonMarkConverter not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Mail.php',
];
$ignoreErrors[] = [
	'message' => '#^Unsafe usage of new static\\(\\)\\.$#',
	'identifier' => 'new.static',
	'count' => 1,
	'path' => __DIR__ . '/src/Middlewares/BaseMiddleware.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setPreviousUrl\\(\\) on array\\|bool\\|float\\|int\\|object\\|string\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/src/Router/Dispatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Router\\\\Dispatcher\\:\\:\\$request \\(BlitzPHP\\\\Http\\\\Request\\) does not accept Psr\\\\Http\\\\Message\\\\ServerRequestInterface\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Router/Dispatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Router\\\\Dispatcher\\:\\:\\$response \\(BlitzPHP\\\\Http\\\\Response\\) does not accept Psr\\\\Http\\\\Message\\\\ResponseInterface\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 3,
	'path' => __DIR__ . '/src/Router/Dispatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between mixed and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Security/Encryption/KeyRotationDecorator.php',
];
$ignoreErrors[] = [
	'message' => '#^Result of && is always false\\.$#',
	'identifier' => 'booleanAnd.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Security/Hashing/Handlers/ArgonHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between \'standard\' and \'sodium\' will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Security/Hashing/Handlers/ArgonHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method BlitzPHP\\\\Contracts\\\\Security\\\\HasherInterface\\:\\:verifyConfiguration\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Security/Hashing/Hasher.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot unset offset string on list\\<int\\>\\.$#',
	'identifier' => 'unset.offset',
	'count' => 2,
	'path' => __DIR__ . '/src/Spec/Mock/MockCache.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Validation\\\\Rule\\:\\:exists\\(\\) return type contains unknown class BlitzPHP\\\\Database\\\\Validation\\\\Rules\\\\Exists\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Validation/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method for method BlitzPHP\\\\Validation\\\\Rule\\:\\:unique\\(\\) return type contains unknown class BlitzPHP\\\\Database\\\\Validation\\\\Rules\\\\Unique\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Validation/Rule.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method directive\\(\\) on an unknown class Jenssegers\\\\Blade\\\\Blade\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method if\\(\\) on an unknown class Jenssegers\\\\Blade\\\\Blade\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method render\\(\\) on an unknown class Jenssegers\\\\Blade\\\\Blade\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Jenssegers\\\\Blade\\\\Blade not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Jenssegers\\\\Blade\\\\Blade not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\BladeAdapter\\:\\:\\$engine has unknown class Jenssegers\\\\Blade\\\\Blade as its type\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method renderToString\\(\\) on an unknown class Latte\\\\Engine\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setAutoRefresh\\(\\) on an unknown class Latte\\\\Engine\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setLoader\\(\\) on an unknown class Latte\\\\Engine\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setTempDirectory\\(\\) on an unknown class Latte\\\\Engine\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Latte\\\\Engine not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Latte\\\\Engine not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Latte\\\\Loaders\\\\FileLoader not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\LatteAdapter\\:\\:\\$latte has unknown class Latte\\\\Engine as its type\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addFolder\\(\\) on an unknown class League\\\\Plates\\\\Engine\\.$#',
	'identifier' => 'class.notFound',
	'count' => 2,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method loadExtension\\(\\) on an unknown class League\\\\Plates\\\\Engine\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method registerFunction\\(\\) on an unknown class League\\\\Plates\\\\Engine\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method render\\(\\) on an unknown class League\\\\Plates\\\\Engine\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class League\\\\Plates\\\\Engine not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class League\\\\Plates\\\\Engine not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class League\\\\Plates\\\\Extension\\\\Asset not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\PlatesAdapter\\:\\:\\$engine has unknown class League\\\\Plates\\\\Engine as its type\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to constant CACHING_LIFETIME_SAVED on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to constant CACHING_OFF on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addPluginsDir\\(\\) on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method assign\\(\\) on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method fetch\\(\\) on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCacheLifetime\\(\\) on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCaching\\(\\) on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCompileId\\(\\) on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setTemplateDir\\(\\) on an unknown class Smarty\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Smarty not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Smarty not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\SmartyAdapter\\:\\:\\$engine has unknown class Smarty as its type\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addFilter\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addFunction\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addGlobal\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method disableAutoReload\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method disableDebug\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method disableStrictVariables\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method enableAutoReload\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method enableDebug\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method enableStrictVariables\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method render\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCache\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCharset\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Twig\\\\Environment not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Twig\\\\TwigFilter not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Twig\\\\TwigFunction not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Twig\\\\Environment not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Twig\\\\Loader\\\\FilesystemLoader not found\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$cache_id$#',
	'identifier' => 'parameter.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$compile_id$#',
	'identifier' => 'parameter.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$parent$#',
	'identifier' => 'parameter.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$template$#',
	'identifier' => 'parameter.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\TwigAdapter\\:\\:\\$engine has unknown class Twig\\\\Environment as its type\\.$#',
	'identifier' => 'class.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
