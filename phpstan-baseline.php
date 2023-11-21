<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Ahc\\\\Cli\\\\Output\\\\Writer\\:\\:errorBold\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Console/Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Ahc\\\\Cli\\\\Output\\\\Writer\\:\\:infoBold\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Console/Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Ahc\\\\Cli\\\\Output\\\\Writer\\:\\:okBold\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Console/Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Ahc\\\\Cli\\\\Output\\\\Writer\\:\\:warnBold\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Console/Command.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Ahc\\\\Cli\\\\Output\\\\Writer\\:\\:\\$bold\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Console/Console.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Ahc\\\\Cli\\\\Application\\|Ahc\\\\Cli\\\\IO\\\\Interactor\\:\\:write\\(\\)\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Cli/Console/Console.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Ahc\\\\Cli\\\\IO\\\\Interactor\\:\\:write\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Cli/Console/Console.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined static method Nette\\\\Schema\\\\Expect\\:\\:closure\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Constants/schemas/middlewares.config.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property mindplay\\\\annotations\\\\IAnnotation\\:\\:\\$method\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Controllers/RestController.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method make\\(\\) on an unknown class Spatie\\\\Ignition\\\\Ignition\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Debug/Debugger.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @method has invalid value \\(static void         configure\\(callable \\$callback\\(RouteBuilder \\$route\\)\\)                         Configure les parametres de routing\\.\\)\\: Unexpected token "\\(", expected \'\\)\' at offset 63$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Facades/Route.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method BlitzPHP\\\\Http\\\\Request\\:\\:getFormat\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method BlitzPHP\\\\Http\\\\Request\\:\\:getMimeType\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method BlitzPHP\\\\Session\\\\Store\\:\\:flashInput\\(\\)\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Http\\\\Request\\:\\:validation\\(\\) should return BlitzPHP\\\\Validation\\\\Validation but returns Dimtrovich\\\\Validation\\\\Validation\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$default of method BlitzPHP\\\\Http\\\\Request\\:\\:old\\(\\) has invalid type BlitzPHP\\\\Wolke\\\\Model\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Request.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method DateTimeInterface\\:\\:setTimezone\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/Response.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Http\\\\ServerRequest\\:\\:\\$uploadedFiles \\(array\\<BlitzPHP\\\\Filesystem\\\\Files\\\\UploadedFile\\>\\) does not accept array\\<array\\<BlitzPHP\\\\Filesystem\\\\Files\\\\UploadedFile\\>\\|Psr\\\\Http\\\\Message\\\\UploadedFileInterface\\>\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Http/ServerRequest.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to constant DEBUG_SERVER on an unknown class PHPMailer\\\\PHPMailer\\\\SMTP\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$AltBody on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Body on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$CharSet on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$DKIM_domain on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$DKIM_identity on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$DKIM_passphrase on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$DKIM_private on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$DKIM_selector on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Debugoutput on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$From on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Host on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Password on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Port on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Priority on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$SMTPAuth on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$SMTPDebug on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$SMTPSecure on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Subject on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Timeout on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to property \\$Username on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addAddress\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addAttachment\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addBCC\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addCC\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addCustomHeader\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addEmbeddedImage\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addReplyTo\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addStringAttachment\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addStringEmbeddedImage\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method clearAddresses\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method clearBCCs\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method clearCCs\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method clearReplyTos\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getLastMessageID\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method isHTML\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method isMail\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method isQmail\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method isSMTP\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method isSendmail\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method send\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setFrom\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method sign\\(\\) on an unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Class PHPMailer\\\\PHPMailer\\\\PHPMailer not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class PHPMailer\\\\PHPMailer\\\\PHPMailer not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @throws with type PHPMailer\\\\PHPMailer\\\\Exception is not subtype of Throwable$#',
	'count' => 11,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Mail\\\\Adapters\\\\PHPMailer\\:\\:\\$mailer has unknown class PHPMailer\\\\PHPMailer\\\\PHPMailer as its type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/PHPMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addBcc\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addCC\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addPart\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addReplyTo\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addTo\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method asInline\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Part\\\\DataPart\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method bcc\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method cc\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method from\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method generateMessageId\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getHeaders\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method html\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method priority\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method replyTo\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method send\\(\\) on an unknown class Symfony\\\\Component\\\\Mailer\\\\Mailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method sign\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Crypto\\\\DkimSigner\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method sign\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Crypto\\\\SMimeSigner\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method subject\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method text\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method to\\(\\) on an unknown class Symfony\\\\Component\\\\Mime\\\\Email\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method fromDsn\\(\\) on an unknown class Symfony\\\\Component\\\\Mailer\\\\Transport\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Symfony\\\\Component\\\\Mailer\\\\Mailer not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Symfony\\\\Component\\\\Mailer\\\\Mailer not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Symfony\\\\Component\\\\Mime\\\\Address not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Symfony\\\\Component\\\\Mime\\\\Crypto\\\\DkimSigner not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Symfony\\\\Component\\\\Mime\\\\Crypto\\\\SMimeSigner not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Symfony\\\\Component\\\\Mime\\\\Email not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Symfony\\\\Component\\\\Mime\\\\Part\\\\DataPart not found\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Symfony\\\\Component\\\\Mime\\\\Part\\\\File not found\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Mail\\\\Adapters\\\\SymfonyMailer\\:\\:makeAddress\\(\\) has invalid return type Symfony\\\\Component\\\\Mime\\\\Address\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method BlitzPHP\\\\Mail\\\\Adapters\\\\SymfonyMailer\\:\\:transporter\\(\\) has invalid return type Symfony\\\\Component\\\\Mailer\\\\Mailer\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Mail\\\\Adapters\\\\SymfonyMailer\\:\\:\\$mailer has unknown class Symfony\\\\Component\\\\Mime\\\\Email as its type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Mail\\\\Adapters\\\\SymfonyMailer\\:\\:\\$transporter has unknown class Symfony\\\\Component\\\\Mailer\\\\Mailer as its type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Mail/Adapters/SymfonyMailer.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Router\\\\Dispatcher\\:\\:\\$request \\(BlitzPHP\\\\Http\\\\ServerRequest\\) does not accept Psr\\\\Http\\\\Message\\\\ServerRequestInterface\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Router/Dispatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\Router\\\\Dispatcher\\:\\:\\$response \\(BlitzPHP\\\\Http\\\\Response\\) does not accept Psr\\\\Http\\\\Message\\\\ResponseInterface\\.$#',
	'count' => 4,
	'path' => __DIR__ . '/src/Router/Dispatcher.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getKeyName\\(\\) on an unknown class BlitzPHP\\\\Wolke\\\\Model\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Validation/Rules/Unique.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$entity of method BlitzPHP\\\\Validation\\\\Rules\\\\Unique\\:\\:ignoreModel\\(\\) has invalid type BlitzPHP\\\\Wolke\\\\Model\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Validation/Rules/Unique.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method directive\\(\\) on an unknown class Jenssegers\\\\Blade\\\\Blade\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method if\\(\\) on an unknown class Jenssegers\\\\Blade\\\\Blade\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method render\\(\\) on an unknown class Jenssegers\\\\Blade\\\\Blade\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Jenssegers\\\\Blade\\\\Blade not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\BladeAdapter\\:\\:\\$engine has unknown class Jenssegers\\\\Blade\\\\Blade as its type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/BladeAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method renderToString\\(\\) on an unknown class Latte\\\\Engine\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setAutoRefresh\\(\\) on an unknown class Latte\\\\Engine\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setLoader\\(\\) on an unknown class Latte\\\\Engine\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setTempDirectory\\(\\) on an unknown class Latte\\\\Engine\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Latte\\\\Engine not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Latte\\\\Engine not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Latte\\\\Loaders\\\\FileLoader not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\LatteAdapter\\:\\:\\$latte has unknown class Latte\\\\Engine as its type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/LatteAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addFolder\\(\\) on an unknown class League\\\\Plates\\\\Engine\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method loadExtension\\(\\) on an unknown class League\\\\Plates\\\\Engine\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method registerFunction\\(\\) on an unknown class League\\\\Plates\\\\Engine\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method render\\(\\) on an unknown class League\\\\Plates\\\\Engine\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class League\\\\Plates\\\\Engine not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class League\\\\Plates\\\\Engine not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class League\\\\Plates\\\\Extension\\\\Asset not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\PlatesAdapter\\:\\:\\$engine has unknown class League\\\\Plates\\\\Engine as its type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/PlatesAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to constant CACHING_LIFETIME_SAVED on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to constant CACHING_OFF on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addPluginsDir\\(\\) on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method assign\\(\\) on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method fetch\\(\\) on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCacheLifetime\\(\\) on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCaching\\(\\) on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCompileId\\(\\) on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setTemplateDir\\(\\) on an unknown class Smarty\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Smarty not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Smarty not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\SmartyAdapter\\:\\:\\$engine has unknown class Smarty as its type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/SmartyAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addFilter\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addFunction\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method addGlobal\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method disableAutoReload\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method disableDebug\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method disableStrictVariables\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method enableAutoReload\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method enableDebug\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method enableStrictVariables\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method render\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCache\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method setCharset\\(\\) on an unknown class Twig\\\\Environment\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Twig\\\\Environment not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Twig\\\\TwigFilter not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Class Twig\\\\TwigFunction not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Twig\\\\Environment not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Instantiated class Twig\\\\Loader\\\\FilesystemLoader not found\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$cache_id$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$compile_id$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$parent$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @param references unknown parameter\\: \\$template$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];
$ignoreErrors[] = [
	'message' => '#^Property BlitzPHP\\\\View\\\\Adapters\\\\TwigAdapter\\:\\:\\$engine has unknown class Twig\\\\Environment as its type\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/View/Adapters/TwigAdapter.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
