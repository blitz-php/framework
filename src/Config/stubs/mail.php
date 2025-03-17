<?php 

return [
    /**
     * Adresse "From" globale
     * 
     * Vous pouvez souhaiter que tous les mails a envoyés par votre demande soient envoyés 
     * à partir de la même adresse. Ici, vous pouvez spécifier un nom et une adresse qui sont 
     * utilisés globalement pour tous les e-mails envoyés par votre application.
     */
    'from' => [
        'address' => env('mail.from.address', 'hello@example.com'),
        'name' => env('mail.from.name', 'Example'),
    ],

    /**
     * Le nom du gestionnaire a utiliser pour l'envoi des mail.
     * 
     * Valeurs admissibles 
     *  - phpmailer: Necessite l'installation de phpmailer (`composer require phpmailer/phpmailer`)
     *  - symfony: Necessite l'installation de symfony mailer (`composer require symfony/mailer`)
     * 
     * Vous pouvez egalement mettre le FQCN d'une classe qui etend la classe \BlitzPHP\Mail\Adapters\AbstractAdapter
     *
     * @var string|class-string<\BlitzPHP\Mail\Adapters\AbstractAdapter>
     */
    'handler' => 'phpmailer',

    /**
     * Dossier de base dans lequel sera pris les vues des emails
     * Il doit etre un sous dossier de app/Views
     * 
     * Par exemple, si definissez ce parametre a "emails" et vous faites `Services::mail()->view('welcome')`, 
     * alors le code de la vue app/Views/emails/welcome.php sera utiliser comme message pour votre email
     * 
     * @var string
     */
    'view_dir' => 'emails',
    
    /**
     * Layout dont heritera toutes les vues d'emails
     * 
     * @var string
     */
    'template' => '',

    /**
     * DSN du serveur de mail
     * 
     * @var string
     */
    'dsn' => env('mail.dsn', ''),
    
    /**
     * Le protocole d'envoi du mail: mail, sendmail, smtp, qmail (phpmailer)
     * 
     * @var string
     */
    'protocol' => env('mail.protocol', \BlitzPHP\Mail\Mail::PROTOCOL_SENDMAIL),

    /**
     * Adresse du serveur SMTP
     * 
     * @var string
     */
    'host' => env('mail.host', 'localhost'),

    /**
     * Utilisateur SMTP
     * 
     * @var string
     */
    'username' => env('mail.username', ''),

    /**
     * Mot de passe SMTP
     * 
     * @var string
     */
    'password' => env('mail.password', ''),

    /**
     * Port SMTP
     * 
     * @var int
     */
    'port' => (int) env('mail.port', 25),

    /**
     * Timeout SMTP (en secondes)
     * 
     * @var int
     */
    'timeout' => 5,

    /**
     * Encryption SMTP.
     * 
     * \BlitzPHP\Mail\Mail::ENCRYPTION_NONE, \BlitzPHP\Mail\Mail::ENCRYPTION_TLS ou \BlitzPHP\Mail\Mail::ENCRYPTION_SSL. 
     * 
     * - 'tls' émettra une commande STARTTLS *au serveur. 
     * - 'ssl' signifie SSL implicite. 
     * Connexion sur port * 465 devrait définir ceci sur 'none'.
     * 
     * @var string
     */
    'encryption' => env('mail.encryption', \BlitzPHP\Mail\Mail::ENCRYPTION_NONE),

    /**
     *Type de mail par defaut, soit 'text' ou 'html'
     * 
     * @var string
     */
    'mailType' => 'html',

    /**
     * Jeu de caractères (utf-8, iso-8859-1, etc.)
     */
    'charset' => env('mail.charset', \BlitzPHP\Mail\Mail::CHARSET_UTF8),

    /**
     * Priorité d'email. 1 = le plus haut. 5 = le plus bas. 3 = normal
     * 
     * @var int
     */
    'priority' => \BlitzPHP\Mail\Mail::PRIORITY_NORMAL,
];
