<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Mail;

interface MailerInterface
{
    public const PRIORITY_HIGH                      = 1;
    public const PRIORITY_NORMAL                    = 3;
    public const PRIORITY_LOW                       = 5;
    public const PROTOCOL_GMAIL                     = 'gmail';
    public const PROTOCOL_MAIL                      = 'mail';
    public const PROTOCOL_MAILGUN                   = 'mailgun';
    public const PROTOCOL_MANDRILL                  = 'mandrill';
    public const PROTOCOL_POSTMARK                  = 'postmark';
    public const PROTOCOL_QMAIL                     = 'qmail';
    public const PROTOCOL_SENDGRID                  = 'sendgrid';
    public const PROTOCOL_SENDMAIL                  = 'sendmail';
    public const PROTOCOL_SES                       = 'ses';
    public const PROTOCOL_SMTP                      = 'smtp';
    public const ENCRYPTION_SSL                     = 'ssl';
    public const ENCRYPTION_TLS                     = 'tls';
    public const ENCRYPTION_NONE                    = 'none';
    public const CHARSET_ASCII                      = 'us-ascii';
    public const CHARSET_ISO88591                   = 'iso-8859-1';
    public const CHARSET_UTF8                       = 'utf-8';
    public const CONTENT_TYPE_PLAINTEXT             = 'text/plain';
    public const CONTENT_TYPE_TEXT_CALENDAR         = 'text/calendar';
    public const CONTENT_TYPE_TEXT_HTML             = 'text/html';
    public const CONTENT_TYPE_MULTIPART_ALTERNATIVE = 'multipart/alternative';
    public const CONTENT_TYPE_MULTIPART_MIXED       = 'multipart/mixed';
    public const CONTENT_TYPE_MULTIPART_RELATED     = 'multipart/related';
    public const ENCODING_7BIT                      = '7bit';
    public const ENCODING_8BIT                      = '8bit';
    public const ENCODING_BASE64                    = 'base64';
    public const ENCODING_BINARY                    = 'binary';
    public const ENCODING_QUOTED_PRINTABLE          = 'quoted-printable';
    public const ICAL_METHOD_REQUEST                = 'REQUEST';
    public const ICAL_METHOD_PUBLISH                = 'PUBLISH';
    public const ICAL_METHOD_REPLY                  = 'REPLY';
    public const ICAL_METHOD_ADD                    = 'ADD';
    public const ICAL_METHOD_CANCEL                 = 'CANCEL';
    public const ICAL_METHOD_REFRESH                = 'REFRESH';
    public const ICAL_METHOD_COUNTER                = 'COUNTER';
    public const ICAL_METHOD_DECLINECOUNTER         = 'DECLINECOUNTER';

    /**
     * Ajoute un texte alternatif pour le message en cas de nom prise en charge du html
     */
    public function alt(string $content): static;

    /**
     * Ajoute des pièces jointes au mail a partir d'un chemin du systeme de fichier.
     * N'utilisez jamais un chemin d'accès fourni par l'utilisateur vers un fichier !
     * Renvoie faux si le fichier n'a pas pu être trouvé ou lu.
     * Explicitement *ne prend pas* en charge la transmission d'URL ; Mailer n'est pas un client HTTP.
     * Si vous avez besoin de le faire, récupérez la ressource vous-même et transmettez-la via un fichier local ou une chaîne.
     */
    public function attach(array|string $path, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static;

    /**
     * Ajoutez une chaîne ou une pièce jointe binaire (non-système de fichiers).
     * Cette méthode peut être utilisée pour joindre des données ascii ou binaires,
     * tel qu'un enregistrement BLOB d'une base de données.
     *
     * @param mixed $binary
     */
    public function attachBinary($binary, string $name, string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'attachment'): static;

    /**
     *Ajoute des adresses de copie (BCC) au mail
     */
    public function bcc(array|string $address, bool|string $name = '', bool $set = false): static;

    /**
     * Ajoute des adresses de copie (CC) au mail
     */
    public function cc(array|string $address, bool|string $name = '', bool $set = false): static;

    public function dkim(string $pk, string $passphrase = '', string $selector = '', string $domain = ''): static;

    /**
     * Ajouter une pièce jointe intégrée (en ligne) à partir d'un fichier.
     * Cela peut inclure des images, des sons et à peu près n'importe quel autre type de document.
     * Celles-ci diffèrent des pièces jointes « régulières » en ce sens qu'elles sont destinées à être
     * affiché en ligne avec le message, pas seulement en pièce jointe pour le téléchargement.
     * Ceci est utilisé dans les messages HTML qui intègrent les images
     * le HTML fait référence à l'utilisation de la valeur `$cid` dans les balises `img`, par exemple `<img src="cid:mylogo">`.
     * N'utilisez jamais un chemin d'accès fourni par l'utilisateur vers un fichier !     *
     */
    public function embedded(string $path, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static;

    /**
     * Ajoutez une pièce jointe stringifiée intégrée.
     * Cela peut inclure des images, des sons et à peu près n'importe quel autre type de document.
     * Si votre nom de fichier ne contient pas d'extension, assurez-vous de définir $type sur un type MIME approprié.
     *
     * @param mixed $binary
     */
    public function embeddedBinary($binary, string $cid, string $name = '', string $type = '', string $encoding = self::ENCODING_BASE64, string $disposition = 'inline'): static;

    /**
     * Defini l'adresse de l'expéditeur (From) du mail
     */
    public function from(string $address, string $name = ''): static;

    /**
     * Ajoute des entêtes personnalisées au mail à envoyer
     */
    public function header(array|string $name, ?string $value = null): static;

    /**
     * Defini le message à envoyer au format html
     */
    public function html(string $content): static;

    /**
     * Initialise le gestionnaire d'email avec les configurations données
     */
    public function init(array $config): static;

    /**
     * Renvoie l'identifiant du dernier mail envoyé
     */
    public function lastId(): string;

    /**
     * Defini le message à envoyer
     */
    public function message(string $message): static;

    /**
     * Ajoute les adresses de reponse (Reply-To) au mail
     */
    public function replyTo(array|string $address, bool|string $name = '', bool $set = false): static;

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
    public function sign(string $cert_filename, string $key_filename, string $key_pass, string $extracerts_filename = ''): static;

    /**
     * Defini le sujet du mail
     */
    public function subject(string $subject): static;

    /**
     * Defini le message à envoyer au format texte
     */
    public function text(string $content): static;

    /**
     * Ajoute l'adresse de destination (To) du mail
     */
    public function to(array|string $address, bool|string $name = '', bool $set = false): static;
}
