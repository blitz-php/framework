<?php

namespace BlitzPHP\Mail;

interface MailerInterface
{
    public const PRIORITY_HIGH   = 1;
    public const PRIORITY_NORMAL = 3;
    public const PRIORITY_LOW    = 5;

    public const PROTOCOL_MAIL     = 'mail';
    public const PROTOCOL_QMAIL    = 'qmail';
    public const PROTOCOL_SENDMAIL = 'sendmail';
    public const PROTOCOL_SMTP     = 'smtp';
    public const PROTOCOL_SES      = 'ses';
    public const PROTOCOL_MAILGUN  = 'mailgun';
    public const PROTOCOL_POSTMARK = 'postmark';

    public const ENCRYPTION_SSL = 'ssl';
    public const ENCRYPTION_TLS = 'tls';

    /**
	 * Ajoute un texte alternatif pour le message en cas de nom prise en charge du html
     */
    public function alt(string $content): self;
    
    /**
     * Ajoute des pièces jointes au mail a partir d'un chemin du systeme de fichier.
     * N'utilisez jamais un chemin d'accès fourni par l'utilisateur vers un fichier !
     * Renvoie faux si le fichier n'a pas pu être trouvé ou lu.
     * Explicitement *ne prend pas* en charge la transmission d'URL ; Mailer n'est pas un client HTTP.
     * Si vous avez besoin de le faire, récupérez la ressource vous-même et transmettez-la via un fichier local ou une chaîne.
     */
    public function attachment(string $path, string $name = '', string $encoding = '', string $disposition = 'attachment'): self;

    /**
     *Ajoute des adresses de copie (BCC) au mail
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false): self;

    /**
     * Ajoute des adresses de copie (CC) au mail
     */
    public function cc(array|string $address, bool|string $name = '', bool $set = false): self;

    /**
     * Defini l'adresse de l'expéditeur (From) du mail
     */
    public function from(string $address, string $name = ''): self;

    /**
     * Ajoute des entêtes personnalisées au mail à envoyer  
     */
    public function header(array|string $name, ?string $value = null): self;
    
    /**
	 * Defini le message à envoyer au format html
     */
    public function html(string $content): self;
    
    /**
     * Initialise le gestionnaire d'email avec les configurations données
     */
    public function init(array $config): self;
    
    /**
	 * Renvoie l'identifiant du dernier mail envoyé
     */
    public function lastId(): string;
    
    /**
	 * Defini le message à envoyer
     */
    public function message(string $message) : self;
    
    /**
     * Ajoute les adresses de reponse (Reply-To) au mail
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): self;

    /**
     * Lance l'envoi du message
     *
     * @return bool false on error
     */
    public function send(): bool;
    
    /**
     * Définissez les fichiers de clé publique et privée et le mot de passe pour la signature S/MIME.
     *
     * @param string $key_pass            Mot de passe pour la clé privée
     * @param string $extracerts_filename Chemin facultatif vers le certificat de chaîne
     */
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): self;

    /**
	 * Defini le sujet du mail
     */
    public function subject(string $subject): self;
    
    /**
	 * Defini le message à envoyer au format texte
     */
    public function text(string $content): self;
    
    /**
	 * Ajoute l'adresse de destination (To) du mail
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): self;
}
